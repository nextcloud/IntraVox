<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Maintenance\PageCacheStatusService;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Characterization of PageCacheStatusService::checkPageCacheStatus — the
 * "is this page in Nextcloud's file cache yet?" diagnostic carved out of
 * PageService (fase-4 C5). Pins the SIX return shapes verbatim so the extraction
 * is provably behaviour-preserving: it had no behavioural unit test before, only
 * the controller endpoint's arity.
 */
class PageCacheStatusServiceTest extends TestCase {

    /**
     * A storage whose cache->get($internalPath) returns $entry (or false).
     * getStorage() is untyped on the Node stub and OCP\Files\Cache\ICache is not
     * stubbed, so a plain anonymous object with getCache()->get() suffices.
     */
    private function storageWithCacheEntry($entry): object {
        $cache = new class($entry) {
            public function __construct(private mixed $entry) {}
            public function get($path) { return $this->entry; }
        };
        return new class($cache) {
            public function __construct(private object $cache) {}
            public function getCache() { return $this->cache; }
        };
    }

    private function fileWithEntry(string $path, $entry): File {
        $file = $this->createMock(File::class);
        $file->method('getInternalPath')->willReturn('files/' . ltrim($path, '/'));
        $file->method('getPath')->willReturn($path);
        $file->method('getStorage')->willReturn($this->storageWithCacheEntry($entry));
        return $file;
    }

    private function folderWithEntry(string $path, $entry): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getInternalPath')->willReturn('files/' . ltrim($path, '/'));
        $folder->method('getPath')->willReturn($path);
        $folder->method('getStorage')->willReturn($this->storageWithCacheEntry($entry));
        return $folder;
    }

    private function cacheEntry(int $id): ICacheEntry {
        $e = $this->createMock(ICacheEntry::class);
        $e->method('getId')->willReturn($id);
        return $e;
    }

    /** The language folder the service resolves via FolderContext::languageFolder(). */
    private function service(Folder $languageFolder, ?PageLocator $locator = null): PageCacheStatusService {
        $config = $this->createMock(IConfig::class);
        $config->method('getUserValue')->willReturn('en');
        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('getPrimaryLanguage')->willReturn('en');
        $folders = new FolderContext(
            $this->createMock(IRootFolder::class),
            'tester',
            $config,
            $languageService,
            new LanguageResolver(),
            new PageLocator($this->createMock(PageIndexService::class), $this->createMock(LoggerInterface::class)),
            $languageFolder,                    // intraVoxOverride
            fn(): Folder => $languageFolder,    // readLanguageFolder seam
            fn(): Folder => $languageFolder     // languageFolder seam
        );
        return new PageCacheStatusService(
            $folders,
            $locator ?? new PageLocator($this->createMock(PageIndexService::class), $this->createMock(LoggerInterface::class)),
            new PageIdUtils(),
            $this->createMock(LoggerInterface::class)
        );
    }

    // 1. home + cache HIT
    public function testHomeVisibleWhenCached(): void {
        $home = $this->fileWithEntry('/IntraVox/en/home.json', $this->cacheEntry(42));
        $lang = $this->createMock(Folder::class);
        $lang->method('get')->willReturnCallback(fn($n) => $n === 'home.json' ? $home : throw new NotFoundException($n));
        $res = $this->service($lang)->checkPageCacheStatus('home');

        $this->assertSame(true, $res['visible']);
        $this->assertSame(true, $res['inCache']);
        $this->assertSame(42, $res['fileId']);
        $this->assertSame('/IntraVox/en/home.json', $res['path']);
        $this->assertSame('Page is visible in Files app', $res['message']);
    }

    // 2. home + cache MISS (entry === false)
    public function testHomeWaitingWhenNotCached(): void {
        $home = $this->fileWithEntry('/IntraVox/en/home.json', false);
        $lang = $this->createMock(Folder::class);
        $lang->method('get')->willReturnCallback(fn($n) => $n === 'home.json' ? $home : throw new NotFoundException($n));
        $res = $this->service($lang)->checkPageCacheStatus('home');

        $this->assertSame(false, $res['visible']);
        $this->assertSame(false, $res['inCache']);
        $this->assertNull($res['fileId']);
        $this->assertSame('Page created but waiting for indexing', $res['message']);
    }

    // 3. home.json missing entirely
    public function testHomeFileNotFound(): void {
        $lang = $this->createMock(Folder::class);
        $lang->method('get')->willReturnCallback(fn($n) => throw new NotFoundException($n));
        $res = $this->service($lang)->checkPageCacheStatus('home');

        $this->assertSame(false, $res['visible']);
        $this->assertSame(false, $res['inCache']);
        $this->assertNull($res['fileId']);
        $this->assertSame('Home page file not found', $res['message']);
    }

    // 4. regular page resolved (via folder->get fallback) + cache HIT
    public function testRegularPageVisibleWhenCached(): void {
        $pageFolder = $this->folderWithEntry('/IntraVox/en/about', $this->cacheEntry(77));
        $lang = $this->createMock(Folder::class);
        $lang->method('getPath')->willReturn('/IntraVox/en');
        $lang->method('getDirectoryListing')->willReturn([]);
        $lang->method('get')->willReturnCallback(fn($n) => $n === 'about' ? $pageFolder : throw new NotFoundException($n));
        // Locator finds nothing across languages, so it falls back to $folder->get($pageId).
        $res = $this->service($lang)->checkPageCacheStatus('about');

        $this->assertSame(true, $res['visible']);
        $this->assertSame(true, $res['inCache']);
        $this->assertSame(77, $res['folderId']);
        $this->assertSame('/IntraVox/en/about', $res['path']);
        $this->assertSame('Page is visible in Files app', $res['message']);
    }

    // 5. regular page folder exists but NOT in cache (entry false)
    public function testRegularPageWaitingWhenNotCached(): void {
        $pageFolder = $this->folderWithEntry('/IntraVox/en/about', false);
        $lang = $this->createMock(Folder::class);
        $lang->method('getPath')->willReturn('/IntraVox/en');
        $lang->method('getDirectoryListing')->willReturn([]);
        $lang->method('get')->willReturnCallback(fn($n) => $n === 'about' ? $pageFolder : throw new NotFoundException($n));
        $res = $this->service($lang)->checkPageCacheStatus('about');

        $this->assertSame(false, $res['visible']);
        $this->assertSame(false, $res['inCache']);
        $this->assertNull($res['folderId']);
        $this->assertSame('Page created but waiting for Nextcloud to index it. This may take 5-15 minutes.', $res['message']);
    }

    // 6a. regular page not found anywhere (inner NotFoundException)
    public function testRegularPageFolderNotFound(): void {
        $lang = $this->createMock(Folder::class);
        $lang->method('getPath')->willReturn('/IntraVox/en');
        $lang->method('getDirectoryListing')->willReturn([]);
        $lang->method('get')->willReturnCallback(fn($n) => throw new NotFoundException($n));
        $res = $this->service($lang)->checkPageCacheStatus('ghost');

        $this->assertSame(false, $res['visible']);
        $this->assertSame(false, $res['inCache']);
        $this->assertNull($res['folderId']);
        $this->assertSame('Page folder not found', $res['message']);
    }

    // 6b. outer catch: languageFolder() itself throws a non-NotFound \Exception
    public function testOuterCatchWhenLanguageFolderThrows(): void {
        // A FolderContext whose languageFolder() throws (no override, unresolvable mount).
        $config = $this->createMock(IConfig::class);
        $config->method('getUserValue')->willReturn('en');
        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $rootFolder->method('getUserFolder')->willThrowException(new \RuntimeException('boom'));
        $folders = new FolderContext(
            $rootFolder, 'tester', $config, $languageService, new LanguageResolver(),
            new PageLocator($this->createMock(PageIndexService::class), $this->createMock(LoggerInterface::class))
        );
        $svc = new PageCacheStatusService(
            $folders,
            new PageLocator($this->createMock(PageIndexService::class), $this->createMock(LoggerInterface::class)),
            new PageIdUtils(),
            $this->createMock(LoggerInterface::class)
        );

        $res = $svc->checkPageCacheStatus('about');
        $this->assertSame(false, $res['visible']);
        $this->assertSame(false, $res['inCache']);
        $this->assertArrayHasKey('error', $res);
        $this->assertSame('Unable to check cache status', $res['message']);
    }
}
