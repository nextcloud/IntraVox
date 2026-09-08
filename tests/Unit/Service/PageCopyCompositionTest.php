<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * copyPage() is TEMPLATE-domain composition: it reads the source (getPage/locate),
 * builds fresh copy data, delegates the write to createPage(), and copies the
 * source's media into the new page. Only the group-strip + order-strip were
 * pinned (PageSlugUniquenessTest). This pins the rest of the observable contract
 * before the TEMPLATE hardening — the media-copy (a memory-flagged gap), the
 * draft status, and the #90 own-language behaviour — using the proven subclass
 * spy: createPage/getPage are captured, the seams overridden.
 */
class PageCopyCompositionTest extends TestCase {

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
    private function makeSpy(array $languages, string $writeLang, PageMediaService $mediaSpy): PageService {
        $byName = [];
        foreach ($languages as $code => $f) {
            $byName[$code] = $f;
        }
        $base = $this->makeFolder('/IntraVox', $byName);
        $writeFolder = $languages[$writeLang];

        // copyPage resolves its write-target ($writeFolder) via
        // folders()->languageFolder() and walks cross-language via
        // locatePageAnyLanguage -> rootClosure() -> getIntraVoxFolder ($base, kept).
        // createPage/getPage spies + clearCache stay (orthogonal to folders).
        $svc = new class($base) extends PageService {
            public ?array $seenData = null;
            public ?string $seenParentPath = null;
            private Folder $baseFolder;
            public function __construct(Folder $baseFolder) {
                $this->baseFolder = $baseFolder;
            }
            protected function getIntraVoxFolder() {
                return $this->baseFolder;
            }
            public function createPage(array $data, ?string $parentPath = null): array {
                $this->seenData = $data;
                $this->seenParentPath = $parentPath;
                return $data; // carries the fresh uniqueId copyPage set
            }
            public function getPage(string $id): array {
                return $this->seenData ?? [];
            }
            public function clearCache(): void {
            }
        };

        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturnCallback(
            fn(string $c) => in_array($c, ['en', 'de', 'nl', 'fr'], true)
        );
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        $this->injectPageServiceDependencies($svc, [
            'userId' => 'tester',
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $languageService,
            'pageMediaService' => $mediaSpy,
            'folderContext' => $this->fakeFolderContext(intraVox: $base, languageFolder: $writeFolder),
        ]);
        return $svc;
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

        $svc->copyPage('page-src');

        $seen = $svc->seenData;
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
        $svc->copyPage('page-src');
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
        $svc->copyPage('page-src');

        $this->assertSame('en', $svc->seenParentPath, 'a root-page copy stays in the source language (en), not the copier de');
    }

    public function testNewTitleOverridesTheCopySuffix(): void {
        $sourceJson = ['uniqueId' => 'page-src', 'title' => 'Original', 'layout' => ['rows' => []]];
        $en = $this->makeFolder('/IntraVox/en', [
            'original' => $this->pageFolderWithMedia('en', 'original', $sourceJson),
        ]);

        $media = $this->createMock(PageMediaService::class);
        $svc = $this->makeSpy(['en' => $en], 'en', $media);

        $svc->copyPage('page-src', null, 'My New Name');

        $this->assertSame('My New Name', $svc->seenData['title'], 'an explicit newTitle wins over "… (copy)"');
    }
}
