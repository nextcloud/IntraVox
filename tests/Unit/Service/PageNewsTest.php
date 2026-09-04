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
        bool $metaVoxAvailable = false
    ): PageService {
        $svc = new class($readFolder) extends PageService {
            private Folder $readFolder;
            public function __construct(Folder $readFolder) {
                $this->readFolder = $readFolder;
            }
            protected function getReadLanguageFolder(): Folder {
                return $this->readFolder;
            }
            protected function getIntraVoxFolder() {
                return $this->readFolder;
            }
            // resolveEffectiveLanguage is private; but getReadLanguageFolder is
            // the only seam getNewsPages needs for the folder. Language is
            // resolved via the (mocked) collaborators below.
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
        $news->method('buildSourcePageItem')->willReturn(null);

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
}
