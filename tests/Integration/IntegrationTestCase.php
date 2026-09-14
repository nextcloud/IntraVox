<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Integration;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\SetupService;
use OCA\GroupFolders\Folder\FolderManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that run against a REAL Nextcloud with a REAL
 * groupfolders app — no stubs, no mocks.
 *
 * Why these exist at all: IntraVox resolves its content folder through three
 * fallback layers (mounted member view, then the raw __groupfolders walk, with
 * a name match in between), and every one of those layers is there because of
 * an actual production breakage. Unit tests cannot see any of it — they stub
 * OCP away. So the code path that decides *where the intranet lives* had zero
 * automated coverage until this suite.
 *
 * Isolation: each test class creates its OWN throwaway groupfolder and removes
 * it again in tearDownAfterClass. Nothing here reads or writes the real
 * IntraVox content, so the suite is safe to run repeatedly, and a crash leaves
 * at worst one stray folder named with the TEST_PREFIX below.
 */
abstract class IntegrationTestCase extends TestCase {
    /**
     * Mount point prefix for every folder this suite creates. Anything with
     * this prefix is disposable by definition; cleanUpStrayFolders() removes
     * leftovers from a previous crashed run.
     */
    protected const TEST_PREFIX = 'IntraVoxITest';

    protected static ?int $folderId = null;
    protected static string $mountPoint = '';
    protected static ?string $groupId = null;
    protected static ?string $userId = null;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        if (!self::isNextcloudBootstrapped()) {
            // Loudly, not silently: running this suite from the default
            // phpunit.xml loads the OCP *stubs*, so every assertion here would
            // be meaningless. Better to stop than to report a green run that
            // proved nothing.
            self::markTestSkippedStatic(
                'Integration tests need a real Nextcloud, but the OCP stubs are loaded. '
                . 'Run them with: scripts/run-integration-tests.sh '
                . '(they execute inside the nc-dev container against real groupfolders).'
            );
        }

        self::cleanUpStrayFolders();
        self::createTestFolder();
    }

    public static function tearDownAfterClass(): void {
        self::destroyTestFolder();
        parent::tearDownAfterClass();
    }

    protected static function isNextcloudBootstrapped(): bool {
        return class_exists(\OC::class, false) && \OC::$server !== null;
    }

    /**
     * markTestSkipped() is not static in PHPUnit 10, but setUpBeforeClass is.
     */
    protected static function markTestSkippedStatic(string $message): void {
        throw new \PHPUnit\Framework\SkippedTestSuiteError($message);
    }

    protected static function server(): \Psr\Container\ContainerInterface {
        return \OC::$server;
    }

    protected static function folderManager(): FolderManager {
        return self::server()->get(FolderManager::class);
    }

    protected function pageService(): PageService {
        return self::server()->get(PageService::class);
    }

    /**
     * The single-page read lives on PageReadService (fase-6 Track 3b retired
     * PageService::getPage). Resolve it from the container exactly as production
     * consumers do.
     */
    protected function pageReadService(): \OCA\IntraVox\Service\Read\PageReadService {
        return self::server()->get(\OCA\IntraVox\Service\Read\PageReadService::class);
    }

    protected function permissionService(): PermissionService {
        return self::server()->get(PermissionService::class);
    }

    protected function setupService(): SetupService {
        return self::server()->get(SetupService::class);
    }

    protected function rootFolder(): IRootFolder {
        return self::server()->get(IRootFolder::class);
    }

    /**
     * Create the throwaway groupfolder, a group, and a member user, then wire
     * them together the way a real IntraVox install is wired.
     */
    protected static function createTestFolder(): void {
        $fm = self::folderManager();

        self::$mountPoint = self::TEST_PREFIX . '-' . bin2hex(random_bytes(4));
        self::$folderId = $fm->createFolder(self::$mountPoint);

        $groupManager = self::server()->get(IGroupManager::class);
        $userManager = self::server()->get(IUserManager::class);

        self::$groupId = self::$mountPoint . '-group';
        if (!$groupManager->groupExists(self::$groupId)) {
            $groupManager->createGroup(self::$groupId);
        }

        self::$userId = self::$mountPoint . '-user';
        if (!$userManager->userExists(self::$userId)) {
            $userManager->createUser(self::$userId, bin2hex(random_bytes(16)));
        }
        $groupManager->get(self::$groupId)?->addUser($userManager->get(self::$userId));

        // 31 = all permissions, matching what IntraVox Admins get.
        $fm->addApplicableGroup(self::$folderId, self::$groupId);
        $fm->setFolderACL(self::$folderId, false);
    }

    protected static function destroyTestFolder(): void {
        if (self::$folderId !== null) {
            try {
                self::folderManager()->removeFolder(self::$folderId);
            } catch (\Throwable $e) {
                // Best effort: a stray folder is picked up by the next run.
            }
            self::$folderId = null;
        }

        $groupManager = self::server()->get(IGroupManager::class);
        $userManager = self::server()->get(IUserManager::class);

        if (self::$userId !== null) {
            $userManager->get(self::$userId)?->delete();
            self::$userId = null;
        }
        if (self::$groupId !== null) {
            $groupManager->get(self::$groupId)?->delete();
            self::$groupId = null;
        }
    }

    /**
     * Remove folders/groups/users left behind by a crashed previous run, so the
     * suite is repeatable without manual cleanup.
     */
    protected static function cleanUpStrayFolders(): void {
        $fm = self::folderManager();
        foreach ($fm->getAllFolders() as $id => $folder) {
            $mountPoint = is_object($folder)
                ? (property_exists($folder, 'mountPoint') ? $folder->mountPoint : null)
                : ($folder['mount_point'] ?? null);
            if (is_string($mountPoint) && str_starts_with($mountPoint, self::TEST_PREFIX)) {
                try {
                    $fm->removeFolder((int)$id);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        $groupManager = self::server()->get(IGroupManager::class);
        foreach ($groupManager->search(self::TEST_PREFIX) as $group) {
            $group->delete();
        }

        $userManager = self::server()->get(IUserManager::class);
        foreach ($userManager->search(self::TEST_PREFIX) as $user) {
            $user->delete();
        }
    }

    /**
     * The `files` folder inside the throwaway groupfolder, resolved through the
     * member's mounted view — the same route the runtime uses.
     */
    protected function testGroupFolder(): Folder {
        $userFolder = $this->rootFolder()->getUserFolder(self::$userId);
        $node = $userFolder->get(self::$mountPoint);
        self::assertInstanceOf(Folder::class, $node, 'the test groupfolder must be mounted for its member');
        return $node;
    }

    /**
     * Run $fn as $userId, with the filesystem set up for that user, then
     * restore the previous session. Permission and folder resolution both read
     * the session, so tests that assert on them must run inside one.
     */
    protected function actingAs(string $userId, callable $fn) {
        $userSession = self::server()->get(IUserSession::class);
        $userManager = self::server()->get(IUserManager::class);
        $previous = $userSession->getUser();

        $user = $userManager->get($userId);
        self::assertNotNull($user, "user {$userId} must exist");

        $userSession->setUser($user);
        \OC_Util::setupFS($userId);
        try {
            return $fn();
        } finally {
            $userSession->setUser($previous);
            if ($previous !== null) {
                \OC_Util::setupFS($previous->getUID());
            }
        }
    }
}
