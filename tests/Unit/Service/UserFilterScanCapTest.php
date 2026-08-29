<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\UserService;
use OCA\IntraVox\Service\People\ProfileFilterMatcher;
use OCP\Accounts\IAccountManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The group-filter branch must respect the scan cap too. (US-1a)
 *
 * getUsersByFilters() has two branches. The all-users branch caps its scan at
 * MAX_FILTER_SCAN "to prevent OOM/timeout on large instances". The group-filter
 * branch, commented as "more efficient", had NO cap at all: it walked every
 * member of every requested group and built a full profile for each.
 *
 * Cheaper per user is not the same as bounded. Nothing stops a group from
 * containing every account on the instance, several such groups can be named in
 * one request, and the People widget is reachable from the anonymous share
 * endpoint — so one request could force a profile build for the entire
 * directory.
 */
class UserFilterScanCapTest extends TestCase {

	private const CAP = 5000;

	private function user(string $uid): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$user->method('getDisplayName')->willReturn($uid);

		return $user;
	}

	/**
	 * A group of $count members. IGroup::getUsers() returns an array, so the cap
	 * cannot stop the members being LISTED — what it bounds is the expensive
	 * work per member. buildUserProfile() calls getDisplayName(), an avatar URL
	 * lookup, a groups query and a status query for each user, so counting
	 * getDisplayName() calls measures exactly what the cap is there to limit.
	 */
	private function group(int $count, ?int &$touched): IGroup {
		$users = [];
		for ($i = 0; $i < $count; $i++) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('user' . $i);
			$user->method('getDisplayName')->willReturnCallback(function () use ($i, &$touched) {
				$touched++;

				return 'user' . $i;
			});
			$users[] = $user;
		}

		$group = $this->createMock(IGroup::class);
		$group->method('getUsers')->willReturn($users);

		return $group;
	}

	private function service(IGroupManager $groupManager): UserService {
		$accountManager = $this->createMock(IAccountManager::class);
		$accountManager->method('getAccount')->willThrowException(new \RuntimeException('no account'));

		// buildUserProfile() resolves each uid back to an IUser for its groups
		// lookup; without this it hands null to getUserGroupIds().
		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->willReturnCallback(fn (string $uid) => $this->user($uid));

		$groupManager->method('getUserGroupIds')->willReturn([]);

		return new UserService(
			$userManager,
			$groupManager,
			$accountManager,
			$this->createMock(IURLGenerator::class),
			$this->createMock(LoggerInterface::class),
			new ProfileFilterMatcher(),
		);
	}

	/**
	 * The regression. On the pre-fix code every one of the 6000 members is
	 * walked; the cap stops it at MAX_FILTER_SCAN.
	 */
	public function testGroupBranchStopsAtTheScanCap(): void {
		$touched = 0;
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('get')->willReturn($this->group(self::CAP + 1000, $touched));

		$this->service($groupManager)->getUsersByFilters(
			[['fieldName' => 'group', 'value' => 'everyone']],
		);

		$this->assertLessThanOrEqual(
			self::CAP,
			$touched,
			'the group branch must not walk more than MAX_FILTER_SCAN members'
		);
		$this->assertGreaterThan(0, $touched, 'it must still walk the group at all');
	}

	/** Several large groups in one request must not multiply the work. */
	public function testCapAppliesAcrossAllRequestedGroups(): void {
		$touched = 0;
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('get')->willReturnCallback(
			fn () => $this->group(4000, $touched)
		);

		$this->service($groupManager)->getUsersByFilters(
			[['fieldName' => 'group', 'values' => ['a', 'b', 'c', 'd']]],
		);

		$this->assertLessThanOrEqual(
			self::CAP,
			$touched,
			'the cap is a total, not per group: 4 x 4000 members must not all be walked'
		);
	}

	/** A normal-sized group is unaffected — every member still returned. */
	public function testSmallGroupIsFullyProcessed(): void {
		$touched = 0;
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('get')->willReturn($this->group(25, $touched));

		$result = $this->service($groupManager)->getUsersByFilters(
			[['fieldName' => 'group', 'value' => 'team']],
			'AND',
			100,
		);

		$this->assertSame(25, $touched);
		$this->assertCount(25, $result);
	}

	/** An unknown group is skipped without consuming the budget. */
	public function testUnknownGroupIsSkipped(): void {
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('get')->willReturn(null);

		$result = $this->service($groupManager)->getUsersByFilters(
			[['fieldName' => 'group', 'value' => 'does-not-exist']],
		);

		$this->assertSame([], $result);
	}
}
