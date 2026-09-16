<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Integration;

use OCA\IntraVox\Service\PermissionService;
use OCP\IGroupManager;
use OCP\IUserManager;

/**
 * Issue #112: two users in the same groups must not share a cache entry once
 * Advanced Permissions are on.
 *
 * The page tree and the news widget are cached under a key built from the
 * user's GROUP membership, which is right and cheap while everyone in a group
 * sees the same thing. Advanced Permissions break that assumption: an ACL can
 * hide a subtree from one member and not from another, so a group-keyed entry
 * serves the first user's tree to the second.
 *
 * Nothing about that failure is loud. No error, no log line, no visual cue —
 * the second user simply sees pages they have no rights to, until the entry
 * expires. It is the kind of defect that is only ever found by looking for it.
 *
 * getCacheDiscriminator() is the fix: it appends a per-user fragment to the key
 * exactly when the folder has ACLs enabled, and returns '' otherwise so
 * installations that never turned them on keep the cheap shared key.
 *
 * This test exists because that fix travelled. It was written on main inside
 * PageService, and the PageService split deleted that class — so the fix had to
 * be re-applied to PageTreeService and NewsWidgetService by hand during the 3.0
 * merge. Hand-carried security fixes are exactly the ones that get dropped, and
 * a unit test cannot catch it: the discriminator's whole job is to ask a real
 * groupfolders backend whether real ACLs are on.
 */
class AclCacheDiscriminatorTest extends IntegrationTestCase {

    private const SECOND_USER_SUFFIX = '-user2';

    private static ?string $secondUserId = null;

    /**
     * getCacheDiscriminator() resolves the groupfolder called 'IntraVox' by
     * name — it is not parameterised — so the suite's own throwaway folder
     * cannot stand in here. These tests therefore toggle Advanced Permissions
     * on the REAL IntraVox folder and put the original setting back in a
     * finally block. They only flip the ACL flag; no rule is ever written, and
     * no content is read or changed.
     */
    private static ?int $intraVoxFolderId = null;
    private static ?bool $aclWasEnabled = null;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        // A second member of the SAME group. Same group hash, different user:
        // the precise situation where a group-keyed cache leaks.
        $userManager = self::server()->get(IUserManager::class);
        $groupManager = self::server()->get(IGroupManager::class);

        self::$secondUserId = self::$mountPoint . self::SECOND_USER_SUFFIX;
        if (!$userManager->userExists(self::$secondUserId)) {
            $userManager->createUser(self::$secondUserId, bin2hex(random_bytes(16)));
        }
        $groupManager->get(self::$groupId)?->addUser($userManager->get(self::$secondUserId));

        foreach (self::folderManager()->getAllFolders() as $id => $folder) {
            $mountPoint = is_array($folder) ? ($folder['mount_point'] ?? '') : ($folder->mountPoint ?? '');
            if ($mountPoint === 'IntraVox') {
                self::$intraVoxFolderId = (int)$id;
                self::$aclWasEnabled = (bool)(is_array($folder) ? ($folder['acl'] ?? false) : ($folder->acl ?? false));
                break;
            }
        }
    }

    public static function tearDownAfterClass(): void {
        // Belt and braces: each test restores the flag itself, but a fatal
        // between the toggle and the finally would otherwise leave Advanced
        // Permissions switched on for a real folder.
        if (self::$intraVoxFolderId !== null && self::$aclWasEnabled !== null) {
            self::folderManager()->setFolderACL(self::$intraVoxFolderId, self::$aclWasEnabled);
        }
        if (self::$secondUserId !== null) {
            self::server()->get(IUserManager::class)->get(self::$secondUserId)?->delete();
            self::$secondUserId = null;
        }
        parent::tearDownAfterClass();
    }

    /**
     * A fresh PermissionService per call: the discriminator is memoised per
     * request, which is correct in production and would hide the difference
     * here if the same instance answered for both users.
     */
    private function discriminatorFor(string $userId): string {
        return $this->actingAs($userId, function () use ($userId): string {
            $container = self::server();

            // Built through the container so this exercises the real wiring,
            // but with the uid passed explicitly: the registered factory reads
            // the session when the container builds the service, and within one
            // phpunit process that value is whatever the first call cached.
            $service = new PermissionService(
                $container->get(\OCP\Files\IRootFolder::class),
                $container->get(\OCP\IUserSession::class),
                $container->get(\OCP\IGroupManager::class),
                $container->get(\OCA\IntraVox\Service\SetupService::class),
                $container->get(\OCP\IConfig::class),
                $container->get(\Psr\Log\LoggerInterface::class),
                $container->get(\OCP\ICacheFactory::class),
                $container->get(\OCP\IUserManager::class),
                $container->get(\OCP\IDBConnection::class),
                $container->get(\OCP\App\IAppManager::class),
                $userId,
                $container->get(\OCA\IntraVox\Service\GroupFolders\GroupFoldersGateway::class),
            );

            return $service->getCacheDiscriminator();
        });
    }

    private function requireIntraVoxFolder(): int {
        if (self::$intraVoxFolderId === null) {
            self::markTestSkipped('no groupfolder named IntraVox on this instance');
        }
        return self::$intraVoxFolderId;
    }

    /** With ACLs off, the shared group key is correct — and stays cheap. */
    public function testDiscriminatorIsEmptyWhileAclsAreOff(): void {
        self::folderManager()->setFolderACL($this->requireIntraVoxFolder(), false);

        self::assertSame(
            '',
            $this->discriminatorFor(self::$userId),
            'without Advanced Permissions every member of a group sees the same '
            . 'tree, so the key must stay group-scoped rather than fragmenting '
            . 'the cache per user for nothing'
        );
    }

    /**
     * The actual #112 assertion: ACLs on, two members of one group, two keys.
     *
     * Equal discriminators here mean the two users share a cache entry, which
     * is the leak itself — not a precursor to it.
     */
    public function testAclsOnGiveTwoGroupMatesDifferentCacheKeys(): void {
        $folderId = $this->requireIntraVoxFolder();
        self::folderManager()->setFolderACL($folderId, true);

        try {
            $first = $this->discriminatorFor(self::$userId);
            $second = $this->discriminatorFor(self::$secondUserId);

            self::assertNotSame(
                '',
                $first,
                'with ACLs enabled the key must carry a per-user fragment; an '
                . 'empty discriminator means the tree cache is still group-keyed'
            );
            self::assertNotSame(
                $first,
                $second,
                'two members of the same group produced the SAME cache key with '
                . 'ACLs on — one user is being served the other\'s cached page '
                . 'tree (issue #112)'
            );
        } finally {
            self::folderManager()->setFolderACL($folderId, self::$aclWasEnabled ?? false);
        }
    }

    /**
     * The fragment must not be the raw uid.
     *
     * Cache keys travel — into Redis, into logs, into support tickets — so the
     * discriminator is a truncated hash rather than a username.
     */
    public function testDiscriminatorDoesNotLeakTheRawUsername(): void {
        $folderId = $this->requireIntraVoxFolder();
        self::folderManager()->setFolderACL($folderId, true);

        try {
            $discriminator = $this->discriminatorFor(self::$userId);

            self::assertStringNotContainsString(
                self::$userId,
                $discriminator,
                'the cache key must not carry the raw uid'
            );
            self::assertMatchesRegularExpression(
                '/^_u[0-9a-f]{12}$/',
                $discriminator,
                'expected the short stable hash form'
            );
        } finally {
            self::folderManager()->setFolderACL($folderId, self::$aclWasEnabled ?? false);
        }
    }

    /**
     * The call sites, not just the helper.
     *
     * The three tests above prove getCacheDiscriminator() answers correctly.
     * They do NOT prove anyone asks it — and "anyone asks it" is the part that
     * broke: the fix lived in PageService, the split deleted that class, and
     * the two keys had to be re-applied by hand to the services that inherited
     * them. Drop either call and every test above stays green while the leak is
     * wide open.
     *
     * So this reads the source of the two services that build the keys. Static,
     * blunt, and deliberately so: the alternative is priming a real cache as one
     * user and reading it back as another, which needs two users with genuinely
     * divergent ACL rules on real content — far more setup, and far more ways to
     * pass for the wrong reason.
     */
    public function testBothCacheKeysActuallyAskForTheDiscriminator(): void {
        $appDir = dirname(__DIR__, 2);

        $callSites = [
            'lib/Service/Tree/PageTreeService.php' => 'the page tree',
            'lib/Service/News/NewsWidgetService.php' => 'the news widget',
        ];

        foreach ($callSites as $relative => $what) {
            $path = $appDir . '/' . $relative;
            self::assertFileExists($path, "expected {$relative} to exist");

            // Strip comments first. Both files EXPLAIN the discriminator in a
            // docblock, so a plain substring match stays green when the actual
            // call is deleted and only the explanation survives — which is
            // exactly what a careless revert leaves behind.
            $code = token_get_all((string)file_get_contents($path));
            $withoutComments = '';
            foreach ($code as $token) {
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $withoutComments .= is_array($token) ? $token[1] : $token;
            }

            self::assertStringContainsString(
                'getCacheDiscriminator()',
                $withoutComments,
                "{$what} cache key no longer carries the ACL discriminator (#112): "
                . "with Advanced Permissions on, two users in the same groups will "
                . "share a cache entry and one gets served the other's content"
            );
        }
    }
}
