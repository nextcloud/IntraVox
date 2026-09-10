<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Version\PageVersionService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Behavioural characterization of the version-manager-delegating methods:
 * getPageVersions, restorePageVersion, getVersionContent. Each resolves a page
 * across language folders (the INLINE-A prologue: languageFolder -> page-*
 * locate -> findPageById fallback) and hands the file to PageVersionService.
 *
 * These had no direct behavioural unit test — only arity pins and the
 * cross-language locate for getCurrentPageContent/updateVersionLabel (which
 * PageServiceMoveLanguageTest covers). This locks the delegation, the per-method
 * exception + logging behaviour, and restorePageVersion's id-resolution BEFORE
 * the VERSION/HISTORY carve, so the pins prove behaviour not location.
 *
 * The PageVersionService engine is injected as a mock so the delegation and the
 * return pass-through are observable without a real IVersionManager.
 */
class PageServiceVersionTest extends TestCase {

    use BuildsPageService;

    private function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        return $file;
    }

    private function makeFolder(string $path, array $children): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new NotFoundException($path . '/' . $p);
        });
        return $folder;
    }

    /**
     * A PageService over one language folder `en` holding page `about`
     * (uniqueId page-v1), with the given PageVersionService engine mock injected.
     *
     * @return array{0: PageService, 1: File} the service and the page file
     */
    private function makeService(PageVersionService $engine, ?LoggerInterface $logger = null): array {
        $file = $this->makeFile('/IntraVox/en/about.json',
            ['uniqueId' => 'page-v1', 'title' => 'About', 'name' => 'About', 'widgets' => []]);
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $file,
            'about' => $this->makeFolder('/IntraVox/en/about', []),
        ]);
        $base = $this->makeFolder('/IntraVox', ['en' => $en]);

        // Built through the real DI ctor (fase-3); the inert PageCacheInvalidator
        // no-ops clearCache.
        $svc = $this->buildRealPageService([
            'userId' => 'tester',
            'logger' => $logger ?? $this->createMock(LoggerInterface::class),
            'pageVersionService' => $engine,
            'folderContext' => $this->fakeFolderContext(
                readLanguageFolder: $en,
                intraVox: $base,
                languageFolder: $en,
                userLanguage: 'en'
            ),
        ]);
        return [$svc, $file];
    }

    // -------------------------------------------------------- getPageVersions

    public function testGetPageVersionsDelegatesToListForFile(): void {
        $engine = $this->createMock(PageVersionService::class);
        [$svc, $file] = $this->makeService($engine);

        $engine->expects($this->once())
            ->method('listForFile')
            ->with($file)
            ->willReturn([['timestamp' => 111, 'label' => 'v1']]);

        $this->assertSame(
            [['timestamp' => 111, 'label' => 'v1']],
            $svc->getPageVersions('page-v1'),
            'the engine result passes through unchanged'
        );
    }

    public function testGetPageVersionsMissLogsWarningAndThrows(): void {
        $engine = $this->createMock(PageVersionService::class);
        $logger = $this->createMock(LoggerInterface::class);
        [$svc] = $this->makeService($engine, $logger);

        $logger->expects($this->once())
            ->method('warning')
            ->with('[getPageVersions] Page not found: page-nope');
        $engine->expects($this->never())->method('listForFile');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Page not found: page-nope');
        $svc->getPageVersions('page-nope');
    }

    // ------------------------------------------------------ getVersionContent

    public function testGetVersionContentDelegatesToContentAtTimestamp(): void {
        $engine = $this->createMock(PageVersionService::class);
        [$svc, $file] = $this->makeService($engine);

        $engine->expects($this->once())
            ->method('contentAtTimestamp')
            ->with($file, 12345)
            ->willReturn(['title' => 'About', 'content' => '{}', 'rawContent' => '{}']);

        $this->assertSame(
            ['title' => 'About', 'content' => '{}', 'rawContent' => '{}'],
            $svc->getVersionContent('page-v1', 12345)
        );
    }

    /** getVersionContent throws on a miss but — unlike getPageVersions — never logs. */
    public function testGetVersionContentMissThrowsWithoutLogging(): void {
        $engine = $this->createMock(PageVersionService::class);
        $logger = $this->createMock(LoggerInterface::class);
        [$svc] = $this->makeService($engine, $logger);

        $logger->expects($this->never())->method('warning');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Page not found: page-nope');
        $svc->getVersionContent('page-nope', 12345);
    }

    // ----------------------------------------------------- restorePageVersion

    public function testRestorePageVersionMergesFolderNameAsIdFirst(): void {
        $engine = $this->createMock(PageVersionService::class);
        [$svc, $file] = $this->makeService($engine);

        $engine->expects($this->once())
            ->method('restoreToTimestamp')
            ->with($file, $this->isInstanceOf(Folder::class), 999)
            ->willReturn(['title' => 'About', 'widgets' => []]);

        $restored = $svc->restorePageVersion('page-v1', 999);

        // id is derived from the page folder's basename ('about') and prepended.
        $this->assertSame('about', $restored['id']);
        $this->assertSame('id', array_key_first($restored), 'the id key is merged first');
        $this->assertSame('About', $restored['title'], 'the restored data is preserved');
    }

    public function testRestorePageVersionMissThrowsWithoutLogging(): void {
        $engine = $this->createMock(PageVersionService::class);
        $logger = $this->createMock(LoggerInterface::class);
        [$svc] = $this->makeService($engine, $logger);

        $logger->expects($this->never())->method('warning');
        $engine->expects($this->never())->method('restoreToTimestamp');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Page not found: page-nope');
        $svc->restorePageVersion('page-nope', 999);
    }
}
