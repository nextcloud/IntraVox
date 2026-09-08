<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Compose\PageCompositionService;
use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCA\IntraVox\Service\Template\PageTemplateService;
use OCA\IntraVox\Service\Translation\TranslationGroupService;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * createTranslation() is COMPOSE-domain composition: it reads the source,
 * refuses the invalid cases, mints/assigns a translation group, builds fresh
 * draft copy data mirrored into the target language tree, delegates the write to
 * createPage(), and copies the source's media. PageTranslationGroupTest covers
 * the link/unlink group mechanics; this pins createTranslation's own contract —
 * the refusal guards and the group/parent/media composition.
 *
 * Drives PageCompositionService DIRECTLY with a stub createPage closure (which
 * records into $seenData/$seenParentPath) instead of subclass-overriding
 * createPage on PageService — the composition IS what this test targets.
 */
class PageTranslationCompositionTest extends TestCase {

    use BuildsPageService;

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
    ): PageCompositionService {
        $this->seenData = null;
        $this->seenParentPath = null;
        $en = $languages['en'];
        $base = $this->makeFolder('/IntraVox', $languages);
        $this->base = $base;

        // createTranslation resolves readLanguageFolder ($en) and intraVox->get($lang)
        // ($base) through the injected FolderContext. The template service is unused
        // by createTranslation; the html sanitizer + id utils are real.
        return new PageCompositionService(
            $this->createMock(PageTemplateService::class),
            $groups,
            $media,
            $this->doubleOrBuild(HtmlSanitizer::class),
            new PageIdUtils(),
            $this->fakeFolderContext(readLanguageFolder: $en, intraVox: $base),
            'tester',
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
    }

    /**
     * Drive createTranslation with a stub createPage closure that records the data
     * it is handed (replacing the old subclass createPage spy), plus the page-lookup
     * / group / cache closures the PageService delegator would supply. locate uses a
     * real PageLocator against the fixture tree, so the #90 cross-language walk runs.
     */
    private function translate(PageCompositionService $svc, string $sourceUniqueId, string $language, ?string $title = null): array {
        $locator = new \OCA\IntraVox\Service\Locator\PageLocator(
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
        return $svc->createTranslation(
            $sourceUniqueId,
            $language,
            $title,
            function (array $data, ?string $parentPath = null): array {
                $this->seenData = $data;
                $this->seenParentPath = $parentPath;
                return $data;
            },
            fn(Folder $folder, string $uid): ?array => $locator->locatePageAnyLanguage(fn() => $this->base, $folder, $uid),
            fn(string $id): ?Folder => null,
            function (array $result, string $group): void {
            },
            function (): void {
            }
        );
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
        $this->translate($svc, 'page-src', 'XX');
    }

    public function testTranslatingIntoTheSourceOwnLanguageIsRefused(): void {
        // Source lives in en/; translating to 'en' is a no-op that must be refused.
        $enPage = $this->sourcePage('en', 'about', ['uniqueId' => 'page-src', 'title' => 'About']);
        $en = $this->makeFolder('/IntraVox/en', ['about' => $enPage]);
        $svc = $this->makeSpy(['en' => $en], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already in that language');
        $this->translate($svc, 'page-src', 'en');
    }

    public function testAMissingTargetLanguageFolderIsRefused(): void {
        // 'de' has no content folder -> must refuse rather than create one silently.
        $enPage = $this->sourcePage('en', 'about', ['uniqueId' => 'page-src', 'title' => 'About']);
        $en = $this->makeFolder('/IntraVox/en', ['about' => $enPage]);
        $svc = $this->makeSpy(['en' => $en], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no content folder yet');
        $this->translate($svc, 'page-src', 'de');
    }

    public function testAReadOnlyTargetLanguageYields403(): void {
        $enPage = $this->sourcePage('en', 'about', ['uniqueId' => 'page-src', 'title' => 'About']);
        $en = $this->makeFolder('/IntraVox/en', ['about' => $enPage]);
        $de = $this->makeFolder('/IntraVox/de', [], creatable: false); // read-only target
        $svc = $this->makeSpy(['en' => $en, 'de' => $de], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $this->expectException(ForbiddenException::class);
        $this->translate($svc, 'page-src', 'de');
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
        $this->translate($svc, 'page-src', 'de');
    }

    public function testUnknownSourceIsRefused(): void {
        $en = $this->makeFolder('/IntraVox/en', []);
        $svc = $this->makeSpy(['en' => $en], $this->noopGroups(), $this->createMock(PageMediaService::class));

        $this->expectException(PageNotFoundException::class);
        $this->translate($svc, 'page-nope', 'de');
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

        $this->translate($svc, 'page-src', 'de');

        $seen = $this->seenData;
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

        $this->translate($svc, 'page-src', 'de');

        $this->assertSame('de', $this->seenParentPath, 'a root-level source translates into the target language root');
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
        $this->translate($svc, 'page-src', 'de');
    }
}
