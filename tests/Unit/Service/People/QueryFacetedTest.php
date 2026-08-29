<?php

declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\People;

use OCA\IntraVox\Service\UserService;
use OCA\IntraVox\Service\People\ProfileFilterMatcher;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Proves the narrowing invariant survives the real service wiring, not just
 * the pure calculator: editor filters build the cohort, viewer refinements
 * operate strictly inside it.
 */
class QueryFacetedTest extends TestCase {
	/** @var array<string, array<string, string>> uid => field => value */
	private const PEOPLE = [
		'u1' => ['displayname' => 'Anne Vries', 'role' => 'Manager', 'gebouw' => 'Noord'],
		'u2' => ['displayname' => 'Bo Jansen', 'role' => 'Manager', 'gebouw' => 'Noord'],
		'u3' => ['displayname' => 'Cem Yilmaz', 'role' => 'Manager', 'gebouw' => 'Zuid'],
		'u4' => ['displayname' => 'Dana Peters', 'role' => 'Adviseur', 'gebouw' => 'Noord'],
		'u5' => ['displayname' => 'Eva Bakker', 'role' => 'Adviseur', 'gebouw' => 'Zuid'],
		'u6' => ['displayname' => 'Finn de Boer', 'role' => 'Stagiair', 'gebouw' => 'Oost'],
	];

	private function makeService(): UserService {
		$users = [];
		$accounts = [];

		foreach (self::PEOPLE as $uid => $fields) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$user->method('getDisplayName')->willReturn($fields['displayname']);
			$user->method('getEMailAddress')->willReturn($uid . '@example.org');
			$users[$uid] = $user;

			$props = [];
			foreach ($fields as $name => $value) {
				$prop = $this->createMock(IAccountProperty::class);
				$prop->method('getName')->willReturn($name);
				$prop->method('getValue')->willReturn($value);
				$prop->method('getScope')->willReturn(IAccountManager::SCOPE_LOCAL);
				$props[] = $prop;
			}

			$account = $this->createMock(IAccount::class);
			$account->method('getProperties')->willReturn($props);
			$account->method('getProperty')->willReturnCallback(
				function (string $name) use ($props): IAccountProperty {
					foreach ($props as $prop) {
						if ($prop->getName() === $name) {
							return $prop;
						}
					}
					throw new \RuntimeException('no such property');
				}
			);
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
			function (IUser $user) use ($accounts): IAccount {
				return $accounts[$user->getUID()];
			}
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')->willReturn([]);
		$groupManager->method('get')->willReturn(null);

		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRouteAbsolute')->willReturn('https://example.org/avatar');

		$sessionUser = $this->createMock(IUser::class);
		$sessionUser->method('getUID')->willReturn('viewer');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($sessionUser);

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
			null,
			$session,
			null
		);
	}

	private static function refine(string $field, array $values): array {
		return ['field' => $field, 'op' => 'in', 'value' => $values];
	}

	/** @return array<string, int> */
	private static function counts(array $result, string $field): array {
		foreach ($result['facets'] as $facet) {
			if ($facet['field'] === $field) {
				return array_column($facet['values'], 'count', 'value');
			}
		}
		return [];
	}

	public function testUnrefinedFacetsCoverTheWholeCohort(): void {
		$result = $this->makeService()->queryFaceted([], 'AND', [], ['role', 'gebouw']);

		$this->assertSame(6, $result['total']);
		$this->assertSame(['Manager' => 3, 'Adviseur' => 2, 'Stagiair' => 1], self::counts($result, 'role'));
	}

	public function testRefinementNarrowsTotal(): void {
		$service = $this->makeService();

		$baseline = $service->queryFaceted([], 'AND', [], ['role']);
		$refined = $service->queryFaceted([], 'AND', [self::refine('role', ['Manager'])], ['role']);

		$this->assertSame(6, $baseline['total']);
		$this->assertSame(3, $refined['total']);
		$this->assertLessThanOrEqual($baseline['total'], $refined['total']);
	}

	/**
	 * The invariant, through the full service: a viewer refinement on a field
	 * the editor already constrains must intersect, never replace.
	 */
	public function testViewerCannotEscapeEditorFilter(): void {
		$editorFilters = [['fieldName' => 'gebouw', 'operator' => 'equals', 'value' => 'Noord']];

		$result = $this->makeService()->queryFaceted(
			$editorFilters,
			'AND',
			[self::refine('gebouw', ['Zuid'])],
			['role']
		);

		$this->assertSame(0, $result['total'], 'viewer reached outside the editor filter');
		$this->assertSame([], $result['users']);
	}

	public function testEditorFilterBoundsTheUnrefinedTotal(): void {
		$editorFilters = [['fieldName' => 'gebouw', 'operator' => 'equals', 'value' => 'Noord']];

		$result = $this->makeService()->queryFaceted($editorFilters, 'AND', [], ['role']);

		// u1, u2, u4 are in Noord.
		$this->assertSame(3, $result['total']);
	}

	/**
	 * A facet on a field the editor already filters would only ever offer
	 * dead options, so it must not be returned at all.
	 */
	public function testFacetOnEditorFilteredFieldIsDropped(): void {
		$editorFilters = [['fieldName' => 'gebouw', 'operator' => 'equals', 'value' => 'Noord']];

		$result = $this->makeService()->queryFaceted($editorFilters, 'AND', [], ['gebouw', 'role']);

		$fields = array_column($result['facets'], 'field');
		$this->assertNotContains('gebouw', $fields);
		$this->assertContains('role', $fields);
	}

	public function testDisjunctiveCountingSurvivesTheServiceLayer(): void {
		$result = $this->makeService()->queryFaceted(
			[],
			'AND',
			[self::refine('role', ['Manager'])],
			['role', 'gebouw']
		);

		// gebouw narrows to managers: Noord x2, Zuid x1.
		$this->assertSame(['Noord' => 2, 'Zuid' => 1], self::counts($result, 'gebouw'));

		// role keeps its unrefined counts so the user can switch or add.
		$this->assertSame(['Manager' => 3, 'Adviseur' => 2, 'Stagiair' => 1], self::counts($result, 'role'));
	}

	public function testAdvertisedCountMatchesActualTotal(): void {
		$service = $this->makeService();
		$result = $service->queryFaceted([], 'AND', [], ['role']);

		foreach ($result['facets'][0]['values'] as $entry) {
			$clicked = $service->queryFaceted([], 'AND', [self::refine('role', [$entry['value']])], ['role']);

			$this->assertSame(
				$entry['count'],
				$clicked['total'],
				'role=' . $entry['value'] . ' advertised ' . $entry['count']
			);
		}
	}

	public function testFreeTextNarrowsAndCannotEscapeEditorFilter(): void {
		$editorFilters = [['fieldName' => 'gebouw', 'operator' => 'equals', 'value' => 'Noord']];

		// "Cem" is in Zuid, so an editor filter on Noord must exclude them
		// no matter what the viewer types.
		$result = $this->makeService()->queryFaceted(
			$editorFilters,
			'AND',
			[],
			['role'],
			'Cem',
			['displayName']
		);

		$this->assertSame(0, $result['total']);
	}

	public function testFreeTextMatchesDisplayName(): void {
		$result = $this->makeService()->queryFaceted([], 'AND', [], [], 'jansen', ['displayName']);

		$this->assertSame(1, $result['total']);
		$this->assertSame('u2', $result['users'][0]['uid'] ?? null);
	}

	public function testPaginationReportsHasMore(): void {
		$service = $this->makeService();

		$first = $service->queryFaceted([], 'AND', [], [], '', [], 2, 0);
		$last = $service->queryFaceted([], 'AND', [], [], '', [], 2, 4);

		$this->assertCount(2, $first['users']);
		$this->assertTrue($first['hasMore']);
		$this->assertFalse($last['hasMore']);
	}

	/**
	 * The result set must not depend on which facets were asked for.
	 *
	 * Found on live data: the cohort snapshot only loaded the fields needed
	 * for facets, search, editor filters and sorting — not the fields the
	 * viewer was actually refining on. Refining on `organisation` while
	 * requesting facets for `role` therefore matched nothing, because
	 * `organisation` was never read into the snapshot.
	 *
	 * This matters beyond the obvious: a deep link restores refinements from
	 * the URL before any facet is rendered, so the broken case is the normal
	 * case for a shared link.
	 */
	public function testResultIsIndependentOfWhichFacetsAreRequested(): void {
		$service = $this->makeService();
		$refine = [self::refine('role', ['Manager'])];

		$withOwnFacet = $service->queryFaceted([], 'AND', $refine, ['role', 'gebouw']);
		$withOtherFacet = $service->queryFaceted([], 'AND', $refine, ['gebouw']);
		$withNoFacets = $service->queryFaceted([], 'AND', $refine, []);

		$this->assertSame(3, $withOwnFacet['total']);
		$this->assertSame(
			$withOwnFacet['total'],
			$withOtherFacet['total'],
			'refining on a field that is not itself a facet must still filter'
		);
		$this->assertSame(
			$withOwnFacet['total'],
			$withNoFacets['total'],
			'refining with no facets requested at all must still filter'
		);
	}

	/**
	 * The deep-link case: refinements arrive from the URL, facets are asked
	 * for separately, and the two lists need not overlap.
	 */
	public function testRefinementOnNonFacetedFieldStillNarrows(): void {
		$service = $this->makeService();

		$all = $service->queryFaceted([], 'AND', [], ['role']);
		$refined = $service->queryFaceted([], 'AND', [self::refine('gebouw', ['Noord'])], ['role']);

		$this->assertSame(6, $all['total']);
		$this->assertSame(3, $refined['total'], 'gebouw refinement ignored when gebouw is not a facet');
	}

	public function testMetaReportsExactCountsForSmallInstance(): void {
		$result = $this->makeService()->queryFaceted([], 'AND', [], ['role']);

		$this->assertFalse($result['meta']['approximate']);
		$this->assertSame(6, $result['meta']['scanned']);
	}
}
