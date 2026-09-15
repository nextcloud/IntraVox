<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Event\PageDeletedEvent;
use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageConflictException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Service\Version\PageVersionService;
use OCA\IntraVox\Service\Write\PageWriteService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes Write/PageWriteService DIRECTLY — `new PageWriteService(...)` —
 * rather than through the thin PageService write delegators. This is the coverage
 * that must exist BEFORE PageService can be deleted (dissolution plan, sessie 1):
 * the existing write coverage (PageCrudWriteTest / PageUpdatePipelineTest /
 * PageConcurrencyTest / PageSlugUniquenessTest / PageServiceCrossLanguageTest) all
 * routes through PageService, so it would evaporate the moment the god-class goes.
 *
 * The load-bearing part is the CLOSURE CONTRACT. PageService injects the folder
 * seams into each write method as $this-bound closures; those closures are what a
 * caller (soon a container factory, not PageService) must reproduce. So the tests
 * build the SAME closures the delegators build — the FolderContext-forward
 * languageFolder/languageOfFolder/userLanguage closures and the validateDepth
 * closure (real PagePathHelper depth math vs the always-5 cap) — and pin:
 *   - deletePage: the home guard fires BEFORE the languageFolder closure resolves;
 *     PageDeletedEvent dispatches BEFORE the folder delete; the configured homepage
 *     is protected; the cache is invalidated afterwards.
 *   - updatePage: the !user guard fires BEFORE the closure resolves; optimistic
 *     concurrency rejects a demonstrably-stale write; uniqueId + translationGroup
 *     are preserved from the existing page; the #90 index language comes from the
 *     languageOfFolder closure, not the editor's own.
 *   - createPage: the required-field guard; slug de-duplication against a sibling;
 *     uniqueId + translationGroup minting; the validateDepth closure's depth cap;
 *     the #70 non-creatable → ForbiddenException preflight.
 *
 * makeFile/makeFolder/fakeFolderContext/fakeCacheInvalidator/fakeHomepageResolver/
 * doubleOrBuild are the seam-agnostic fixture primitives shared with the PageService
 * characterization tests (they build real collaborators, not PageService), reused
 * here verbatim.
 */
class PageWriteServiceTest extends TestCase {

    use BuildsPageService;

    /** Ordered log of side effects, used to assert event-before-delete. */
    private array $events = [];

    /** clearRequest() calls the invalidator's cache spy saw (cache-invalidate pin). */
    private int $clearCacheCalls = 0;

    protected function setUp(): void {
        $this->events = [];
        $this->clearCacheCalls = 0;
    }

    /**
     * Build a real PageWriteService with the 14 ctor deps. Every dep defaults to an
     * inert double; a test overrides only what it drives, by ctor-param name.
     *
     * @param array<string,object> $over ctor-param-name => collaborator
     */
    private function makeWriteService(array $over = []): PageWriteService {
        $get = fn(string $name, callable $default): object => $over[$name] ?? $default();

        return new PageWriteService(
            $get('idUtils', fn() => new PageIdUtils()),
            $get('eventDispatcher', fn() => $this->createMock(IEventDispatcher::class)),
            $get('logger', fn() => $this->createMock(LoggerInterface::class)),
            $get('userSession', fn() => $this->createMock(IUserSession::class)),
            $get('pageVersionService', fn() => $this->createMock(PageVersionService::class)),
            $get('pageIndexService', fn() => $this->createMock(PageIndexService::class)),
            $get('languageService', fn() => $this->createMock(LanguageService::class)),
            $get('folders', fn() => $this->fakeFolderContext()),
            $get('locator', fn() => new PageLocator(
                $this->createMock(PageIndexService::class),
                $this->createMock(LoggerInterface::class)
            )),
            $get('homepageResolver', fn() => $this->fakeHomepageResolver(null)),
            $get('cacheInvalidator', fn() => $this->fakeCacheInvalidator()),
            $get('shape', fn() => $this->doubleOrBuild(PageShapeSanitizer::class)),
            $get('media', fn() => $this->createMock(PageMediaService::class)),
            $get('pageCache', fn() => $this->createMock(PageCacheService::class)),
        );
    }

    /**
     * The validateDepth closure PageService::createPage injects, reproduced
     * byte-faithfully: real PagePathHelper depth math vs getMaxDepthForPath, which
     * always returns 5.
     */
    private function validateDepthClosure(): \Closure {
        $pathHelper = new PagePathHelper();
        return function (string $parentPath) use ($pathHelper): void {
            $currentDepth = $pathHelper->calculateDepth($parentPath);
            $maxDepth = 5;
            if ($currentDepth >= $maxDepth) {
                throw new \InvalidArgumentException(
                    "Cannot create child page: maximum nesting depth of {$maxDepth} would be exceeded"
                );
            }
        };
    }

    /** A user session whose getUser() returns a stub user (update needs a user). */
    private function userSessionWith(?IUser $user): IUserSession {
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn($user);
        return $session;
    }

    // --------------------------------------------------------------- deletePage

    public function testDeletingHomeIdIsRejectedBeforeTheFolderIsResolved(): void {
        // The cheap $id==='home' guard MUST fire before the language folder is
        // self-sourced (resolving it can create-on-miss / throw). A bare
        // FolderContext (no seam, no intraVox override) throws on languageFolder();
        // if the guard order regressed to folder-first, this test would surface that
        // "IntraVox folder not found" instead of the crisp 'Cannot delete home page'.
        $svc = $this->makeWriteService(['folders' => $this->fakeFolderContext()]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete home page');
        $svc->deletePage('home');
    }

    public function testDeletingUnknownPageThrowsPageNotFound(): void {
        $empty = $this->makeFolder('/IntraVox/en', []);
        $base = $this->makeFolder('/IntraVox', ['en' => $empty]);
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);

        $svc = $this->makeWriteService([
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $empty),
            'locator' => new PageLocator($index, $this->createMock(LoggerInterface::class)),
        ]);

        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionMessage('Page not found: page-missing');
        $svc->deletePage('page-missing');
    }

    public function testConfiguredHomepageCannotBeDeleted(): void {
        [$lang] = $this->deleteFixture();
        // The resolver reports 'page-del' as the configured homepage.
        $svc = $this->makeWriteService([
            'folders' => $this->fakeFolderContext(intraVox: $this->deleteBase($lang), languageFolder: $lang),
            'locator' => $this->deleteLocator(),
            'homepageResolver' => $this->fakeHomepageResolver('page-del'),
        ]);

        try {
            $svc->deletePage('page-del');
            $this->fail('the configured homepage must not be deletable');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('HOMEPAGE_PROTECTED', $e->getMessage());
        }
        $this->assertNotContains('folder.delete', $this->events, 'nothing may be deleted when the guard fires');
    }

    public function testEventIsDispatchedBeforeTheFolderIsDeleted(): void {
        [$lang] = $this->deleteFixture();
        $dispatcher = $this->createMock(IEventDispatcher::class);
        $dispatcher->method('dispatchTyped')->willReturnCallback(function ($event): void {
            if ($event instanceof PageDeletedEvent) {
                $this->events[] = 'event:' . $event->getPageId() . ':' . $event->getUniqueId();
            }
        });

        $cache = $this->createMock(PageCacheService::class);
        $cache->method('clearRequest')->willReturnCallback(function (): void {
            $this->clearCacheCalls++;
        });

        $svc = $this->makeWriteService([
            'folders' => $this->fakeFolderContext(intraVox: $this->deleteBase($lang), languageFolder: $lang),
            'locator' => $this->deleteLocator(),
            'eventDispatcher' => $dispatcher,
            'cacheInvalidator' => $this->fakeCacheInvalidator($cache),
        ]);

        $svc->deletePage('page-del');

        $this->assertSame(
            ['event:del:page-del', 'folder.delete'],
            $this->events,
            'PageDeletedEvent must fire before the folder delete so cleanup sees a live page'
        );
        $this->assertSame(1, $this->clearCacheCalls, 'clearCache runs once after a successful delete');
    }

    // --------------------------------------------------------------- updatePage

    public function testUpdatingWithNoUserIsRejectedBeforeTheFolderIsResolved(): void {
        // The !$user guard MUST fire before the language folder is self-sourced. A
        // bare FolderContext throws on languageFolder(); the no-user guard must win,
        // so this surfaces 'No user in session', not 'IntraVox folder not found'.
        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith(null),
            'folders' => $this->fakeFolderContext(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No user in session');
        $svc->updatePage('page-x', ['title' => 'X']);
    }

    public function testStaleWriteIsRejectedWithAConflict(): void {
        // Optimistic concurrency: a baseVersion older than the file's current mtime
        // is a demonstrably-stale write and must be rejected, never silently
        // overwriting newer content.
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('doc.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/doc/doc.json');
        $file->method('getId')->willReturn(42);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getContent')->willReturn(json_encode(['uniqueId' => 'page-doc', 'title' => 'Doc']));
        $file->method('getMTime')->willReturn(2000); // current version is newer
        $file->expects($this->never())->method('putContent');

        [$lang, $base] = $this->pageFixture('doc', 'page-doc', $file);

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            'locator' => $this->fixtureLocator(),
        ]);

        $this->expectException(PageConflictException::class);
        $svc->updatePage(
            'page-doc',
            ['title' => 'New', 'baseVersion' => 1000] // started from an older version
        );
    }

    public function testUpdatePreservesUniqueIdAndTranslationGroupFromExistingPage(): void {
        // A save from a UI that knows nothing about uniqueId / translationGroup must
        // not drop the page's identity or unlink it from its other languages.
        $written = null;
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('doc.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/doc/doc.json');
        $file->method('getId')->willReturn(42);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getMTime')->willReturn(1000);
        // A well-formed translationGroup ('tg-' + 36 chars): the strict sanitizer
        // whitelist only preserves this exact shape, so the fixture must use it to
        // prove the preserve-from-existing invariant (a malformed group is dropped
        // by design, which would mask the test).
        $keepGroup = 'tg-00000000-0000-0000-0000-000000000000';
        $file->method('getContent')->willReturn(json_encode([
            'uniqueId' => 'page-doc',
            'translationGroup' => $keepGroup,
            'title' => 'Doc',
        ]));
        $file->method('putContent')->willReturnCallback(function (string $json) use (&$written): bool {
            $written = json_decode($json, true);
            return true;
        });

        [$lang, $base] = $this->pageFixture('doc', 'page-doc', $file);

        // The REAL (final) PageShapeSanitizer: a strict whitelist that rebuilds the
        // output. It preserves uniqueId / a well-formed translationGroup / title,
        // which is exactly the invariant under test — so the byte-faithful sanitizer
        // is the right double here, not an identity stub.
        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $svc->updatePage(
            'page-doc',
            ['title' => 'Renamed'] // client sends NO uniqueId / translationGroup
        );

        $this->assertNotNull($written, 'the page was written');
        $this->assertSame('page-doc', $written['uniqueId'], 'uniqueId is preserved from the existing page');
        $this->assertSame($keepGroup, $written['translationGroup'], 'translationGroup is preserved (no silent unlink)');
        $this->assertSame('Renamed', $written['title']);
    }

    public function testUpdateIndexesTheLanguageThePageLivesInNotTheEditorsOwn(): void {
        // #90: the index language is derived from the folder the page actually lives
        // in (FolderContext.languageOfFolder, path-based), never the editor's
        // userLanguage. The page folder is /IntraVox/en/doc, so it resolves to 'en'
        // even though the editing user's own language is 'de' — proving the folder,
        // not the editor, decides the indexed language.
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('doc.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/doc/doc.json');
        $file->method('getId')->willReturn(42);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getMTime')->willReturn(1000);
        $file->method('getContent')->willReturn(json_encode(['uniqueId' => 'page-doc', 'title' => 'Doc']));
        $file->method('putContent')->willReturnCallback(fn(string $json): int => strlen($json));

        [$lang, $base] = $this->pageFixture('doc', 'page-doc', $file);

        $indexedLanguage = null;
        $index = $this->createMock(PageIndexService::class);
        $index->method('indexPage')->willReturnCallback(
            function (array $data, string $language) use (&$indexedLanguage): void {
                $indexedLanguage = $language;
            }
        );

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            // The editing user's own language is 'de'; the page lives in en/.
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang, userLanguage: 'de'),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
            'pageIndexService' => $index,
        ]);

        $svc->updatePage('page-doc', ['title' => 'Renamed']);

        $this->assertSame('en', $indexedLanguage, 'the index language is the page\'s own, not the editor\'s');
    }

    // --------------------------------------------------------------- createPage

    public function testCreateRejectsMissingRequiredFields(): void {
        $svc = $this->makeWriteService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: id, title');
        $svc->createPage(['title' => 'No id'], null, $this->validateDepthClosure());
    }

    public function testCreateValidateDepthClosureRejectsTooDeepAParent(): void {
        // A parent already at the depth cap must be rejected by the injected
        // validateDepth closure BEFORE any folder write. 'en/public/a/b/c/d/e' strips
        // the language, then 'public' is depth len-1 = 5 → at the cap. The slug-dedup
        // scan runs first and touches the folder substrate, so a resolvable EN folder
        // is wired; the deep parentPath then trips validateDepth in createPageAtPath
        // before any newFolder/newFile.
        $lang = $this->makeFolder('/IntraVox/en', []);
        $base = $this->makeFolder('/IntraVox', ['en' => $lang]);
        $svc = $this->makeWriteService([
            'folders' => $this->fakeFolderContext(
                readLanguageFolder: $lang,
                intraVox: $base,
                languageFolder: $lang
            ),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maximum nesting depth of 5');
        $svc->createPage(
            ['id' => 'kid', 'title' => 'Too deep'],
            'en/public/a/b/c/d/e',
            $this->validateDepthClosure()
        );
    }

    public function testCreateOnAReadOnlyFolderIsForbidden(): void {
        // #70 preflight: a read-only destination must yield a clean 403, not a
        // filesystem-level failure. No parent → the read-language folder is the target.
        $target = $this->makeFolder('/IntraVox/en', [], creatable: false);

        $svc = $this->makeWriteService([
            'folders' => $this->fakeFolderContext(readLanguageFolder: $target, intraVox: $target),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $this->expectException(ForbiddenException::class);
        $svc->createPage(
            ['id' => 'newpage', 'title' => 'New'],
            null,
            $this->validateDepthClosure()
        );
    }

    public function testCreateMintsUniqueIdAndTranslationGroupAndDedupesTheSlug(): void {
        // A sibling 'guide' already exists at the root, so the slug must dedupe to
        // 'guide-2'; a page with no uniqueId/translationGroup gets both minted.
        $writtenName = null;
        $target = $this->makeFolder('/IntraVox/en', [
            'guide' => $this->makeFolder('/IntraVox/en/guide'),
        ]);
        // newFolder records the child slug actually created and returns a page folder
        // that can host {slug}.json + a _media subfolder (createPageAtPath writes both).
        $target->method('newFolder')->willReturnCallback(function (string $name) use (&$writtenName): Folder {
            $writtenName = $name;
            $pageFolder = $this->makeFolder('/IntraVox/en/' . $name);
            $jsonFile = $this->createMock(File::class);
            $jsonFile->method('getName')->willReturn($name . '.json');
            $jsonFile->method('putContent')->willReturnCallback(fn(string $c): int => strlen($c));
            $jsonFile->method('getId')->willReturn(7);
            $pageFolder->method('newFile')->willReturn($jsonFile);
            $pageFolder->method('newFolder')->willReturnCallback(
                fn(string $n): Folder => $this->makeFolder('/IntraVox/en/' . $name . '/' . $n)
            );
            // scanPageFolder() walks getStorage()->getScanner()/getCache(); the
            // storage/scanner/cache trio has no OCP stub in this suite, so it is a
            // hand-rolled no-op (matching PageSlugUniquenessTest). The scan is a
            // cache-warming side effect, irrelevant to where a page lands — but an
            // NPE here would be an \Error that escapes its \Exception catch.
            $pageFolder->method('getStorage')->willReturn(new class {
                public function getScanner() {
                    return new class {
                        public function scan($path, $recursive = false) {
                        }
                    };
                }
                public function getCache() {
                    return new class {
                        public function correctFolderSize($path, $data = null) {
                        }
                    };
                }
            });
            $pageFolder->method('getInternalPath')->willReturn('files/IntraVox/en/' . $name);
            return $pageFolder;
        });

        // The REAL (final) sanitizer: a strict whitelist that preserves the minted
        // uniqueId and a well-formed translationGroup, so both survive into the
        // returned page — the invariant under test.
        $svc = $this->makeWriteService([
            'folders' => $this->fakeFolderContext(readLanguageFolder: $target, intraVox: $target),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $result = $svc->createPage(
            ['id' => 'guide', 'title' => 'Guide'],
            null,
            $this->validateDepthClosure()
        );

        $this->assertSame('guide-2', $result['id'], 'a taken sibling slug is de-duplicated');
        $this->assertSame('guide-2', $writtenName, 'the folder is created under the de-duplicated slug');
        $this->assertStringStartsWith('page-', $result['uniqueId'], 'a uniqueId is minted');
        $this->assertStringStartsWith('tg-', $result['translationGroup'], 'a translation group is minted');
    }

    // ------------------------------------------------------------------ fixtures

    /**
     * The delete fixture: a page 'page-del' living as del.json + del/ in the EN
     * language folder, the folder recording its delete() into the ordered event log.
     *
     * @return array{0:Folder} the language folder
     */
    private function deleteFixture(): array {
        $pageJson = $this->makeFile('/IntraVox/en/del.json', ['uniqueId' => 'page-del', 'title' => 'Delete me']);
        $pageFolder = $this->createMock(Folder::class);
        $pageFolder->method('getName')->willReturn('del');
        $pageFolder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $pageFolder->method('getPath')->willReturn('/IntraVox/en/del');
        $pageFolder->method('getDirectoryListing')->willReturn([]);
        $pageFolder->method('delete')->willReturnCallback(function (): void {
            $this->events[] = 'folder.delete';
        });

        $lang = $this->makeFolder('/IntraVox/en', [
            'del.json' => $pageJson,
            'del' => $pageFolder,
        ]);
        return [$lang];
    }

    private function deleteBase(Folder $lang): Folder {
        return $this->makeFolder('/IntraVox', ['en' => $lang]);
    }

    /** A PageLocator whose index misses, so the delete resolves via the folder scan. */
    private function deleteLocator(): PageLocator {
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        return new PageLocator($index, $this->createMock(LoggerInterface::class));
    }

    /**
     * A page fixture: a folder {slug}/ holding {slug}.json (the given File), inside
     * the EN language folder, inside /IntraVox. Used by the update tests.
     *
     * @return array{0:Folder,1:Folder} language folder, base folder
     */
    private function pageFixture(string $slug, string $uniqueId, File $file): array {
        $pageFolder = $this->makeFolder("/IntraVox/en/$slug", ["$slug.json" => $file]);
        $lang = $this->makeFolder('/IntraVox/en', [$slug => $pageFolder]);
        $base = $this->makeFolder('/IntraVox', ['en' => $lang]);
        return [$lang, $base];
    }

    /** A PageLocator whose index misses, so lookups resolve via the folder scan. */
    private function fixtureLocator(): PageLocator {
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        return new PageLocator($index, $this->createMock(LoggerInterface::class));
    }
}
