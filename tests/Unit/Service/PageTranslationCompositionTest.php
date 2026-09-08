<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Translation\TranslationGroupService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * createTranslation() is TRANSLATE-domain composition: it reads the source,
 * refuses the invalid cases, mints/assigns a translation group, builds fresh
 * draft copy data mirrored into the target language tree, delegates the write to
 * createPage(), and copies the source's media. PageTranslationGroupTest covers
 * the link/unlink group mechanics; this pins createTranslation's own contract —
 * the refusal guards and the group/parent/media composition — before any TRANSLATE
 * hardening, using the proven subclass spy (createPage captured, seams overridden).
 */
class PageTranslationCompositionTest extends TestCase {

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
    private function makeFolder(string $path, array $children = [], bool $creatable = true): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('isCreatable')->willReturn($creatable);
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        return $folder;
    }

    /** A source page folder /IntraVox/{lang}/{slug} holding {slug}.json + _media. */
    private function sourcePage(string $lang, string $slug, array $json): Folder {
        $path = "/IntraVox/$lang/$slug";
        return $this->makeFolder($path, [
            "$slug.json" => $this->makeFile("$path/$slug.json", $json),
            '_media' => $this->makeFolder("$path/_media"),
        ]);
    }

    /**
     * Build a createTranslation spy. The en/ tree holds the source; $languages
     * maps every language code to its root folder under /IntraVox (so the target
     * language folder resolves via getIntraVoxFolder()->get($lang)).
     *
     * @param array<string,Folder> $languages code => language root
     */
    private function makeSpy(
        array $languages,
        TranslationGroupService $groups,
        PageMediaService $media,
        bool $existingGroup = false
    ): PageService {
        $en = $languages['en'];
        $base = $this->makeFolder('/IntraVox', $languages);

        // createTranslation resolves readLanguageFolder ($en) and intraVox->get($lang)
        // ($base) through the injected FolderContext, so all folder seams are gone.
        // The createPage spy is orthogonal to folders.
        $svc = new class() extends PageService {
            public ?array $seenData = null;
            public ?string $seenParentPath = null;
            public function __construct() {
            }
            public function createPage(array $data, ?string $parentPath = null): array {
                $this->seenData = $data;
                $this->seenParentPath = $parentPath;
                return $data;
            }
            public function clearCache(): void {
            }
        };

        $this->injectPageServiceDependencies($svc, [
            'userId' => 'tester',
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $this->createMock(\OCA\IntraVox\Service\LanguageService::class),
            'translationGroupService' => $groups,
            'pageMediaService' => $media,
            'folderContext' => $this->fakeFolderContext(
                readLanguageFolder: $en,
                intraVox: $base
            ),
        ]);
        return $svc;
    }

    private function noopGroups(): TranslationGroupService {
        $groups = $this->createMock(TranslationGroupService::class);
        $groups->method('groupHasLanguage')->willReturn(false);
        $groups->method('newGroupId')->willReturn('tg-fresh-0000-0000-0000-000000000000');
        return $groups;
    }

    // ------------------------------------------------------------ refusal guards

    public function testMalformedLanguageCodeIsRefused(): void {
        $en = $this->sourcePage('en', 'about', ['uniqueId' => 'page-src', 'title' => 'About']);
        $svc = $this->makeSpy(['en' => $this->makeFolder('/IntraVox/en', ['about' => $en])], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language code');
        $svc->createTranslation('page-src', 'XX');
    }

    public function testTranslatingIntoTheSourceOwnLanguageIsRefused(): void {
        // Source lives in en/; translating to 'en' is a no-op that must be refused.
        $enPage = $this->sourcePage('en', 'about', ['uniqueId' => 'page-src', 'title' => 'About']);
        $en = $this->makeFolder('/IntraVox/en', ['about' => $enPage]);
        $svc = $this->makeSpy(['en' => $en], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already in that language');
        $svc->createTranslation('page-src', 'en');
    }

    public function testAMissingTargetLanguageFolderIsRefused(): void {
        // 'de' has no content folder -> must refuse rather than create one silently.
        $enPage = $this->sourcePage('en', 'about', ['uniqueId' => 'page-src', 'title' => 'About']);
        $en = $this->makeFolder('/IntraVox/en', ['about' => $enPage]);
        $svc = $this->makeSpy(['en' => $en], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no content folder yet');
        $svc->createTranslation('page-src', 'de');
    }

    public function testAReadOnlyTargetLanguageYields403(): void {
        $enPage = $this->sourcePage('en', 'about', ['uniqueId' => 'page-src', 'title' => 'About']);
        $en = $this->makeFolder('/IntraVox/en', ['about' => $enPage]);
        $de = $this->makeFolder('/IntraVox/de', [], creatable: false); // read-only target
        $svc = $this->makeSpy(['en' => $en, 'de' => $de], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $this->expectException(ForbiddenException::class);
        $svc->createTranslation('page-src', 'de');
    }

    public function testDuplicateLanguageInTheGroupIsRefused(): void {
        // The source already belongs to a group that HOLDS the target language.
        $enPage = $this->sourcePage('en', 'about', ['uniqueId' => 'page-src', 'title' => 'About', 'translationGroup' => 'tg-existing']);
        $en = $this->makeFolder('/IntraVox/en', ['about' => $enPage]);
        $de = $this->makeFolder('/IntraVox/de', []);

        $groups = $this->createMock(TranslationGroupService::class);
        $groups->method('groupHasLanguage')->with('tg-existing', 'de')->willReturn(true);
        $svc = $this->makeSpy(['en' => $en, 'de' => $de], $groups, $this->createMock(PageMediaService::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists in that language');
        $svc->createTranslation('page-src', 'de');
    }

    public function testUnknownSourceIsRefused(): void {
        $en = $this->makeFolder('/IntraVox/en', []);
        $svc = $this->makeSpy(['en' => $en], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $this->expectException(PageNotFoundException::class);
        $svc->createTranslation('page-nope', 'de');
    }

    // ------------------------------------------------------------ composition

    public function testTranslationIsADraftCopyWithFreshIdentityInTheTargetGroup(): void {
        $enPage = $this->sourcePage('en', 'about', [
            'uniqueId' => 'page-src', 'title' => 'About', 'order' => 5, 'status' => 'published',
            'layout' => ['rows' => []],
        ]);
        $en = $this->makeFolder('/IntraVox/en', ['about' => $enPage]);
        $de = $this->makeFolder('/IntraVox/de', []);
        $svc = $this->makeSpy(['en' => $en, 'de' => $de], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $svc->createTranslation('page-src', 'de');

        $seen = $svc->seenData;
        $this->assertSame('draft', $seen['status'], 'a fresh translation is a draft');
        $this->assertArrayNotHasKey('order', $seen, 'a translation does not inherit sibling order');
        $this->assertNotSame('page-src', $seen['uniqueId'], 'a translation gets a fresh uniqueId');
        $this->assertSame('tg-fresh-0000-0000-0000-000000000000', $seen['translationGroup'], 'the minted group is assigned');
    }

    public function testTranslationLandsInTheTargetLanguageRoot(): void {
        // Source at the en/ root -> the translation's parent path is just 'de'.
        $enPage = $this->sourcePage('en', 'about', ['uniqueId' => 'page-src', 'title' => 'About', 'layout' => ['rows' => []]]);
        $en = $this->makeFolder('/IntraVox/en', ['about' => $enPage]);
        $de = $this->makeFolder('/IntraVox/de', []);
        $svc = $this->makeSpy(['en' => $en, 'de' => $de], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $svc->createTranslation('page-src', 'de');

        $this->assertSame('de', $svc->seenParentPath, 'a root-level source translates into the target language root');
    }

    public function testTranslationCopiesTheSourceMedia(): void {
        $enPage = $this->sourcePage('en', 'about', ['uniqueId' => 'page-src', 'title' => 'About', 'layout' => ['rows' => []]]);
        $en = $this->makeFolder('/IntraVox/en', ['about' => $enPage]);
        $de = $this->makeFolder('/IntraVox/de', []);

        $media = $this->createMock(PageMediaService::class);
        $media->expects($this->once())
            ->method('copyPageMedia')
            ->with($this->identicalTo($enPage), $this->anything(), 'createTranslation');

        $svc = $this->makeSpy(['en' => $en, 'de' => $de], $this->noopGroups(), $media);
        $svc->createTranslation('page-src', 'de');
    }
}
