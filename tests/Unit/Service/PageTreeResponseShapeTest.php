<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes getPageTree()'s cache/response-shaping orchestration before it
 * moves into Tree/PageTreeService in Phase 12 (v2). Complements the existing
 * PageTreePlaceholderTest (which covers fresh-build placeholder behaviour) by
 * pinning the parts that make the shared cache safe:
 *
 *  - an in-process cache hit within TTL returns via shapeTreeResponse WITHOUT
 *    rebuilding from the filesystem;
 *  - the current page is marked (isCurrent) on the returned tree;
 *  - the deep-copy quirk (issue #86/#70): shaping the group-shared cached blob
 *    for one user must NOT mutate the cached tree, so the next user does not
 *    inherit the first user's isCurrent / permissions.
 */
class PageTreeResponseShapeTest extends TestCase {

    use BuildsPageService;

    /** A minimal cached tree: two root nodes, one with a child. */
    private function cachedTree(): array {
        return [
            [
                'uniqueId' => 'page-a',
                'title' => 'A',
                'path' => 'en/a',
                'language' => 'en',
                'isCurrent' => false,
                'permissions' => ['canRead' => true, 'canWrite' => false],
                'children' => [
                    [
                        'uniqueId' => 'page-a1',
                        'title' => 'A1',
                        'path' => 'en/a/a1',
                        'language' => 'en',
                        'isCurrent' => false,
                        'permissions' => ['canRead' => true, 'canWrite' => false],
                        'children' => [],
                    ],
                ],
            ],
            [
                'uniqueId' => 'page-b',
                'title' => 'B',
                'path' => 'en/b',
                'language' => 'en',
                'isCurrent' => false,
                'permissions' => ['canRead' => true, 'canWrite' => false],
                'children' => [],
            ],
        ];
    }

    /**
     * @param array $cachedTree the in-process cached tree blob (by reference-able)
     */
    private function makeService(array &$cachedTree, PageCacheService $cache): PageService {
        $svc = new class extends PageService {
            public function __construct() {
            }
            // refreshTreePermissions resolves getFolderPermissions via the root;
            // let it degrade so the cached permissions survive (the quirk we pin).
            protected function getIntraVoxFolder(): Folder {
                throw new \RuntimeException('no root in this fixture');
            }
            protected function getLanguageFolder(): Folder {
                throw new \RuntimeException('not needed for a cache hit');
            }
        };

        // GroupContextService is final; the harness builds the real one via
        // doubleOrBuild. Its group hash only forms the cache key, which is
        // irrelevant here because getTree() is stubbed to always return the blob.
        $this->injectPageServiceDependencies($svc, [
            'cache' => $cache,
            'logger' => $this->createMock(LoggerInterface::class),
        ]);

        return $svc;
    }

    public function testInProcessCacheHitReturnsWithoutRebuilding(): void {
        $tree = $this->cachedTree();

        $cache = $this->createMock(PageCacheService::class);
        $cache->method('getTree')->willReturn(['tree' => $tree, 'time' => time()]);
        // A hit within TTL must not consult the distributed cache or write back.
        $cache->expects($this->never())->method('getDistributed');
        $cache->expects($this->never())->method('setTree');
        $cache->expects($this->never())->method('setDistributed');

        $svc = $this->makeService($tree, $cache);

        // language 'en' provided so no effective-language resolution is needed.
        $result = $svc->getPageTree(currentPageId: null, language: 'en');

        $this->assertCount(2, $result);
        $this->assertSame('page-a', $result[0]['uniqueId']);
        $this->assertSame('page-b', $result[1]['uniqueId']);
    }

    public function testCurrentPageIsMarkedOnTheReturnedTree(): void {
        $tree = $this->cachedTree();
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('getTree')->willReturn(['tree' => $tree, 'time' => time()]);

        $svc = $this->makeService($tree, $cache);

        $result = $svc->getPageTree(currentPageId: 'page-a1', language: 'en');

        $this->assertFalse($result[0]['isCurrent'], 'root A is not the current page');
        $this->assertTrue($result[0]['children'][0]['isCurrent'], 'the nested current page is marked');
        $this->assertFalse($result[1]['isCurrent']);
    }

    public function testShapingDoesNotMutateTheSharedCachedTree(): void {
        // The heart of the #86/#70 safety: two users hit the same cached blob with
        // different currentPageIds; neither may see the other's marking, so the
        // cached tree must stay pristine between calls.
        $tree = $this->cachedTree();
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('getTree')->willReturn(['tree' => $tree, 'time' => time()]);

        $svc = $this->makeService($tree, $cache);

        $first = $svc->getPageTree(currentPageId: 'page-a', language: 'en');
        $this->assertTrue($first[0]['isCurrent']);

        // The cached source array must be untouched: still all-false isCurrent.
        $this->assertFalse($tree[0]['isCurrent'], 'the shared cached tree must not be mutated');
        $this->assertFalse($tree[0]['children'][0]['isCurrent']);

        // A second user marking a different page sees a clean starting point.
        $second = $svc->getPageTree(currentPageId: 'page-b', language: 'en');
        $this->assertFalse($second[0]['isCurrent'], 'user 2 must not inherit user 1 marking');
        $this->assertTrue($second[1]['isCurrent']);
    }
}
