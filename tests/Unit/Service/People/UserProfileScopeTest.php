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
 * End-to-end proof that account-property scopes are honoured when a People
 * profile is assembled.
 *
 * AccountScopePolicyTest proves the rules; this proves they are actually
 * wired into buildUserProfile(), which is where the leak lived.
 */
class UserProfileScopeTest extends TestCase {
	private function makeProperty(string $name, string $value, string $scope): IAccountProperty {
		$prop = $this->createMock(IAccountProperty::class);
		$prop->method('getName')->willReturn($name);
		$prop->method('getValue')->willReturn($value);
		$prop->method('getScope')->willReturn($scope);

		return $prop;
	}

	/**
	 * @param array<string, array{0: string, 1: string}> $properties name => [value, scope]
	 */
	private function makeService(array $properties, bool $loggedIn): UserService {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('jdoe');
		$user->method('getDisplayName')->willReturn('Jan Doe');
		$user->method('getEMailAddress')->willReturn('leaked@example.org');

		$propObjects = [];
		foreach ($properties as $name => [$value, $scope]) {
			$propObjects[$name] = $this->makeProperty($name, $value, $scope);
		}

		$account = $this->createMock(IAccount::class);
		$account->method('getProperty')->willReturnCallback(
			function (string $name) use ($propObjects): IAccountProperty {
				if (!isset($propObjects[$name])) {
					throw new \RuntimeException('no such property: ' . $name);
				}
				return $propObjects[$name];
			}
		);
		$account->method('getProperties')->willReturn(array_values($propObjects));

		$accountManager = $this->createMock(IAccountManager::class);
		$accountManager->method('getAccount')->willReturn($account);

		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->willReturn($user);
		$userManager->method('searchDisplayName')->willReturn([$user]);
		$userManager->method('search')->willReturn([]);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')->willReturn(['staff']);

		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRouteAbsolute')->willReturn('https://example.org/avatar');

		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($loggedIn ? $user : null);

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

	/**
	 * @param array<string, array{0: string, 1: string}> $properties
	 */
	private function profileFor(array $properties, bool $loggedIn): array {
		$service = $this->makeService($properties, $loggedIn);
		$results = $service->searchUsers('Jan', 1);

		$this->assertNotEmpty($results, 'expected searchUsers to return the stubbed user');

		return $results[0];
	}

	public function testPrivatePropertyIsHiddenFromLoggedInViewer(): void {
		$profile = $this->profileFor([
			'phone' => ['0612345678', IAccountManager::SCOPE_PRIVATE],
			'role' => ['Manager', IAccountManager::SCOPE_LOCAL],
		], true);

		$this->assertArrayNotHasKey('phone', $profile, 'a private phone number must never be served');
		$this->assertSame('Manager', $profile['role'] ?? null);
	}

	public function testLocalPropertyIsHiddenFromAnonymousViewer(): void {
		$profile = $this->profileFor([
			'role' => ['Manager', IAccountManager::SCOPE_LOCAL],
			'organisation' => ['VoxCloud', IAccountManager::SCOPE_PUBLISHED],
		], false);

		$this->assertArrayNotHasKey('role', $profile, 'a local-scope role must not reach a share visitor');
		$this->assertSame('VoxCloud', $profile['organisation'] ?? null);
	}

	public function testFederatedAndPublishedReachAnonymousViewer(): void {
		$profile = $this->profileFor([
			'organisation' => ['VoxCloud', IAccountManager::SCOPE_FEDERATED],
			'website' => ['https://voxcloud.nl', IAccountManager::SCOPE_PUBLISHED],
		], false);

		$this->assertSame('VoxCloud', $profile['organisation'] ?? null);
		$this->assertSame('https://voxcloud.nl', $profile['website'] ?? null);
	}

	/**
	 * The email address used to be seeded straight from IUser::getEMailAddress(),
	 * which bypasses the account-property scope entirely. That is exactly the
	 * kind of side door this test exists to keep shut.
	 */
	public function testPrivateEmailIsNotLeakedViaTheUserObject(): void {
		$profile = $this->profileFor([
			'email' => ['jan@example.org', IAccountManager::SCOPE_PRIVATE],
		], true);

		$this->assertNull(
			$profile['email'] ?? null,
			'email must come from the scoped account property, never from IUser::getEMailAddress()'
		);
		$this->assertStringNotContainsString('leaked', json_encode($profile) ?: '');
	}

	public function testScopedEmailIsServedWhenVisible(): void {
		$profile = $this->profileFor([
			'email' => ['jan@example.org', IAccountManager::SCOPE_LOCAL],
		], true);

		$this->assertSame('jan@example.org', $profile['email'] ?? null);
	}

	/**
	 * LDAP/OIDC extras arrive through getProperties() and are never listed in
	 * STANDARD_PROPERTIES, so they were the widest part of the leak.
	 */
	public function testPrivateLdapExtraIsHidden(): void {
		$profile = $this->profileFor([
			'employeenumber' => ['00042', IAccountManager::SCOPE_PRIVATE],
			'gebouw' => ['Noord', IAccountManager::SCOPE_LOCAL],
		], true);

		$this->assertArrayNotHasKey('employeenumber', $profile);
		$this->assertSame('Noord', $profile['gebouw'] ?? null);
	}

	public function testIdentityFieldsSurviveForLoggedInViewer(): void {
		$profile = $this->profileFor([
			'role' => ['Manager', IAccountManager::SCOPE_LOCAL],
		], true);

		// A People widget without names would be useless; these are not gated.
		$this->assertSame('jdoe', $profile['uid']);
		$this->assertSame('Jan Doe', $profile['displayName']);
		$this->assertArrayHasKey('avatarUrl', $profile);
	}
}
