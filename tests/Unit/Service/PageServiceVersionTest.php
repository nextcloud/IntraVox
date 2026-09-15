<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Version\PageVersionService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsFolderFixtures;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsNodeFixtures;
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

    use BuildsFolderFixtures;

    use BuildsNodeFixtures;
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
     * The version domain service over one language folder `en` holding page
     * `about` (uniqueId page-v1), with the given PageVersionService engine mock.
     *
     * These methods were delegators on PageService (pure forwards to
     * versionDomain()); fase-4 deletes the delegators, so this drives
     * PageVersionDomainService directly — the real owner of the cross-language
     * locate + per-method exception/logging the assertions below pin.
     *
     * @return array{0: \OCA\IntraVox\Service\Version\PageVersionDomainService, 1: File}
     */
    private function makeService(PageVersionService $engine, ?LoggerInterface $logger = null): array {
        $file = $this->makeFile('/IntraVox/en/about.json',
            ['uniqueId' => 'page-v1', 'title' => 'About', 'name' => 'About', 'widgets' => []]);
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $file,
            'about' => $this->makeFolder('/IntraVox/en/about', []),
        ]);
        $base = $this->makeFolder('/IntraVox', ['en' => $en]);

        $svc = new \OCA\IntraVox\Service\Version\PageVersionDomainService(
            $engine,
            $logger ?? $this->createMock(LoggerInterface::class),
            $this->fakeFolderContext(
                readLanguageFolder: $en,
                intraVox: $base,
                languageFolder: $en,
                userLanguage: 'en'
            ),
            new \OCA\IntraVox\Service\Locator\PageLocator(
                $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
                $logger ?? $this->createMock(LoggerInterface::class)
            ),
            new \OCA\IntraVox\Service\Util\PageIdUtils()
        );
        return [$svc, $file];
    }

    /**
     * The version domain service over a cross-language fixture: an English page in
     * `en` while the user's profile language is `de`. Drives the
     * getCurrentPageContent / updateVersionLabel foreign-language locate directly
     * (migrated from the retired PageServiceMoveLanguageTest, whose #90 movePage
     * guards now live in PageStructureServiceTest — only these two version-domain
     * locate tests remained, and they belong beside their sibling delegators here).
     *
     * @param array<int,Folder> $allLanguages every language folder under /IntraVox
     */
    private function versionDomainAcrossLanguages(Folder $userLanguageFolder, array $allLanguages): \OCA\IntraVox\Service\Version\PageVersionDomainService {
        $byLang = [];
        foreach ($allLanguages as $l) {
            $byLang[$l->getName()] = $l;
        }
        $base = $this->createMock(Folder::class);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willReturn($allLanguages);
        $base->method('get')->willReturnCallback(function ($p) use ($byLang) {
            if (isset($byLang[$p])) {
                return $byLang[$p];
            }
            throw new NotFoundException($p);
        });
        return new \OCA\IntraVox\Service\Version\PageVersionDomainService(
            $this->createMock(PageVersionService::class),
            $this->createMock(LoggerInterface::class),
            $this->fakeFolderContext(
                readLanguageFolder: $userLanguageFolder,
                intraVox: $base,
                userLanguage: 'de',
                primaryLanguage: 'en',
                languageFolder: $userLanguageFolder
            ),
            new \OCA\IntraVox\Service\Locator\PageLocator(
                $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
                $this->createMock(LoggerInterface::class)
            ),
            new \OCA\IntraVox\Service\Util\PageIdUtils()
        );
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

    // ----------------------------------------------- foreign-language operation-locate

    /**
     * getCurrentPageContent() had no uniqueId branch at all — it passed a
     * page-… id straight to findPageById() — and no cross-language fallback.
     * The "compare with current" panel in version history therefore failed on
     * every modern id, and on any foreign-language page.
     */
    public function testCurrentPageContentResolvesForeignLanguageByUniqueId(): void {
        $pageJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-cur1', 'title' => 'About', 'name' => 'About', 'widgets' => []]
        );
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $pageJson,
            'about' => $this->makeFolder('/IntraVox/en/about', []),
        ]);
        $de = $this->makeFolder('/IntraVox/de', []);

        $svc = $this->versionDomainAcrossLanguages($de, [$de, $en]);
        $content = $svc->getCurrentPageContent('page-cur1');

        $this->assertStringContainsString(
            'page-cur1',
            $content['rawContent'],
            'the current content of a foreign-language page must be readable'
        );
    }

    /** The same lookup gap in updateVersionLabel's existence check. */
    public function testUpdateVersionLabelFindsForeignLanguagePage(): void {
        $pageJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-lbl1', 'title' => 'About', 'widgets' => []]
        );
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $pageJson,
            'about' => $this->makeFolder('/IntraVox/en/about', []),
        ]);
        $de = $this->makeFolder('/IntraVox/de', []);

        $svc = $this->versionDomainAcrossLanguages($de, [$de, $en]);

        // The page must be FOUND: resolution is what was broken. It then fails
        // later on the version manager, which this fixture does not provide —
        // so anything other than "Page not found" proves the lookup succeeded.
        try {
            $svc->updateVersionLabel('page-lbl1', 12345, 'Release');
            $this->addToAssertionCount(1);
        } catch (\Throwable $e) {
            $this->assertStringNotContainsString(
                'Page not found',
                $e->getMessage(),
                'the page must resolve across languages; only later steps may fail'
            );
        }
    }
}
