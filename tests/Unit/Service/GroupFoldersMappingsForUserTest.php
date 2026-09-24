<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\GroupFolders\GroupFoldersGateway;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * mappingsForUser() keeps the mapping TYPE alongside the id.
 *
 * mappingIdsForUser() returns a flat id list, which is all the SQL needs to
 * decide WHICH acl rows apply. It is not enough to decide how to COMBINE them:
 * rules are folded per (type, id), and a circle id and a group id are two
 * separate id spaces that may collide. These cases pin the fallback path, which
 * is the one reachable without a Nextcloud container — the container path is
 * covered by scripts/acl-mapping-matrix.php against a real instance.
 */
class GroupFoldersMappingsForUserTest extends TestCase {

    private function gateway(bool $groupfoldersEnabled): GroupFoldersGateway {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('isEnabledForUser')->willReturn($groupfoldersEnabled);

        return new GroupFoldersGateway($appManager, $this->createMock(LoggerInterface::class));
    }

    private function user(): \OCP\IUser {
        $user = $this->createMock(\OCP\IUser::class);
        $user->method('getUID')->willReturn('alice');

        return $user;
    }

    /**
     * Without groupfolders there is no mapping manager to ask, so the caller's
     * group ids stand in — and they are groups, so they must be typed as such.
     * Typing them 'user' or leaving the type off would merge every group into
     * one accumulator and defeat the per-mapping fold.
     */
    public function testFallbackGroupsAreTypedAsGroups(): void {
        $mappings = $this->gateway(false)->mappingsForUser($this->user(), ['G-A', 'G-B']);

        $this->assertSame([
            ['type' => 'group', 'id' => 'G-A'],
            ['type' => 'group', 'id' => 'G-B'],
        ], $mappings);
    }

    public function testFallbackWithNoGroupsIsEmpty(): void {
        $this->assertSame([], $this->gateway(false)->mappingsForUser($this->user(), []));
    }

    /**
     * The flat id list stays the contract the SQL `IN` wants: ids only, and no
     * duplicates — a user can hold the same id as both a group and a circle,
     * and binding it twice would only widen the query for nothing.
     */
    public function testIdListIsFlatAndDeduplicated(): void {
        $ids = $this->gateway(false)->mappingsForUser($this->user(), ['G-A', 'G-A', 'G-B']);

        $this->assertSame(
            ['G-A', 'G-B'],
            array_values(array_unique(array_column($ids, 'id')))
        );
    }

    public function testMappingIdsForUserReturnsPlainIds(): void {
        $this->assertSame(
            ['G-A', 'G-B'],
            $this->gateway(false)->mappingIdsForUser($this->user(), ['G-A', 'G-B', 'G-A'])
        );
    }
}
