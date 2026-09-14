<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Compose\PageCompositionService;
use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCA\IntraVox\Service\Template\PageTemplateService;
use OCA\IntraVox\Service\Translation\TranslationGroupService;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * copyPage() is COMPOSE-domain composition: it reads the source (getPage/locate),
 * builds fresh copy data, delegates the write to createPage(), and copies the
 * source's media into the new page. This pins the observable contract — the
 * group/order strip, the media-copy (a memory-flagged gap), the draft status, and
 * the #90 own-language behaviour.
 *
 * Drives PageCompositionService DIRECTLY with stub createPage/getPage closures
 * (recording into $seenData/$seenParentPath) instead of subclass-overriding them.
 */
class PageCopyCompositionTest extends TestCase {

    use BuildsPageService;
    use BuildsPageRead;

    /** What the stub createPage closure last received. */
    private ?array $seenData = null;
    private ?string $seenParentPath = null;
    /** The /IntraVox base folder, so the locate closure walks cross-language (#90). */
    private ?Folder $base = null;

    private function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        return $file;
    }

    /** @param array<string,\OCP\Files\Node> $children */
    private function makeFolder(string $path, array $children = []): Folder {
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
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        return $folder;
    }

    /**
     * A page folder /IntraVox/{lang}/{slug} holding {slug}.json + a _media folder.
     */
    private function pageFolderWithMedia(string $lang, string $slug, array $json): Folder {
        $path = "/IntraVox/$lang/$slug";
        $media = $this->makeFolder("$path/_media");
        return $this->makeFolder($path, [
            "$slug.json" => $this->makeFile("$path/$slug.json", $json),
            '_media' => $media,
        ]);
    }

    /**
     * Build a copyPage spy: createPage captures its ($data, $parentPath); getPage
     * echoes the created page; the mediaService records copyPageMedia calls; the
     * seams point at the given language folders.
     *
     * @param array<string,Folder> $languages code => language folder under /IntraVox
     * @param PageMediaService $mediaSpy explicit media service (records copy calls)
     */
    private function makeSpy(array $languages, string $writeLang, PageMediaService $mediaSpy): PageCompositionService {
        $this->seenData = null;
        $this->seenParentPath = null;
        $byName = [];
        foreach ($languages as $code => $f) {
            $byName[$code] = $f;
        }
        $base = $this->makeFolder('/IntraVox', $byName);
        $this->base = $base;
        $writeFolder = $languages[$writeLang];

        // copyPage resolves its write-target ($writeFolder) via
        // folders()->languageFolder() and walks cross-language via the injected
        // PageLocator (self-sourced root). The template/translation engines are unused
        // by copyPage; the html sanitizer + id utils are real; media records copies.
        return new PageCompositionService(
            $this->createMock(PageTemplateService::class),
            $this->createMock(TranslationGroupService::class),
            $mediaSpy,
            $this->doubleOrBuild(HtmlSanitizer::class),
            new PageIdUtils(),
            $this->fakeFolderContext(intraVox: $base, languageFolder: $writeFolder),
            'tester',
            $this->createMock(\Psr\Log\LoggerInterface::class),
            $this->fakeCacheInvalidator(),
            // copyPage's closing re-fetch echoes the freshly created page — the same
            // role the old getPage closure ($this->seenData ?? []) played. Lazy so it
            // reads $seenData set during the copyPage call.
            $this->fakePageReadFrom(fn(string $id): array => $this->seenData ?? []),
            // fase-7: copyPage self-sources the #90 cross-language locate via a real
            // PageLocator against the fixture tree (mocked index → folder-walk path).
            new \OCA\IntraVox\Service\Locator\PageLocator(
                $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
                $this->createMock(\Psr\Log\LoggerInterface::class)
            ),
            // fase-7 T6: findPageFolder is self-sourced. A fresh empty PageCacheService
            // → cache-miss → the locate-walk runs and finds nothing (the stub createPage
            // never writes the created folder to the fixture tree), so findPageFolder
            // returns null — byte-identical to the old `fn($id) => null` closure.
            new \OCA\IntraVox\Service\Cache\PageCacheService()
        );
    }

    /**
     * Drive copyPage with stub createPage/getPage closures (recording into
     * $seenData/$seenParentPath). The #90 cross-language locate and findPageFolder
     * (media-source resolve) are self-sourced by the service via the injected real
     * PageLocator + PageCacheService against the fixture tree; getPage echoes the
     * created data.
     */
    private function copy(PageCompositionService $svc, string $sourceUniqueId, ?string $targetParentId = null, ?string $newTitle = null): array {
        return $svc->copyPage(
            $sourceUniqueId,
            $targetParentId,
            $newTitle,
            function (array $data, ?string $parentPath = null): array {
                $this->seenData = $data;
                $this->seenParentPath = $parentPath;
                return $data; // carries the fresh uniqueId copyPage set
            }
        );
    }

    public function testCopyStripsGroupAndOrderAndIsDraftWithFreshIdentity(): void {
        $sourceJson = [
            'uniqueId' => 'page-src', 'title' => 'Handbook',
            'translationGroup' => 'tg-old', 'order' => 3, 'status' => 'published',
            'layout' => ['rows' => []],
        ];
        $en = $this->makeFolder('/IntraVox/en', [
            'handbook' => $this->pageFolderWithMedia('en', 'handbook', $sourceJson),
        ]);

        $media = $this->createMock(PageMediaService::class);
        $svc = $this->makeSpy(['en' => $en], 'en', $media);

        $this->copy($svc, 'page-src');

        $seen = $this->seenData;
        $this->assertArrayNotHasKey('translationGroup', $seen, 'a copy must not inherit the group');
        $this->assertArrayNotHasKey('order', $seen, 'a copy must not inherit sibling order');
        $this->assertSame('draft', $seen['status'], 'a copy starts as draft');
        $this->assertMatchesRegularExpression('/^page-/', $seen['uniqueId']);
        $this->assertNotSame('page-src', $seen['uniqueId'], 'a copy gets a fresh uniqueId');
        $this->assertSame('Handbook (copy)', $seen['title']);
    }

    public function testCopyCopiesSourceMediaIntoTheNewPage(): void {
        // The memory-flagged gap: copyPage must copy the source _media into the copy.
        $sourceJson = ['uniqueId' => 'page-src', 'title' => 'Docs', 'layout' => ['rows' => []]];
        $sourcePage = $this->pageFolderWithMedia('en', 'docs', $sourceJson);
        $en = $this->makeFolder('/IntraVox/en', ['docs' => $sourcePage]);

        $media = $this->createMock(PageMediaService::class);
        // copyPage -> copyPageMedia -> media()->copyPageMedia(sourceFolder, newPageFolder, 'copyPage')
        $media->expects($this->once())
            ->method('copyPageMedia')
            ->with(
                $this->identicalTo($sourcePage),
                $this->anything(),
                'copyPage'
            );

        $svc = $this->makeSpy(['en' => $en], 'en', $media);
        $this->copy($svc, 'page-src');
    }

    public function testCopyOfALanguageRootPageStaysInItsOwnLanguage(): void {
        // #90: an EN page copied by a DE user must land in en/, not de/. The source
        // sits at the language ROOT (dirname === '.'), so the parent path must fall
        // back to the source's own language, never the copier's.
        $sourceJson = ['uniqueId' => 'page-src', 'title' => 'Root', 'layout' => ['rows' => []]];
        $enPage = $this->pageFolderWithMedia('en', 'root', $sourceJson);
        $en = $this->makeFolder('/IntraVox/en', ['root' => $enPage]);
        $de = $this->makeFolder('/IntraVox/de', []);

        $media = $this->createMock(PageMediaService::class);
        // The DE user's write/read seam is de/, but the source resolves cross-language to en/.
        $svc = $this->makeSpy(['en' => $en, 'de' => $de], 'de', $media);
        // Source located via the base folder scan; drive by uniqueId across languages.
        $this->copy($svc, 'page-src');

        $this->assertSame('en', $this->seenParentPath, 'a root-page copy stays in the source language (en), not the copier de');
    }

    public function testCopyOfATrueLanguageRootPageFallsBackToItsOwnLanguage(): void {
        // The dirname === '.' fallback branch specifically: the source's OWN folder
        // IS the language root (/IntraVox/en), so relativePathFromRoot is just 'en'
        // and dirname('en') === '.'. A DE user copying it must still land in en/ via
        // languageOfFolder(source), never in the copier's de/. (Without the fallback
        // this is the pre-#90 bug: parentPath null -> copier's own language.)
        $sourceJson = ['uniqueId' => 'page-src', 'title' => 'Home', 'isHome' => true, 'layout' => ['rows' => []]];
        $en = $this->makeFolder('/IntraVox/en', [
            'home.json' => $this->makeFile('/IntraVox/en/home.json', $sourceJson),
        ]);
        $de = $this->makeFolder('/IntraVox/de', []);

        $media = $this->createMock(PageMediaService::class);
        $svc = $this->makeSpy(['en' => $en, 'de' => $de], 'de', $media);

        // The injected real PageLocator resolves the source across languages: the
        // primary (de write-folder) misses, the walk finds page-src's home.json in
        // en/, so the located folder IS the en language root itself (dirname '.').
        $svc->copyPage(
            'page-src',
            null,
            null,
            function (array $data, ?string $parentPath = null): array {
                $this->seenData = $data;
                $this->seenParentPath = $parentPath;
                return $data;
            }
        );

        $this->assertSame('en', $this->seenParentPath, 'a true language-root page copy falls back to its own language (en), not the copier de');
    }

    public function testNewTitleOverridesTheCopySuffix(): void {
        $sourceJson = ['uniqueId' => 'page-src', 'title' => 'Original', 'layout' => ['rows' => []]];
        $en = $this->makeFolder('/IntraVox/en', [
            'original' => $this->pageFolderWithMedia('en', 'original', $sourceJson),
        ]);

        $media = $this->createMock(PageMediaService::class);
        $svc = $this->makeSpy(['en' => $en], 'en', $media);

        $this->copy($svc, 'page-src', null, 'My New Name');

        $this->assertSame('My New Name', $this->seenData['title'], 'an explicit newTitle wins over "… (copy)"');
    }
}
