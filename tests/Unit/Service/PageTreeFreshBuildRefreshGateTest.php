<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Tree\PageTreeService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsCollaboratorFixtures;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The BLOCKING correctness gate for the fresh-build perf fix (perf-hunt Batch A,
 * Fix #3): getPageTree() now SKIPS refreshTreePermissions() on the fresh-build
 * path, because the build already resolved every node's permissions for THIS user
 * from the live filesystem view. The VM measurement showed 0 divergences between
 * the two passes for both a full-access and an ACL-denied user; this file is the
 * permanent unit pin so any future regression that makes the build's per-node
 * perms diverge from the per-user refresh fails CI instead of silently serving
 * stale build-time permissions.
 *
 * It drives a REAL PageTreeService + REAL PageTreeBuilder over a fixture language
 * mount (via fakeFolderContext's intraVoxOverride), with a PermissionService
 * double that models per-user GroupFolder ACLs: permissionsFromNode(node) and
 * getFolderPermissions(path) return the SAME per-(user,path) decision — which is
 * exactly the invariant the skip relies on. Two users are exercised:
 *   - admin: reads every node;
 *   - restricted: denied on 'en/secret' (models Piet's -read on en/departments).
 *
 * The gate: the served fresh-build tree's per-node {canRead,...} is byte-identical
 * to the per-user ACL decision — the same result the retired second pass produced.
 */
class PageTreeFreshBuildRefreshGateTest extends TestCase {

    use BuildsCollaboratorFixtures;

    /** A page JSON file for {name}/{name}.json. */
    private function pageJson(string $path, string $uid, string $title): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode(['uniqueId' => $uid, 'title' => $title]));
        $file->method('getId')->willReturn(abs(crc32($path)));
        $file->method('getMTime')->willReturn(1000);
        $file->method('isReadable')->willReturn(true);
        return $file;
    }

    /** A subpage folder en/{name} holding {name}.json, at IntraVox-relative path en/{name}. */
    private function subpage(string $name, string $uid): Folder {
        $rel = 'en/' . $name;
        $abs = '/IntraVox/' . $rel;
        $json = $this->pageJson($abs . '/' . $name . '.json', $uid, ucfirst($name));
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn($name);
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($abs);
        $folder->method('getDirectoryListing')->willReturn([$json]);
        $folder->method('nodeExists')->willReturnCallback(fn($n) => $n === $name . '.json');
        $folder->method('get')->willReturnCallback(function ($p) use ($json, $name, $abs) {
            if ($p === $name . '.json') {
                return $json;
            }
            throw new NotFoundException($abs . '/' . $p);
        });
        return $folder;
    }

    /**
     * The en language mount: a loose home.json plus two subpages (about, secret).
     * Its own getPath is /IntraVox/en; relativePathFromRoot derives en/about etc.
     */
    private function enMount(): Folder {
        $home = $this->pageJson('/IntraVox/en/home.json', 'page-home', 'Home');
        $about = $this->subpage('about', 'page-about');
        $secret = $this->subpage('secret', 'page-secret');
        $children = ['home.json' => $home, 'about' => $about, 'secret' => $secret];

        $en = $this->createMock(Folder::class);
        $en->method('getName')->willReturn('en');
        $en->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $en->method('getPath')->willReturn('/IntraVox/en');
        $en->method('getDirectoryListing')->willReturn([$home, $about, $secret]);
        $en->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $en->method('get')->willReturnCallback(function ($p) use ($children) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new NotFoundException('/IntraVox/en/' . $p);
        });
        return $en;
    }

    /**
     * A PermissionService double that models per-(user,path) ACL: both
     * permissionsFromNode(node) [used by the build] and getFolderPermissions(path)
     * [used by the refresh] return the SAME decision, keyed by the node/path's
     * IntraVox-relative path. $deny lists the relative paths this user cannot read.
     *
     * @param string[] $deny
     */
    private function permsFor(array $deny): PermissionService {
        $decision = function (string $rel) use ($deny): array {
            $canRead = !in_array($rel, $deny, true);
            return [
                'canRead' => $canRead,
                'canWrite' => $canRead,
                'canCreate' => $canRead,
                'canDelete' => $canRead,
                'canShare' => false,
                'raw' => $canRead ? 31 : 0,
            ];
        };
        $relFromPath = static function (string $absOrRel): string {
            // Node paths are /IntraVox/en/about; getFolderPermissions gets en/about.
            $p = ltrim($absOrRel, '/');
            if (str_starts_with($p, 'IntraVox/')) {
                $p = substr($p, strlen('IntraVox/'));
            }
            return $p;
        };

        $perms = $this->createMock(PermissionService::class);
        $perms->method('permissionsFromNode')->willReturnCallback(
            fn($node) => $decision($relFromPath($node->getPath()))
        );
        $perms->method('getFolderPermissions')->willReturnCallback(
            fn(string $path) => $decision($relFromPath($path))
        );
        $perms->method('getCacheDiscriminator')->willReturn('');
        return $perms;
    }

    /** The /IntraVox base whose get('en') returns the en mount. */
    private function baseMount(Folder $en): Folder {
        $base = $this->createMock(Folder::class);
        $base->method('getName')->willReturn('IntraVox');
        $base->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willReturn([$en]);
        $base->method('nodeExists')->willReturnCallback(fn($n) => $n === 'en');
        $base->method('get')->willReturnCallback(function ($p) use ($en) {
            if ($p === 'en') {
                return $en;
            }
            throw new NotFoundException('/IntraVox/' . $p);
        });
        return $base;
    }

    private function serviceFor(PermissionService $perms): PageTreeService {
        $en = $this->enMount();
        $base = $this->baseMount($en);
        // intraVox == the /IntraVox base (so languageFolderByCode('en')=base->get('en')
        // resolves the en mount, and relativePathFromRoot(en/about) resolves against
        // the base root). readLanguageFolder is the en mount.
        $folders = $this->fakeFolderContext(
            readLanguageFolder: $en,
            intraVox: $base,
            userLanguage: 'en',
        );
        // homepageService: no configurable-homepage pointer (returns null), so the
        // loose home.json stays first and no reordering happens.
        $homepageService = $this->createMock(\OCA\IntraVox\Service\HomepageService::class);
        $homepageService->method('getHomepageUniqueId')->willReturn(null);

        return $this->fakeTreeService([
            'permissionService' => $perms,
            'folderContext' => $folders,
            'homepageService' => $homepageService,
            'logger' => $this->createMock(LoggerInterface::class),
        ]);
    }

    /** Flatten the tree to path => permissions for assertion. */
    private function permsByPath(array $tree): array {
        $out = [];
        $walk = function (array $nodes) use (&$walk, &$out): void {
            foreach ($nodes as $n) {
                if (isset($n['path'])) {
                    $out[$n['path']] = $n['permissions'];
                }
                if (!empty($n['children'])) {
                    $walk($n['children']);
                }
            }
        };
        $walk($tree);
        return $out;
    }

    public function testFreshBuildServesFullAccessUserEveryNodeReadable(): void {
        $tree = $this->serviceFor($this->permsFor([]))->getPageTree(language: 'en');
        $byPath = $this->permsByPath($tree);

        // Home + both subpages present and readable.
        $this->assertArrayHasKey('en', $byPath, 'home node present');
        $this->assertArrayHasKey('en/about', $byPath);
        $this->assertArrayHasKey('en/secret', $byPath);
        foreach ($byPath as $path => $perm) {
            $this->assertTrue($perm['canRead'], "admin reads {$path}");
        }
    }

    public function testFreshBuildEnforcesPerUserAclDenyWithoutTheSecondPass(): void {
        // The restricted user is denied 'en/secret' (models Piet's -read deny).
        // The build filters non-readable nodes out entirely (canRead=false ->
        // skipped), so the served tree must NOT contain en/secret, while en/about
        // survives — the exact per-user pruning the retired refresh guaranteed.
        $tree = $this->serviceFor($this->permsFor(['en/secret']))->getPageTree(language: 'en');
        $byPath = $this->permsByPath($tree);

        $this->assertArrayHasKey('en/about', $byPath, 'a readable sibling survives');
        $this->assertTrue($byPath['en/about']['canRead']);
        $this->assertArrayNotHasKey('en/secret', $byPath, 'the ACL-denied page is pruned on the fresh build');
        // The home node is readable for this user (only en/secret is denied).
        $this->assertArrayHasKey('en', $byPath);
        $this->assertTrue($byPath['en']['canRead']);
    }

    public function testHomeNodeMissingLanguageFallbackFailsClosedNotOpen(): void {
        // The divergence the adversarial verify flagged: request a language whose
        // folder is ABSENT. languageFolderByCode('de') silently falls back to the
        // en folder, but the home node's 'path' is still 'de'. The OLD code's
        // second pass called getFolderPermissions('de') -> get('IntraVox/de') throws
        // -> fail-closed canRead=false. The fix must reproduce that: it derives the
        // home node's perms from getFolderPermissions($lang) at build time, so a
        // missing language stays fail-closed instead of leaking the en folder's
        // readable perms under a 'de' path.
        $en = $this->enMount();
        $base = $this->baseMount($en); // base has ONLY 'en'; 'de' is absent

        $folders = $this->fakeFolderContext(
            readLanguageFolder: $en,
            intraVox: $base,
            userLanguage: 'en',
        );
        $homepageService = $this->createMock(\OCA\IntraVox\Service\HomepageService::class);
        $homepageService->method('getHomepageUniqueId')->willReturn(null);

        // getFolderPermissions('de') must fail-closed; the double models the real
        // service: a path whose folder does not exist resolves to all-false.
        $perms = $this->createMock(PermissionService::class);
        $perms->method('permissionsFromNode')->willReturnCallback(function ($node) {
            return ['canRead' => true, 'canWrite' => true, 'canCreate' => true,
                    'canDelete' => true, 'canShare' => false, 'raw' => 31];
        });
        $perms->method('getFolderPermissions')->willReturnCallback(function (string $path) {
            // Only en-rooted paths resolve; 'de' (the missing language) fails closed.
            $ok = $path === 'de' ? false : str_starts_with($path, 'en');
            return ['canRead' => $ok, 'canWrite' => $ok, 'canCreate' => $ok,
                    'canDelete' => $ok, 'canShare' => false, 'raw' => $ok ? 31 : 0];
        });
        $perms->method('getCacheDiscriminator')->willReturn('');

        $svc = $this->fakeTreeService([
            'permissionService' => $perms,
            'folderContext' => $folders,
            'homepageService' => $homepageService,
            'logger' => $this->createMock(LoggerInterface::class),
        ]);

        $tree = $svc->getPageTree(language: 'de');
        $byPath = $this->permsByPath($tree);

        // The home node is emitted under path 'de' (the requested language) but must
        // be fail-closed, NOT readable — the fallback en folder must not leak here.
        $this->assertArrayHasKey('de', $byPath, 'home node emitted under the requested language path');
        $this->assertFalse(
            $byPath['de']['canRead'],
            'a missing-language home node must fail closed (canRead=false), matching the retired second pass — not leak the en fallback'
        );
    }

    public function testFreshBuildPerNodePermsMatchThePerUserDecisionForBothUsers(): void {
        // The core equivalence: for EVERY node the fresh build serves, its
        // permissions equal the per-(user,path) ACL decision — i.e. exactly what
        // refreshTreePermissions(getFolderPermissions(path)) would have written.
        foreach ([[], ['en/secret']] as $deny) {
            $perms = $this->permsFor($deny);
            $tree = $this->serviceFor($perms)->getPageTree(language: 'en');
            foreach ($this->permsByPath($tree) as $path => $served) {
                $expected = $perms->getFolderPermissions($path);
                $this->assertSame(
                    $expected,
                    $served,
                    "fresh-build perms for {$path} must equal getFolderPermissions({$path}) — the skipped refresh's result"
                );
            }
        }
    }

    /**
     * PERFORMANCE regression pin (deterministic — counts calls, not wall-clock so
     * it can't flake in CI). The whole point of the fix is that a fresh build does
     * NOT run the redundant per-user re-resolution: getFolderPermissions() — the
     * ~0.4ms/node getUserFolder->get() the VM measured as the tree's dominant cost
     * (~14ms/35 nodes) — must be called AT MOST ONCE on a fresh build (the home
     * node only), never once per tree node. Before the fix, refreshTreePermissions
     * called it for every node; if that second pass ever comes back, this fails.
     */
    public function testFreshBuildDoesNotReResolvePermissionsPerNode(): void {
        $en = $this->enMount();
        $base = $this->baseMount($en);
        $folders = $this->fakeFolderContext(
            readLanguageFolder: $en,
            intraVox: $base,
            userLanguage: 'en',
        );
        $homepageService = $this->createMock(\OCA\IntraVox\Service\HomepageService::class);
        $homepageService->method('getHomepageUniqueId')->willReturn(null);

        // Count the two resolution primitives separately.
        $fromNodeCalls = 0;
        $getFolderCalls = 0;
        $decision = ['canRead' => true, 'canWrite' => true, 'canCreate' => true,
                     'canDelete' => true, 'canShare' => false, 'raw' => 31];
        $perms = $this->createMock(PermissionService::class);
        $perms->method('permissionsFromNode')->willReturnCallback(function () use (&$fromNodeCalls, $decision) {
            $fromNodeCalls++;
            return $decision;
        });
        $perms->method('getFolderPermissions')->willReturnCallback(function () use (&$getFolderCalls, $decision) {
            $getFolderCalls++;
            return $decision;
        });
        $perms->method('getCacheDiscriminator')->willReturn('');

        $svc = $this->fakeTreeService([
            'permissionService' => $perms,
            'folderContext' => $folders,
            'homepageService' => $homepageService,
            'logger' => $this->createMock(LoggerInterface::class),
        ]);

        $tree = $svc->getPageTree(language: 'en');

        // The fixture has 3 readable nodes (home + about + secret). The BUILD
        // resolves each subpage node once via permissionsFromNode, and the home
        // node once via getFolderPermissions. The retired refresh would have added
        // one getFolderPermissions PER node (4+). Assert the second pass is gone:
        $this->assertCount(3, $this->permsByPath($tree), 'home + two subpages');
        $this->assertLessThanOrEqual(
            1,
            $getFolderCalls,
            "fresh build must NOT re-resolve permissions per node — getFolderPermissions ran {$getFolderCalls}× (the second pass is back)"
        );
        // Sanity: the build itself did resolve the subpage nodes (not zero work).
        $this->assertGreaterThanOrEqual(2, $fromNodeCalls, 'the build resolved the subpage nodes');
    }

    /**
     * The other half of the gate: a cache HIT must still be re-personalised.
     *
     * Skipping the refresh is only safe because the fresh build resolved every
     * node for THIS user. A cached tree has no such guarantee — it is a
     * group-shared blob built by whoever missed the cache first, and on most
     * installs that is CacheWarmupJob running from cron every fifteen minutes.
     * Serve it unrefreshed and every member of the group gets the builder's
     * view of the tree, which is #112 in reverse.
     *
     * The five tests above all drive the fresh-build path, so flipping either
     * cache-hit call site to $freshlyBuilt = true leaves them green. I checked:
     * turning the DISTRIBUTED hit into a skip passes all 1345 unit tests. That
     * is the path production actually takes — dev and every real install run
     * Redis — so the one branch that matters most was the one nothing watched.
     *
     * Both hits get their own test rather than one parameterised case: they are
     * separate call sites and a regression tends to touch one of them.
     *
     * @dataProvider cacheHitPaths
     */
    public function testCacheHitIsRePersonalisedForTheReadingUser(string $which): void {
        // The cached blob carries the BUILDER's permissions: everything readable,
        // including en/secret. The reading user is denied there.
        $cachedTree = [
            [
                'uniqueId' => 'home', 'title' => 'Home', 'status' => 'published',
                'fileId' => 1, 'path' => 'en', 'language' => 'en',
                'isCurrent' => false, 'children' => [],
                'permissions' => $this->allowAll(),
            ],
            [
                'uniqueId' => 'secret', 'title' => 'Secret', 'status' => 'published',
                'fileId' => 2, 'path' => 'en/secret', 'language' => 'en',
                'isCurrent' => false, 'children' => [],
                'permissions' => $this->allowAll(),
            ],
        ];

        $cache = $this->createMock(\OCA\IntraVox\Service\Cache\PageCacheService::class);
        if ($which === 'in-process') {
            $cache->method('getTree')->willReturn(['tree' => $cachedTree, 'time' => time()]);
            $cache->method('isDistributedAvailable')->willReturn(false);
        } else {
            $cache->method('getTree')->willReturn(null);
            $cache->method('isDistributedAvailable')->willReturn(true);
            $cache->method('getDistributed')->willReturn(json_encode($cachedTree));
        }

        $perms = $this->permsFor(['en/secret']);
        $en = $this->enMount();
        $homepageService = $this->createMock(\OCA\IntraVox\Service\HomepageService::class);
        $homepageService->method('getHomepageUniqueId')->willReturn(null);

        $svc = $this->fakeTreeService([
            'cache' => $cache,
            'permissionService' => $perms,
            'folderContext' => $this->fakeFolderContext(
                readLanguageFolder: $en,
                intraVox: $this->baseMount($en),
                userLanguage: 'en',
            ),
            'homepageService' => $homepageService,
            'logger' => $this->createMock(LoggerInterface::class),
        ]);

        $served = $this->permsByPath($svc->getPageTree(language: 'en'));

        self::assertArrayHasKey('en/secret', $served, 'the cached node must still be served');
        self::assertFalse(
            $served['en/secret']['canRead'],
            "a {$which} cache hit served the builder's permissions unchanged: this "
            . 'user is denied on en/secret but got canRead=true. The cached tree is '
            . 'group-shared — usually built by cron — and must be re-personalised '
            . 'on every hit (#86/#112)'
        );
        self::assertTrue(
            $served['en']['canRead'],
            'the refresh must not turn a readable node unreadable'
        );
    }

    /** @return array<string, array{string}> */
    public static function cacheHitPaths(): array {
        return [
            'in-process cache' => ['in-process'],
            'distributed cache' => ['distributed'],
        ];
    }

    /** The permission shape a fully-allowed node carries in a cached tree. */
    private function allowAll(): array {
        return [
            'canRead' => true, 'canWrite' => true, 'canCreate' => true,
            'canDelete' => true, 'canShare' => false, 'raw' => 31,
        ];
    }
}
