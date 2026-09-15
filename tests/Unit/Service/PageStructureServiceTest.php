<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Exception\CrossLanguageMoveException;
use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Cache\PageCacheInvalidator;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\Structure\PageStructureService;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes Structure/PageStructureService::movePage DIRECTLY —
 * `new PageStructureService(...)` — rather than through the thin PageService
 * movePage delegator. This is the coverage that must exist BEFORE PageService can
 * be deleted (dissolution plan, sessie 1): today the move guards are pinned by
 * PageMoveGuardTest + PageServiceMoveLanguageTest, both routed through PageService
 * (reflection `structureService()`), so they would evaporate with the god-class.
 *
 * The load-bearing part is the CLOSURE CONTRACT. PageService injects three
 * $this-bound closures into movePage; a container factory (not PageService) must
 * soon reproduce them. So the tests build the SAME closures the delegator builds:
 *   - languageFolderOfPageResult: the page's OWN language folder from its result
 *     (FolderContext.languageOfFolder → intraVox()->get(lang)), the #90 anchor that
 *     keeps a move inside the source's language.
 *   - languageDisplayName: the human language name for the cross-language refusal
 *     message (available-languages lookup, '(' variant stripped, code-upper fallback).
 *   - validateDepth: real PagePathHelper depth math vs the always-5 cap.
 * and pin every movePage refusal + the happy paths + the index repath, migrated
 * verbatim from the two PageService-routed suites.
 *
 * makeFile/makeFolder/fakeFolderContext/fakeCacheInvalidator/fakeHomepageResolver
 * are the seam-agnostic fixture primitives shared with the PageService
 * characterization tests (they build real collaborators, not PageService).
 */
class PageStructureServiceTest extends TestCase {

    use BuildsPageService;

    /** Records every move() performed: [sourcePath => destinationPath]. */
    private array $moves = [];
    /** Index subtree repaths the service triggered. */
    private array $indexRepaths = [];

    protected function setUp(): void {
        $this->moves = [];
        $this->indexRepaths = [];
    }

    /**
     * A folder that records move() into $this->moves and can be made read-only /
     * non-creatable so the permission preflight is testable. (A local copy of the
     * harness makeFolder so move() lands in THIS test's $moves.)
     *
     * @param array<string,\OCP\Files\Node> $children name => node
     */
    private function moveFolder(
        string $path,
        array $children = [],
        bool $deletable = true,
        bool $creatable = true
    ): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('isDeletable')->willReturn($deletable);
        $folder->method('isCreatable')->willReturn($creatable);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new NotFoundException($path . '/' . $p);
        });
        $folder->method('move')->willReturnCallback(function ($dest) use ($path): void {
            $this->moves[$path] = $dest;
        });
        return $folder;
    }

    /** A page folder holding {slug}.json with the given uniqueId. */
    private function pageFolder(string $path, string $uniqueId): Folder {
        $slug = basename($path);
        return $this->moveFolder($path, [
            $slug . '.json' => $this->makeFile(
                $path . '/' . $slug . '.json',
                ['uniqueId' => $uniqueId, 'title' => ucfirst($slug)]
            ),
        ]);
    }

    /**
     * The base /IntraVox folder over the given language folders, resolving each by
     * name — the intraVox() root movePage walks for languageOfFolder /
     * relativePathFromRoot / the languageFolderOfPageResult closure.
     *
     * @param array<int,Folder> $languages
     */
    private function baseOver(array $languages): Folder {
        $byLang = [];
        foreach ($languages as $l) {
            $byLang[$l->getName()] = $l;
        }
        $base = $this->createMock(Folder::class);
        $base->method('getName')->willReturn('IntraVox');
        $base->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willReturn($languages);
        $base->method('get')->willReturnCallback(function ($p) use ($byLang) {
            if (isset($byLang[$p])) {
                return $byLang[$p];
            }
            throw new NotFoundException($p);
        });
        return $base;
    }

    /** A LanguageService reporting en/de/fr/nl available + the two display names. */
    private function languageService(): LanguageService {
        $ls = $this->createMock(LanguageService::class);
        $ls->method('isLanguageAvailable')->willReturnCallback(
            fn(string $code) => in_array($code, ['en', 'de', 'fr', 'nl'], true)
        );
        $ls->method('getPrimaryLanguage')->willReturn('en');
        $ls->method('getAvailableLanguages')->willReturn([
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'de', 'name' => 'Deutsch'],
        ]);
        return $ls;
    }

    /**
     * A real PageStructureService over the given closure substrate. Every
     * cross-language test builds its own FolderContext (the fixtures differ per
     * test) and hands it here so the service and the move() closures share the one
     * folders instance.
     */
    private function serviceOver(FolderContext $folders, ?PageIndexService $index = null): PageStructureService {
        return new PageStructureService(
            new PageIdUtils(),
            $index ?? $this->createMock(PageIndexService::class),
            $this->createMock(LoggerInterface::class),
            $folders,
            $this->fakeHomepageResolver(null),
            $this->fakeCacheInvalidator(),
            new PageLocator(
                $this->createMock(PageIndexService::class),
                $this->createMock(LoggerInterface::class)
            ),
        );
    }

    /**
     * Drive movePage with the same three closures PageService::movePage injects,
     * built here over the real FolderContext + LanguageService (byte-faithful to
     * the resident helpers languageFolderOfPageResult / languageDisplayName /
     * validateDepth).
     */
    private function move(
        PageStructureService $svc,
        FolderContext $folders,
        LanguageService $languageService,
        string $pageId,
        string $targetParentId
    ): void {
        $pathHelper = new PagePathHelper();

        $languageFolderOfPageResult = function (array $result) use ($folders): ?Folder {
            $folder = $result['folder'] ?? null;
            if (!($folder instanceof Folder)) {
                return null;
            }
            $language = $folders->languageOfFolder($folder);
            if ($language === null) {
                return null;
            }
            try {
                $candidate = $folders->intraVox()->get($language);
                return $candidate instanceof Folder ? $candidate : null;
            } catch (NotFoundException $e) {
                return null;
            }
        };

        $languageDisplayName = function (string $code) use ($languageService): string {
            try {
                foreach ($languageService->getAvailableLanguages() as $lang) {
                    if (($lang['code'] ?? '') === $code) {
                        $name = $lang['name'] ?? '';
                        if ($name === '') {
                            return strtoupper($code);
                        }
                        $baseName = trim(explode('(', $name)[0]);
                        return $baseName !== '' ? $baseName : $name;
                    }
                }
            } catch (\Throwable $e) {
                // cosmetic
            }
            return strtoupper($code);
        };

        $validateDepth = function (string $path) use ($pathHelper): void {
            $currentDepth = $pathHelper->calculateDepth($path);
            $maxDepth = 5;
            if ($currentDepth >= $maxDepth) {
                throw new \InvalidArgumentException(
                    "Cannot create child page: maximum nesting depth of {$maxDepth} would be exceeded"
                );
            }
        };

        $svc->movePage($pageId, $targetParentId, $languageFolderOfPageResult, $languageDisplayName, $validateDepth);
    }

    /**
     * Assemble a single-language (en/) service + its closure substrate, so a guard
     * test can drive move() with one call. Returns [service, folders, languageService].
     *
     * @param array<string,\OCP\Files\Node> $enChildren
     * @return array{0:PageStructureService,1:FolderContext,2:LanguageService}
     */
    private function enFixture(array $enChildren, ?string $homepageUniqueId = null): array {
        $en = $this->moveFolder('/IntraVox/en', $enChildren);
        $base = $this->baseOver([$en]);
        $ls = $this->languageService();
        $folders = $this->fakeFolderContext(readLanguageFolder: $en, intraVox: $base, languageFolder: $en);
        // Rebuild the service over the SAME folders instance the closures use.
        $svc = new PageStructureService(
            new PageIdUtils(),
            $this->createMock(PageIndexService::class),
            $this->createMock(LoggerInterface::class),
            $folders,
            $this->fakeHomepageResolver($homepageUniqueId),
            $this->fakeCacheInvalidator(),
            new PageLocator($this->createMock(PageIndexService::class), $this->createMock(LoggerInterface::class)),
        );
        return [$svc, $folders, $ls];
    }

    // ------------------------------------------------------------- refusal guards

    public function testTheHomeStringIdCanNeverBeMoved(): void {
        [$svc, $folders, $ls] = $this->enFixture([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The home page cannot be moved');
        $this->move($svc, $folders, $ls, 'home', '');
    }

    public function testTheConfiguredHomepageCanNeverBeMoved(): void {
        $about = $this->pageFolder('/IntraVox/en/about', 'page-home');
        [$svc, $folders, $ls] = $this->enFixture(['about' => $about], homepageUniqueId: 'page-home');

        try {
            $this->move($svc, $folders, $ls, 'page-home', '');
            $this->fail('the configured homepage must not be movable');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('HOMEPAGE_PROTECTED', $e->getMessage());
        }
        $this->assertSame([], $this->moves, 'a protected homepage move must not touch the filesystem');
    }

    public function testAMoveIntoItselfIsRefused(): void {
        $about = $this->pageFolder('/IntraVox/en/about', 'page-about');
        [$svc, $folders, $ls] = $this->enFixture(['about' => $about]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move a page into itself or its descendant');
        $this->move($svc, $folders, $ls, 'page-about', 'page-about');
    }

    public function testAMoveIntoADescendantIsRefused(): void {
        $child = $this->pageFolder('/IntraVox/en/about/child', 'page-child');
        $about = $this->moveFolder('/IntraVox/en/about', [
            'about.json' => $this->makeFile('/IntraVox/en/about/about.json', ['uniqueId' => 'page-about', 'title' => 'About']),
            'child' => $child,
        ]);
        [$svc, $folders, $ls] = $this->enFixture(['about' => $about]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move a page into itself or its descendant');
        $this->move($svc, $folders, $ls, 'page-about', 'page-child');
    }

    public function testAMoveAlreadyUnderTheTargetIsASilentNoOp(): void {
        $about = $this->pageFolder('/IntraVox/en/about', 'page-about');
        [$svc, $folders, $ls] = $this->enFixture(['about' => $about]);

        $this->move($svc, $folders, $ls, 'page-about', '');

        $this->assertSame([], $this->moves, 'a page already under the target parent must not be moved');
    }

    public function testUnknownPageStillReportsNotFound(): void {
        [$svc, $folders, $ls] = $this->enFixture([]);

        $this->expectException(PageNotFoundException::class);
        $this->move($svc, $folders, $ls, 'page-nope', '');
    }

    // --------------------------------------------------------- cross-language (#90)

    /**
     * THE regression: a move to root must land in the page's OWN language root,
     * never the user's. The user's profile language is de/, the page lives in en/.
     */
    public function testMoveToRootStaysInThePagesOwnLanguage(): void {
        $pageFolder = $this->moveFolder('/IntraVox/en/news/about', []);
        $newsFolder = $this->moveFolder('/IntraVox/en/news', [
            'about.json' => $this->makeFile('/IntraVox/en/news/about.json', ['uniqueId' => 'page-move1', 'title' => 'About']),
            'about' => $pageFolder,
        ]);
        $en = $this->moveFolder('/IntraVox/en', ['news' => $newsFolder]);
        $de = $this->moveFolder('/IntraVox/de', []);
        $base = $this->baseOver([$de, $en]);
        $ls = $this->languageService();
        // The user's write-target is de/; the page is resolved cross-language in en/.
        $folders = $this->fakeFolderContext(readLanguageFolder: $de, intraVox: $base, languageFolder: $de);
        $svc = $this->serviceOver($folders);

        $this->move($svc, $folders, $ls, 'page-move1', '');

        $this->assertArrayHasKey('/IntraVox/en/news/about', $this->moves, 'the page should have been moved');
        $this->assertSame(
            '/IntraVox/en/about',
            $this->moves['/IntraVox/en/news/about'],
            'a move to root must land in the page\'s OWN language root, never the user\'s'
        );
    }

    public function testMoveIntoAnotherLanguageIsRefused(): void {
        $de = $this->moveFolder('/IntraVox/de', [
            'ziele.json' => $this->makeFile('/IntraVox/de/ziele.json', ['uniqueId' => 'page-detarget', 'title' => 'Ziele']),
            'ziele' => $this->moveFolder('/IntraVox/de/ziele', []),
        ]);
        $en = $this->moveFolder('/IntraVox/en', [
            'about.json' => $this->makeFile('/IntraVox/en/about.json', ['uniqueId' => 'page-move2', 'title' => 'About']),
            'about' => $this->moveFolder('/IntraVox/en/about', []),
        ]);
        $base = $this->baseOver([$de, $en]);
        $ls = $this->languageService();
        $folders = $this->fakeFolderContext(readLanguageFolder: $de, intraVox: $base, languageFolder: $de);
        $svc = $this->serviceOver($folders);

        try {
            $this->move($svc, $folders, $ls, 'page-move2', 'page-detarget');
            $this->fail('a cross-language move must be refused');
        } catch (CrossLanguageMoveException $e) {
            $this->assertStringContainsString('English', $e->getMessage());
            $this->assertStringContainsString('Deutsch', $e->getMessage());
        }
        $this->assertSame([], $this->moves, 'nothing may be moved when the guard fires');
    }

    public function testMoveWithinTheSameLanguageStillWorks(): void {
        $en = $this->moveFolder('/IntraVox/en', [
            'about.json' => $this->makeFile('/IntraVox/en/about.json', ['uniqueId' => 'page-move3', 'title' => 'About']),
            'about' => $this->moveFolder('/IntraVox/en/about', []),
            'news.json' => $this->makeFile('/IntraVox/en/news.json', ['uniqueId' => 'page-entarget', 'title' => 'News']),
            'news' => $this->moveFolder('/IntraVox/en/news', []),
        ]);
        $de = $this->moveFolder('/IntraVox/de', []);
        $base = $this->baseOver([$de, $en]);
        $ls = $this->languageService();
        $folders = $this->fakeFolderContext(readLanguageFolder: $de, intraVox: $base, languageFolder: $de);
        $svc = $this->serviceOver($folders);

        $this->move($svc, $folders, $ls, 'page-move3', 'page-entarget');

        $this->assertSame(
            '/IntraVox/en/news/about',
            $this->moves['/IntraVox/en/about'] ?? null,
            'a same-language move must still relocate the page'
        );
    }

    // ------------------------------------------------------- permission preflight

    public function testMoveIsRefusedWhenSourceIsNotDeletable(): void {
        $pageFolder = $this->moveFolder('/IntraVox/en/news/about', [], deletable: false);
        $newsFolder = $this->moveFolder('/IntraVox/en/news', [
            'about.json' => $this->makeFile('/IntraVox/en/news/about.json', ['uniqueId' => 'page-move1', 'title' => 'About']),
            'about' => $pageFolder,
        ]);
        $en = $this->moveFolder('/IntraVox/en', ['news' => $newsFolder]);
        $de = $this->moveFolder('/IntraVox/de', []);
        $base = $this->baseOver([$de, $en]);
        $ls = $this->languageService();
        $folders = $this->fakeFolderContext(readLanguageFolder: $de, intraVox: $base, languageFolder: $de);
        $svc = $this->serviceOver($folders);

        $this->expectException(ForbiddenException::class);
        $this->move($svc, $folders, $ls, 'page-move1', '');
    }

    public function testMoveIsRefusedWhenDestinationIsNotCreatable(): void {
        // Source and destination in the SAME language, so the language guard cannot
        // be what rejects this — the permission check on the non-creatable root must.
        $newsFolder = $this->moveFolder('/IntraVox/en/news', [
            'about.json' => $this->makeFile('/IntraVox/en/news/about.json', ['uniqueId' => 'page-move4', 'title' => 'About']),
            'about' => $this->moveFolder('/IntraVox/en/news/about', []),
        ]);
        $en = $this->moveFolder('/IntraVox/en', ['news' => $newsFolder], creatable: false);
        $de = $this->moveFolder('/IntraVox/de', []);
        $base = $this->baseOver([$de, $en]);
        $ls = $this->languageService();
        $folders = $this->fakeFolderContext(readLanguageFolder: $de, intraVox: $base, languageFolder: $de);
        $svc = $this->serviceOver($folders);

        $this->expectException(ForbiddenException::class);
        $this->move($svc, $folders, $ls, 'page-move4', '');
    }

    // ------------------------------------------------------------- index subtree

    public function testMoveRepathsTheIndexedSubtree(): void {
        $index = $this->createMock(PageIndexService::class);
        $index->method('repathSubtree')->willReturnCallback(function (string $old, string $new): int {
            $this->indexRepaths[] = ['from' => $old, 'to' => $new];
            return 1;
        });

        $pageFolder = $this->moveFolder('/IntraVox/en/news/about', []);
        $newsFolder = $this->moveFolder('/IntraVox/en/news', [
            'about.json' => $this->makeFile('/IntraVox/en/news/about.json', ['uniqueId' => 'page-move1', 'title' => 'About']),
            'about' => $pageFolder,
        ]);
        $en = $this->moveFolder('/IntraVox/en', ['news' => $newsFolder]);
        $de = $this->moveFolder('/IntraVox/de', []);
        $base = $this->baseOver([$de, $en]);
        $ls = $this->languageService();
        $folders = $this->fakeFolderContext(readLanguageFolder: $de, intraVox: $base, languageFolder: $de);
        $svc = $this->serviceOver($folders, $index);

        $this->move($svc, $folders, $ls, 'page-move1', '');

        $this->assertCount(1, $this->indexRepaths, 'a move must repath the index once');
        $this->assertSame(
            ['from' => '/IntraVox/en/news/about', 'to' => '/IntraVox/en/about'],
            $this->indexRepaths[0],
            'the index must follow the page from its old path to its new one'
        );
    }

    public function testRefusedMoveLeavesTheIndexAlone(): void {
        $index = $this->createMock(PageIndexService::class);
        $index->method('repathSubtree')->willReturnCallback(function (string $old, string $new): int {
            $this->indexRepaths[] = ['from' => $old, 'to' => $new];
            return 1;
        });

        $de = $this->moveFolder('/IntraVox/de', [
            'ziele.json' => $this->makeFile('/IntraVox/de/ziele.json', ['uniqueId' => 'page-detarget2', 'title' => 'Ziele']),
            'ziele' => $this->moveFolder('/IntraVox/de/ziele', []),
        ]);
        $en = $this->moveFolder('/IntraVox/en', [
            'about.json' => $this->makeFile('/IntraVox/en/about.json', ['uniqueId' => 'page-move5', 'title' => 'About']),
            'about' => $this->moveFolder('/IntraVox/en/about', []),
        ]);
        $base = $this->baseOver([$de, $en]);
        $ls = $this->languageService();
        $folders = $this->fakeFolderContext(readLanguageFolder: $de, intraVox: $base, languageFolder: $de);
        $svc = $this->serviceOver($folders, $index);

        try {
            $this->move($svc, $folders, $ls, 'page-move5', 'page-detarget2');
            $this->fail('a cross-language move must be refused');
        } catch (CrossLanguageMoveException $e) {
            // expected
        }

        $this->assertSame([], $this->indexRepaths, 'a refused move must not repath anything');
    }
}
