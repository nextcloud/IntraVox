<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
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
        };

        // GroupContextService is final; the harness builds the real one via
        // doubleOrBuild. Its group hash only forms the cache key, which is
        // irrelevant here because getTree() is stubbed to always return the blob.
        //
        // getPageTree resolves its folder through the injected FolderContext
        // (languageFolderByCode / effectiveLanguage); with language 'en' given and
        // the cache hitting, neither is reached. A fakeFolderContext with no folder
        // wired reproduces the old "must not resolve" guards exactly — its intraVox
        // closure throws if anything on the hit path tried to touch it.
        $this->injectPageServiceDependencies($svc, [
            'cache' => $cache,
            'logger' => $this->createMock(LoggerInterface::class),
            'folderContext' => $this->fakeFolderContext(),
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

    public function testConsecutiveHitsAreIsolatedPerCurrentPage(): void {
        // The observable #86/#70 guarantee: two users hit the same cached tree with
        // different currentPageIds, and neither sees the other's marking. This is
        // what a consumer can actually rely on, so it is what we assert.
        //
        // NOTE on the cache-mutation angle: getPageTree cannot pollute the cache
        // via this path anyway — shapeTreeResponse() takes the tree BY VALUE and
        // markCurrentPageInTree() returns a fresh array, so PHP's copy-on-write
        // already isolates the cached blob before production's own copy. A unit
        // test driving getTree through a mock therefore cannot meaningfully prove
        // "the cache array object is untouched" (the mock hands out a COW clone on
        // each call). The real protection lives in shapeTreeResponse's by-value
        // signature + markCurrentPageInTree's rebuild; the per-call isolation below
        // is the behaviour that would break if either regressed, so that is the pin.
        $tree = $this->cachedTree();
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('getTree')->willReturnCallback(
            fn() => ['tree' => $this->cachedTree(), 'time' => time()]
        );

        $svc = $this->makeService($tree, $cache);

        $first = $svc->getPageTree(currentPageId: 'page-a', language: 'en');
        $this->assertTrue($first[0]['isCurrent'], 'user 1 marks page-a');
        $this->assertFalse($first[1]['isCurrent']);

        // A second user marking a different page must not inherit user 1's marking.
        $second = $svc->getPageTree(currentPageId: 'page-b', language: 'en');
        $this->assertFalse($second[0]['isCurrent'], 'user 2 must not inherit user 1 marking on page-a');
        $this->assertTrue($second[1]['isCurrent'], 'user 2 marks page-b');
    }
}
