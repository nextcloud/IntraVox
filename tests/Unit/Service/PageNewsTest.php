<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\News\NewsPageService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Publication\MetaVoxGateway;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of getNewsPages() — the News-widget orchestration — which
 * had zero behavioural coverage (only PageServicePublicSurfaceTest, an arity
 * map, mentioned it). getNewsPages is a public endpoint reached from
 * ApiController/PublicShareController, so this pins its observable contract:
 * the source-page/-folder resolution and its not-found early returns, the
 * collect -> filter -> sort/limit pipeline order, and the {items,total,
 * metavoxAvailable} result shape.
 *
 * Strategy: override the getReadLanguageFolder / resolveEffectiveLanguage seams
 * to feed a fixture folder + language; inject a mocked NewsPageService (the
 * collect/sort mechanics already live there and are tested there) and a mocked
 * MetaVoxGateway; run with the distributed cache unavailable so the pipeline is
 * exercised directly rather than short-circuited by a cache hit.
 */
class PageNewsTest extends TestCase {

    use BuildsPageService;

    /**
     * @param list<array> $collected what NewsPageService::findNewsPagesInFolder
     *   yields (written into the &$pages out-param)
     * @param bool $metaVoxAvailable value of MetaVoxGateway::isMetaVoxAvailable
     */
    private function makeService(
        Folder $readFolder,
        array $collected = [],
        bool $metaVoxAvailable = false,
        ?array $sourceItem = null
    ): PageService {
        // getNewsPages resolves its folder via folders()->readLanguageFolder +
        // folders()->intraVox (findNewsPagesInFolder) and runs no cross-language
        // locate, so both seam overrides go away. Language falls through
        // resolveEffectiveLanguage() (no real content -> null) to userLanguage 'en',
        // matching the old collaborator-resolved path.
        $svc = new class extends PageService {
            public function __construct() {
            }
            public function clearCache(): void {
            }
        };

        $news = $this->createMock(NewsPageService::class);
        // findNewsPagesInFolder writes into the &$pages out-param.
        $news->method('findNewsPagesInFolder')->willReturnCallback(
            function ($root, $folder, array &$pages, string $language, int $maxCollect = 0) use ($collected): void {
                foreach ($collected as $p) {
                    $pages[] = $p;
                }
            }
        );
        // sortAndLimit: honour the limit, keep order (the real sort is tested in
        // the NewsPageService suite; here we pin that getNewsPages applies it).
        $news->method('sortAndLimit')->willReturnCallback(
            fn(array $pages, string $sortBy, string $sortOrder, int $limit): array
                => array_slice($pages, 0, $limit)
        );
        $news->method('buildSourcePageItem')->willReturn($sourceItem);

        $metaVox = $this->createMock(MetaVoxGateway::class);
        $metaVox->method('isMetaVoxAvailable')->willReturn($metaVoxAvailable);

        // Distributed cache unavailable -> the version-counter cache block is
        // skipped entirely, so the pipeline runs and nothing is cached.
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('isDistributedAvailable')->willReturn(false);

        $this->injectPageServiceDependencies($svc, [
            'newsPageService' => $news,
            'metaVoxGateway' => $metaVox,
            'cache' => $cache,
            'logger' => $this->createMock(LoggerInterface::class),
            'userId' => 'tester',
            'folderContext' => $this->fakeFolderContext(
                readLanguageFolder: $readFolder,
                intraVox: $readFolder
            ),
        ]);
        return $svc;
    }

    /** A language folder that resolves $childName to $child, else throws. */
    private function folderWith(array $children = []): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn('en');
        $folder->method('getPath')->willReturn('/IntraVox/en');
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('get')->willReturnCallback(function ($p) use ($children) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new NotFoundException('/IntraVox/en/' . $p);
        });
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        return $folder;
    }

    public function testEmptySourceCollectsAndReturnsShape(): void {
        $svc = $this->makeService(
            $this->folderWith(),
            collected: [
                ['uniqueId' => 'page-1', 'title' => 'One', 'modified' => 10],
                ['uniqueId' => 'page-2', 'title' => 'Two', 'modified' => 20],
            ],
            metaVoxAvailable: false
        );

        $result = $svc->getNewsPages();

        $this->assertSame(2, $result['total']);
        $this->assertCount(2, $result['items']);
        $this->assertArrayHasKey('metavoxAvailable', $result);
        $this->assertFalse($result['metavoxAvailable']);
    }

    public function testMetavoxAvailableIsReportedInTheShape(): void {
        $svc = $this->makeService($this->folderWith(), collected: [], metaVoxAvailable: true);

        $result = $svc->getNewsPages();

        $this->assertTrue($result['metavoxAvailable']);
        $this->assertSame(0, $result['total']);
        $this->assertSame([], $result['items']);
    }

    public function testUnknownSourcePageIdReturnsEmptyWithMetavoxFlag(): void {
        // The read folder holds no page with this uniqueId, so
        // findPageByUniqueId() returns null -> the early "not found" return.
        $svc = $this->makeService($this->folderWith(), metaVoxAvailable: true);

        $result = $svc->getNewsPages(sourcePageId: 'page-does-not-exist');

        $this->assertSame(['items' => [], 'total' => 0, 'metavoxAvailable' => true], $result);
    }

    public function testMissingLegacySourcePathReturnsEmpty(): void {
        // A sourcePath that the language folder cannot resolve -> NotFound ->
        // the early empty return (legacy path branch).
        $svc = $this->makeService($this->folderWith(), metaVoxAvailable: false);

        $result = $svc->getNewsPages(sourcePath: 'nonexistent/folder');

        $this->assertSame(['items' => [], 'total' => 0, 'metavoxAvailable' => false], $result);
    }

    public function testTotalIsCountedBeforeTheLimitIsApplied(): void {
        // Five collected pages, limit 2: total reports 5, items is capped at 2.
        $svc = $this->makeService(
            $this->folderWith(),
            collected: array_map(
                fn(int $i) => ['uniqueId' => "page-$i", 'title' => "P$i", 'modified' => $i],
                range(1, 5)
            ),
            metaVoxAvailable: false
        );

        $result = $svc->getNewsPages(limit: 2);

        $this->assertSame(5, $result['total'], 'total is the pre-limit count');
        $this->assertCount(2, $result['items'], 'items honour the limit');
    }

    // ----------------------------------------- source-page + filter branches

    public function testSourcePageIsPrependedToTheResults(): void {
        // A resolvable sourcePageId whose buildSourcePageItem yields an item:
        // the source page is unshifted to the front of the collected children.
        // The locator resolves a home.json whose uniqueId matches FIRST, so a
        // loose home.json in the root is the simplest resolvable fixture.
        $srcFile = $this->createMock(\OCP\Files\File::class);
        $srcFile->method('getContent')->willReturn(json_encode(['uniqueId' => 'page-src', 'title' => 'Source']));
        $srcFile->method('getName')->willReturn('home.json');
        $srcFile->method('getId')->willReturn(42);
        $root = $this->createMock(Folder::class);
        $root->method('getName')->willReturn('en');
        $root->method('getPath')->willReturn('/IntraVox/en');
        $root->method('nodeExists')->willReturnCallback(fn($n) => $n === 'home.json');
        $root->method('getDirectoryListing')->willReturn([]);
        $root->method('get')->willReturnCallback(function ($p) use ($srcFile) {
            if ($p === 'home.json') {
                return $srcFile;
            }
            throw new NotFoundException($p);
        });

        // buildSourcePageItem returns a real item so the unshift branch fires.
        $svc = $this->makeService(
            $root,
            collected: [['uniqueId' => 'page-child', 'title' => 'Child', 'modified' => 5]],
            sourceItem: ['uniqueId' => 'page-src', 'title' => 'Source', 'modified' => 99]
        );

        $result = $svc->getNewsPages(sourcePageId: 'page-src');

        $this->assertSame('page-src', $result['items'][0]['uniqueId'],
            'the source page is prepended before the collected children');
        $this->assertSame(2, $result['total']);
    }

    public function testFilterPublishedInvokesTheEnginePublicationFilter(): void {
        $svc = $this->makeService(
            $this->folderWith(),
            collected: [
                ['uniqueId' => 'page-1', 'title' => 'One', 'modified' => 1],
                ['uniqueId' => 'page-2', 'title' => 'Two', 'modified' => 2],
            ]
        );
        $news = $this->newsMockFrom($svc);
        // The publication filter drops page-2, so total reflects the filtered set.
        $news->expects($this->once())
            ->method('applyPublicationDateFilter')
            ->willReturnCallback(fn(array $pages) => array_slice($pages, 0, 1));

        $result = $svc->getNewsPages(filterPublished: true);

        $this->assertSame(1, $result['total'], 'total counts the publication-filtered set, pre-limit');
    }

    // -------------------------------------------------- distributed cache path

    /**
     * The distributed-cache path is entirely skipped by makeService
     * (isDistributedAvailable=false). This variant enables it and records the
     * key/value handed to getDistributed/setDistributed.
     *
     * The GroupContextService is final (cannot be mocked); a real instance with
     * a userless session yields the deterministic ANONYMOUS_HASH ('anon'), which
     * is what the cache-key assertions below expect.
     *
     * @param array<string,string> $store getDistributed lookups (key => JSON)
     * @param array<int,array{0:string,1:string,2:int}> &$writes setDistributed calls
     */
    private function makeDistributedService(
        array $store,
        array &$writes,
        array $collected = []
    ): PageService {
        $svc = new class extends PageService {
            public function __construct() {
            }
            public function clearCache(): void {
            }
        };

        $news = $this->createMock(NewsPageService::class);
        $news->method('findNewsPagesInFolder')->willReturnCallback(
            function ($root, $folder, array &$pages, string $language, int $maxCollect = 0) use ($collected): void {
                foreach ($collected as $p) {
                    $pages[] = $p;
                }
            }
        );
        $news->method('sortAndLimit')->willReturnCallback(
            fn(array $pages, string $sortBy, string $sortOrder, int $limit): array => array_slice($pages, 0, $limit)
        );
        $news->method('buildSourcePageItem')->willReturn(null);

        $metaVox = $this->createMock(MetaVoxGateway::class);
        $metaVox->method('isMetaVoxAvailable')->willReturn(false);

        $cache = $this->createMock(PageCacheService::class);
        $cache->method('isDistributedAvailable')->willReturn(true);
        $cache->method('getDistributed')->willReturnCallback(fn($k) => $store[$k] ?? null);
        $cache->method('setDistributed')->willReturnCallback(
            function ($k, $v, $ttl) use (&$writes): void {
                $writes[] = [$k, $v, $ttl];
            }
        );

        // Final class -> build a real one; a userless session -> 'anon' hash.
        $session = $this->createMock(\OCP\IUserSession::class);
        $session->method('getUser')->willReturn(null);
        $groupContext = new \OCA\IntraVox\Service\GroupContextService(
            $session,
            $this->createMock(\OCP\IGroupManager::class)
        );

        $this->injectPageServiceDependencies($svc, [
            'newsPageService' => $news,
            'metaVoxGateway' => $metaVox,
            'cache' => $cache,
            'groupContext' => $groupContext,
            'logger' => $this->createMock(LoggerInterface::class),
            'userId' => 'tester',
            'folderContext' => $this->fakeFolderContext(
                readLanguageFolder: $this->folderWith(),
                intraVox: $this->folderWith(),
                userLanguage: 'en'
            ),
        ]);
        return $svc;
    }

    public function testDistributedCacheHitReturnsDecodedWithoutCollecting(): void {
        // Pre-seed the exact key getNewsPages builds for the default params.
        $lang = 'en';
        $paramHash = md5(json_encode(['', [], 'AND', 5, 'modified', 'desc', null, false]));
        $key = 'news_' . $lang . '_anon_v0_' . $paramHash;
        $cached = ['items' => [['uniqueId' => 'page-cached']], 'total' => 1, 'metavoxAvailable' => false];

        $writes = [];
        $svc = $this->makeDistributedService([$key => json_encode($cached)], $writes,
            collected: [['uniqueId' => 'page-should-not-appear', 'title' => 'X', 'modified' => 1]]);

        $result = $svc->getNewsPages();

        $this->assertSame($cached, $result, 'a cache hit returns the decoded payload verbatim');
        $this->assertSame([], $writes, 'a hit writes nothing');
    }

    public function testDistributedCacheMissWritesResultWithNewsTtl(): void {
        $writes = [];
        $svc = $this->makeDistributedService([], $writes,
            collected: [['uniqueId' => 'page-1', 'title' => 'One', 'modified' => 1]]);

        $result = $svc->getNewsPages();

        $this->assertSame(1, $result['total']);
        $this->assertCount(1, $writes, 'a miss writes exactly one cache entry');
        [$key, $value, $ttl] = $writes[0];
        $paramHash = md5(json_encode(['', [], 'AND', 5, 'modified', 'desc', null, false]));
        $this->assertSame('news_en_anon_v0_' . $paramHash, $key,
            'the key is news_{lang}_{groupHash}_v0_{paramHash} (v0 = read-only counter)');
        $this->assertSame(PageCacheService::NEWS_TTL, $ttl);
        $this->assertSame($result, json_decode($value, true), 'the written value is the json-encoded result');
    }

    /** A cached value that is not a JSON array falls through to the live pipeline. */
    public function testDistributedCacheNonArrayValueFallsThrough(): void {
        $lang = 'en';
        $paramHash = md5(json_encode(['', [], 'AND', 5, 'modified', 'desc', null, false]));
        $key = 'news_' . $lang . '_anon_v0_' . $paramHash;

        $writes = [];
        // A JSON string that decodes to a scalar, not an array -> no early return.
        $svc = $this->makeDistributedService([$key => '"not-an-array"'], $writes,
            collected: [['uniqueId' => 'page-live', 'title' => 'Live', 'modified' => 1]]);

        $result = $svc->getNewsPages();

        $this->assertSame(1, $result['total'], 'a non-array cached value does not short-circuit');
        $this->assertCount(1, $writes, 'the freshly-built result is then cached');
    }

    /** Reach into the anonymous subclass's injected NewsPageService mock. */
    private function newsMockFrom(PageService $svc): NewsPageService
    {
        $prop = new \ReflectionProperty(PageService::class, 'newsPageService');
        /** @var NewsPageService $news */
        $news = $prop->getValue($svc);
        return $news;
    }
}
