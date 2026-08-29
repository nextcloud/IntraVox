<?php

declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\People;

use OCA\IntraVox\Service\UserService;
use OCA\IntraVox\Service\People\ProfileFilterMatcher;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The warmup job must rebuild a cohort for the audience it was recorded
 * under, not for the audience of the process running the job.
 *
 * Found on live data (dev.rikdekker.nl): a viewer's filter panel rendered its
 * group headings with no options at all after a page reload, then filled back
 * in by itself some minutes later.
 *
 * The cause was not the widget config, which survived the reload intact. Every
 * account property that carries scope LOCAL — role, organisation — and every
 * IntraVox custom field — werking, thema, gebouw — was missing from the cached
 * cohort snapshot, so there was nothing left to count and every facet came back
 * with an empty value list.
 *
 * PeopleCohortWarmupJob runs without a session. scanCohort() read the audience
 * from currentAudience(), which fails closed to anonymous when there is no
 * session — correct for a real request, wrong for a rebuild. The job therefore
 * rebuilt the *logged-in* cohort as anonymous, stripping every Local field, and
 * wrote the result straight back over the logged-in cache key. The panel stayed
 * empty until a real browser request rebuilt the entry, which is exactly the
 * come-and-go the bug report described.
 *
 * The registry already stored the audience per cohort; the job simply never
 * read it back.
 */
class CohortWarmupAudienceTest extends TestCase {
	/**
	 * Scopes mirror a real instance: displayname is federated (visible to
	 * anonymous share visitors), role and organisation are local.
	 *
	 * @var array<string, array<string, array{value: string, scope: string}>>
	 */
	private const PEOPLE = [
		'u1' => [
			'displayname' => ['value' => 'Anne Vries', 'scope' => IAccountManager::SCOPE_FEDERATED],
			'role' => ['value' => 'Manager', 'scope' => IAccountManager::SCOPE_LOCAL],
			'organisation' => ['value' => 'Marketing', 'scope' => IAccountManager::SCOPE_LOCAL],
		],
		'u2' => [
			'displayname' => ['value' => 'Bo Jansen', 'scope' => IAccountManager::SCOPE_FEDERATED],
			'role' => ['value' => 'Adviseur', 'scope' => IAccountManager::SCOPE_LOCAL],
			'organisation' => ['value' => 'Sales', 'scope' => IAccountManager::SCOPE_LOCAL],
		],
	];

	/** An ICache backed by a plain array, so TTLs never enter into it. */
	private function makeCache(array &$store): ICache {
		$cache = $this->createMock(ICache::class);
		$cache->method('get')->willReturnCallback(
			fn(string $key) => $store[$key] ?? null
		);
		$cache->method('set')->willReturnCallback(
			function (string $key, $value) use (&$store): bool {
				$store[$key] = $value;
				return true;
			}
		);
		$cache->method('remove')->willReturnCallback(
			function (string $key) use (&$store): bool {
				unset($store[$key]);
				return true;
			}
		);
		$cache->method('hasKey')->willReturnCallback(
			fn(string $key): bool => isset($store[$key])
		);

		return $cache;
	}

	/**
	 * @param bool $loggedIn whether the service has a session user, i.e.
	 *                       whether it behaves like a browser request or like
	 *                       the background job
	 */
	private function makeService(array &$store, bool $loggedIn): UserService {
		$users = [];
		$accounts = [];

		foreach (self::PEOPLE as $uid => $fields) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$user->method('getDisplayName')->willReturn($fields['displayname']['value']);
			$users[$uid] = $user;

			$props = [];
			foreach ($fields as $name => $meta) {
				$prop = $this->createMock(IAccountProperty::class);
				$prop->method('getName')->willReturn($name);
				$prop->method('getValue')->willReturn($meta['value']);
				$prop->method('getScope')->willReturn($meta['scope']);
				$props[] = $prop;
			}

			$account = $this->createMock(IAccount::class);
			$account->method('getProperties')->willReturn($props);
			$accounts[$uid] = $account;
		}

		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->willReturnCallback(
			fn(string $uid): ?IUser => $users[$uid] ?? null
		);
		$userManager->method('callForAllUsers')->willReturnCallback(
			function (\Closure $callback) use ($users): void {
				foreach ($users as $user) {
					$callback($user);
				}
			}
		);

		$accountManager = $this->createMock(IAccountManager::class);
		$accountManager->method('getAccount')->willReturnCallback(
			fn(IUser $user): IAccount => $accounts[$user->getUID()]
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')->willReturn([]);
		$groupManager->method('get')->willReturn(null);

		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRouteAbsolute')->willReturn('https://example.org/avatar');

		$session = $this->createMock(IUserSession::class);
		if ($loggedIn) {
			$sessionUser = $this->createMock(IUser::class);
			$sessionUser->method('getUID')->willReturn('viewer');
			$session->method('getUser')->willReturn($sessionUser);
		} else {
			// Exactly what the background job sees: no session at all.
			$session->method('getUser')->willReturn(null);
		}

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('isAvailable')->willReturn(true);
		$cacheFactory->method('createDistributed')->willReturn($this->makeCache($store));

		return new UserService(
			$userManager,
			$groupManager,
			$accountManager,
			$urlGenerator,
			$this->createMock(LoggerInterface::class),
			new ProfileFilterMatcher(),
			null,
			null,
			null,
			null,
			$cacheFactory,
			$session,
			null
		);
	}

	/**
	 * Counts for one facet, key-sorted.
	 *
	 * Facet values come back ordered by count, so equal counts tie in an order
	 * this test has no business asserting on.
	 *
	 * @return array<string, int>
	 */
	private static function counts(array $result, string $field): array {
		foreach ($result['facets'] as $facet) {
			if ($facet['field'] === $field) {
				$counts = array_column($facet['values'], 'count', 'value');
				ksort($counts);
				return $counts;
			}
		}
		return [];
	}

	/**
	 * The regression itself: warm the cache as a logged-in viewer, run the
	 * sessionless job over it, then query again as that same viewer.
	 */
	public function testWarmupDoesNotStripLocalFieldsFromLoggedInCohort(): void {
		$store = [];
		$facets = ['role', 'organisation'];

		// A browser request populates the logged-in cohort.
		$viewer = $this->makeService($store, true);
		$before = $viewer->queryFaceted([], 'AND', [], $facets);
		$this->assertSame(
			['Adviseur' => 1, 'Manager' => 1],
			self::counts($before, 'role'),
			'precondition: a logged-in viewer sees local fields'
		);

		// The fresh entry expires; only the recipe and the stale copy remain.
		foreach (array_keys($store) as $key) {
			if (str_starts_with($key, 'cohort_v1_') && !str_ends_with($key, '_stale')) {
				unset($store[$key]);
			}
		}

		// The cron fires. No session, so currentAudience() is anonymous.
		$this->makeService($store, false)->warmCohorts();

		// The same viewer reloads the page.
		$after = $this->makeService($store, true)->queryFaceted([], 'AND', [], $facets);

		$this->assertSame(
			self::counts($before, 'role'),
			self::counts($after, 'role'),
			'a warmup run must not empty a logged-in viewer\'s filter panel'
		);
		$this->assertSame(
			self::counts($before, 'organisation'),
			self::counts($after, 'organisation')
		);
	}

	/**
	 * The other half of the same guarantee: honouring the recorded audience
	 * must not hand anonymous visitors fields they may not see. Without this,
	 * "fix the empty panel" could be satisfied by rebuilding everything as
	 * logged-in, which would leak Local fields onto public shares.
	 */
	public function testWarmupKeepsAnonymousCohortRestricted(): void {
		$store = [];
		$facets = ['role', 'organisation'];

		$anon = $this->makeService($store, false);
		$anon->queryFaceted([], 'AND', [], $facets);

		foreach (array_keys($store) as $key) {
			if (str_starts_with($key, 'cohort_v1_') && !str_ends_with($key, '_stale')) {
				unset($store[$key]);
			}
		}

		$this->makeService($store, false)->warmCohorts();

		$after = $this->makeService($store, false)->queryFaceted([], 'AND', [], $facets);

		$this->assertSame(
			[],
			self::counts($after, 'role'),
			'local fields must stay invisible to anonymous visitors after a warmup'
		);
		$this->assertSame([], self::counts($after, 'organisation'));
	}
}
