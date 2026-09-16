<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PermissionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * getPermissions() memoises its result per request, keyed by (userId, path).
 *
 * getPermissions() → calculatePermissions() → applyAclRules() reconstructs the
 * ACL decision from the raw group_folders_acl/filecache/storages tables with no
 * caching. filterNavigation() called it once per menu item, and the editor menu
 * is rendered twice, so a 40-item tree re-ran that whole raw-SQL pass ~80×. The
 * answer is a pure function of (userId, path) within a request, so it memoises.
 *
 * The key MUST include userId: the same path yields different permissions for
 * different users under GroupFolder ACLs, and serving one user's bits to another
 * would break the per-user ACL guarantee. These tests pin: repeat calls compute
 * once, different users compute independently, and the mutation hook clears it.
 *
 * A counting seam on calculatePermissions() (opened to protected for exactly
 * this, like resolveGroupFolderId for the fail-closed test) records how often the
 * uncached path actually runs.
 */
class PermissionResultMemoTest extends TestCase {

    /**
     * A PermissionService whose calculatePermissions() is replaced by a counter
     * returning a per-user canned bitmask, so the memo can be observed without
     * wiring the userManager/groupManager/db collaborators the real body needs.
     *
     * @param array<string,int> $bitsByUser userId => bitmask to return
     */
    private function svc(array $bitsByUser): PermissionService {
        $svc = new class($bitsByUser) extends PermissionService {
            public int $calcCalls = 0;
            /** @param array<string,int> $bitsByUser */
            public function __construct(private array $bitsByUser) {
            }
            protected function calculatePermissions(string $relativePath, string $userId): int {
                $this->calcCalls++;
                return $this->bitsByUser[$userId] ?? 0;
            }
        };
        (new \ReflectionProperty(PermissionService::class, 'logger'))
            ->setValue($svc, $this->createMock(LoggerInterface::class));
        return $svc;
    }

    public function testRepeatCallsForTheSameUserAndPathComputeOnce(): void {
        $svc = $this->svc(['alice' => 5]);

        $first = $svc->getPermissions('en/about', 'alice');
        $second = $svc->getPermissions('en/about', 'alice');

        $this->assertSame(5, $first);
        $this->assertSame(5, $second, 'the memoised answer is the same answer');
        $this->assertSame(1, $svc->calcCalls, 'the second call must hit the memo, not recompute');
    }

    public function testDifferentPathsComputeIndependently(): void {
        $svc = $this->svc(['alice' => 7]);

        $svc->getPermissions('en/about', 'alice');
        $svc->getPermissions('en/contact', 'alice');

        $this->assertSame(2, $svc->calcCalls, 'a different path is a different memo key');
    }

    public function testTheSamePathForDifferentUsersDoesNotLeak(): void {
        // alice may read+write (3), bob may only read (1) on the SAME path.
        $svc = $this->svc(['alice' => 3, 'bob' => 1]);

        $aliceBits = $svc->getPermissions('en/secret', 'alice');
        $bobBits = $svc->getPermissions('en/secret', 'bob');

        $this->assertSame(3, $aliceBits, "alice's own bits");
        $this->assertSame(1, $bobBits, "bob must NOT be served alice's cached bits");
        $this->assertSame(2, $svc->calcCalls, 'each user is computed independently (key includes userId)');
    }

    public function testExplicitUserIdOverridesTheInstanceUser(): void {
        // The instance user is 'alice'; an explicit uid arg must key on THAT.
        $svc = $this->svc(['alice' => 3, 'bob' => 1]);
        (new \ReflectionProperty(PermissionService::class, 'userId'))->setValue($svc, 'alice');

        $this->assertSame(1, $svc->getPermissions('en/x', 'bob'), 'explicit bob, not instance alice');
        $this->assertSame(3, $svc->getPermissions('en/x'), 'no arg falls back to instance alice');
        $this->assertSame(2, $svc->calcCalls, 'alice and bob are distinct keys even on one path');
    }

    public function testClearingTheCacheForcesAFreshComputation(): void {
        $svc = $this->svc(['alice' => 5]);

        $svc->getPermissions('en/about', 'alice');
        // The mutation hook (PageCacheInvalidator on create/update/delete) must
        // drop this memo alongside its siblings, or a stale ACL answer would
        // outlive the change that invalidated it.
        $svc->clearNodePermissionsCache();
        $svc->getPermissions('en/about', 'alice');

        $this->assertSame(2, $svc->calcCalls, 'after a clear the answer is recomputed');
    }

    public function testNoUserIdShortCircuitsWithoutComputing(): void {
        $svc = $this->svc(['alice' => 5]);

        $this->assertSame(0, $svc->getPermissions('en/about', ''), 'empty uid = no permissions');
        $this->assertSame(0, $svc->calcCalls, 'the no-user guard returns before calculatePermissions');
    }
}
