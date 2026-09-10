<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Event\PageDeletedEvent;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes deletePage()'s contract before it moves into Page/PageWriteService
 * in Phase 14. The load-bearing invariant (plan W2) is that PageDeletedEvent is
 * dispatched BEFORE the folder is deleted, so the comment/reaction cleanup
 * listener still sees a live page. These tests pin that ordering plus the guard
 * rejections and the clearCache-after-delete step.
 */
class PageCrudWriteTest extends TestCase {

    use BuildsPageService;

    /** Ordered log of side effects, used to assert event-before-delete. */
    private array $events = [];

    /** Number of clearCache() calls the service made. */
    private int $clearCacheCalls = 0;

    protected function setUp(): void {
        $this->events = [];
        $this->clearCacheCalls = 0;
    }

    /**
     * Build a delete-capable service. The page 'page-del' lives as del.json + del/
     * in the language folder, found by the real PageLocator's primary-folder scan
     * (the index mock misses, the root seam throws so the walk stays in-folder).
     *
     * @param bool $isHomepage what the isHomepage() seam reports for the page
     */
    private function makeService(bool $isHomepage = false): PageService {
        $pageJson = $this->makeFile(
            '/IntraVox/en/del.json',
            ['uniqueId' => 'page-del', 'title' => 'Delete me']
        );
        // The page's own folder records its delete() into the ordered event log.
        $pageFolder = $this->createMock(Folder::class);
        $pageFolder->method('getName')->willReturn('del');
        $pageFolder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $pageFolder->method('getPath')->willReturn('/IntraVox/en/del');
        $pageFolder->method('getDirectoryListing')->willReturn([]);
        $pageFolder->method('delete')->willReturnCallback(function () {
            $this->events[] = 'folder.delete';
        });

        $lang = $this->makeFolder('/IntraVox/en', [
            'del.json' => $pageJson,
            'del' => $pageFolder,
        ]);

        // deletePage resolves its language folder via folders()->languageFolder()
        // (wired to $lang below) and walks cross-language via locatePageAnyLanguage
        // -> rootClosure() -> getIntraVoxFolder, which is kept THROWING here to pin
        // the degrade-to-in-folder-walk behaviour.
        $svc = new class($isHomepage) extends PageService {
            private bool $home;
            public function __construct(bool $home) {
                $this->home = $home;
            }
            public function isHomepage(string $uniqueId, ?string $language = null): bool {
                return $this->home;
            }
        };

        $dispatcher = $this->createMock(IEventDispatcher::class);
        $dispatcher->method('dispatchTyped')->willReturnCallback(function ($event) {
            if ($event instanceof PageDeletedEvent) {
                $this->events[] = 'event:' . $event->getPageId() . ':' . $event->getUniqueId();
            }
        });

        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);

        // clearCache() is private, so it cannot be overridden on the subclass; it
        // is observed through its first collaborator call, clearRequest(). That call
        // now lives in PageCacheInvalidator (phase 2), so the spy goes on the cache
        // the invalidator holds — and the invalidator is wired explicitly over the
        // same cache so PageService's own cache() accessor stays consistent.
        $cache = $this->createMock(\OCA\IntraVox\Service\Cache\PageCacheService::class);
        $cache->method('clearRequest')->willReturnCallback(function () {
            $this->clearCacheCalls++;
        });
        $cacheInvalidator = new \OCA\IntraVox\Service\Cache\PageCacheInvalidator(
            $cache,
            $this->createMock(\OCA\IntraVox\Service\Locator\PageLocator::class),
            $this->createMock(\OCA\IntraVox\Service\PermissionService::class)
        );

        $this->injectPageServiceDependencies($svc, [
            'eventDispatcher' => $dispatcher,
            'pageIndexService' => $index,
            'cache' => $cache,
            'cacheInvalidator' => $cacheInvalidator,
            'logger' => $this->createMock(LoggerInterface::class),
            'folderContext' => $this->fakeFolderContext(languageFolder: $lang),
        ]);

        return $svc;
    }

    public function testDeletingHomeIdIsRejectedOutright(): void {
        $svc = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete home page');
        $svc->deletePage('home');
    }

    /**
     * Guard-ordering pin: the cheap $id==='home' guard MUST fire before the
     * language folder is resolved. Resolving it can create-on-miss or throw
     * (getIntraVoxFolder), so a rejected 'home' delete must never touch it. The
     * unwired FolderContext throws on any folder resolution; if the guard order
     * regressed (folder resolved first, as the write-cluster carve once did) this
     * would surface a LogicException instead of the crisp 'Cannot delete home page'.
     */
    public function testDeletingHomeRejectsBeforeResolvingTheFolder(): void {
        $svc = new class extends PageService {
            public function __construct() {
            }
            public function clearCache(): void {
            }
        };
        $this->injectPageServiceDependencies($svc, [
            'logger' => $this->createMock(LoggerInterface::class),
            // No folder wired: languageFolder()/intraVox() throw LogicException if
            // deletePage resolves the folder before checking $id==='home'.
            'folderContext' => $this->fakeFolderContext(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete home page');
        $svc->deletePage('home');
    }

    public function testDeletingUnknownPageThrowsPageNotFound(): void {
        // An empty language folder under an empty IntraVox root: the primary-folder
        // scan misses and the cross-language walk (which resolves the root) finds
        // no other language folders either, so the lookup returns null.
        $empty = $this->makeFolder('/IntraVox/en', []);
        $base = $this->makeFolder('/IntraVox', ['en' => $empty]);
        // languageFolder ($empty) via the seam; getIntraVoxFolder ($base) kept for
        // the cross-language walk (locatePageAnyLanguage -> rootClosure()).
        $svc = new class extends PageService {
            public function __construct() {
            }
        };
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $this->injectPageServiceDependencies($svc, [
            'pageIndexService' => $index,
            'logger' => $this->createMock(LoggerInterface::class),
            'folderContext' => $this->fakeFolderContext(intraVox: $base, languageFolder: $empty),
        ]);

        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionMessage('Page not found: page-missing');
        $svc->deletePage('page-missing');
    }

    public function testConfiguredHomepageCannotBeDeleted(): void {
        $svc = $this->makeService(isHomepage: true);

        try {
            $svc->deletePage('page-del');
            $this->fail('the configured homepage must not be deletable');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('HOMEPAGE_PROTECTED', $e->getMessage());
        }
        $this->assertNotContains('folder.delete', $this->events, 'nothing may be deleted when the guard fires');
    }

    public function testEventIsDispatchedBeforeTheFolderIsDeleted(): void {
        $svc = $this->makeService();

        $svc->deletePage('page-del');

        $this->assertSame(
            ['event:del:page-del', 'folder.delete'],
            $this->events,
            'PageDeletedEvent must fire before the folder delete so cleanup sees a live page'
        );
    }

    public function testDeleteClearsCacheAfterwards(): void {
        $svc = $this->makeService();

        $svc->deletePage('page-del');

        $this->assertSame(1, $this->clearCacheCalls, 'clearCache runs once after a successful delete');
    }
}
