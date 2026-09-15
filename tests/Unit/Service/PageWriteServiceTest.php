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
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsCollaboratorFixtures;
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

    use BuildsCollaboratorFixtures;
    /** Ordered log of side effects, used to assert event-before-delete. */
    private array $events = [];

    /** clearRequest() calls the invalidator's cache spy saw (cache-invalidate pin). */
    private int $clearCacheCalls = 0;

    /** Names newFolder() was called with, per folder path (slug-dedup tests). */
    private array $slugCreated = [];

    protected function setUp(): void {
        $this->events = [];
        $this->clearCacheCalls = 0;
        $this->slugCreated = [];
    }

    /**
     * Build a real PageWriteService with the 15 ctor deps. Every dep defaults to an
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
            $get('depthValidator', fn() => $this->realDepthValidator()),
        );
    }

    /**
     * A real PageDepthValidator (the create-path max-nesting rule the service now
     * self-sources instead of receiving as a closure). Its LanguageService reports
     * the standard codes available so the leading-language-strip in
     * getMaxDepthForPath fires exactly as production.
     */
    private function realDepthValidator(): \OCA\IntraVox\Service\Path\PageDepthValidator {
        $ls = $this->createMock(LanguageService::class);
        $ls->method('isLanguageAvailable')->willReturnCallback(
            fn(string $code) => in_array($code, ['en', 'de', 'fr', 'nl'], true)
        );
        return new \OCA\IntraVox\Service\Path\PageDepthValidator(new PagePathHelper(), $ls);
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

    public function testIndexFallsBackToTheEditorLanguageWhenPathHasNone(): void {
        // #90 null-branch: a page whose folder sits outside any language folder
        // (legacy layouts, odd mounts) must still index rather than throw — the
        // index update is explicitly non-blocking, the page is already saved by
        // this point. The page folder IS the IntraVox root, so
        // FolderContext.languageOfFolder() returns null and the write falls back
        // to the editor's OWN language ('de'), not a language in the path.
        $pageJson = $this->createMock(File::class);
        $pageJson->method('getName')->willReturn('loose.json');
        $pageJson->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $pageJson->method('getPath')->willReturn('/IntraVox/loose.json');
        $pageJson->method('getId')->willReturn(abs(crc32('/IntraVox/loose.json')));
        $pageJson->method('isUpdateable')->willReturn(true);
        $pageJson->method('getMTime')->willReturn(1000);
        $pageJson->method('getContent')->willReturn(
            json_encode(['uniqueId' => 'page-idx3', 'title' => 'Loose', 'widgets' => []])
        );
        $pageJson->method('putContent')->willReturnCallback(fn(string $json): int => strlen($json));

        // The page folder IS the IntraVox root — languageOfFolder() returns null.
        $root = $this->makeFolder('/IntraVox', [
            'loose.json' => $pageJson,
        ]);

        $indexed = [];
        $index = $this->createMock(PageIndexService::class);
        $index->method('indexPage')->willReturnCallback(
            function (array $pageData, string $language, string $path, ?int $fileId = null) use (&$indexed): void {
                $indexed[] = [
                    'uniqueId' => $pageData['uniqueId'] ?? null,
                    'language' => $language,
                    'path' => $path,
                ];
            }
        );

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            // The page folder IS the root; the editing user's own language is 'de'.
            'folders' => $this->fakeFolderContext(intraVox: $root, languageFolder: $root, userLanguage: 'de'),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
            'pageIndexService' => $index,
        ]);

        $svc->updatePage('page-idx3', ['title' => 'Loose page', 'widgets' => []]);

        $this->assertCount(1, $indexed);
        $this->assertSame(
            'de',
            $indexed[0]['language'],
            'with no language in the path, fall back to the editor\'s own'
        );
    }

    public function testCurrentSaveSucceeds(): void {
        // A save from the current version (baseVersion === the file's mtime) is not
        // stale, so it goes through — and the response must carry the NEW token so
        // the editor's next save is not a conflict with itself.
        $written = null;
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('doc.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/doc/doc.json');
        $file->method('getId')->willReturn(42);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getMTime')->willReturn(2000); // current version
        $file->method('getContent')->willReturn(
            json_encode(['uniqueId' => 'page-doc', 'title' => 'Doc', 'widgets' => []])
        );
        $file->method('putContent')->willReturnCallback(function (string $json) use (&$written): bool {
            $written = json_decode($json, true);
            return true;
        });

        [$lang, $base] = $this->pageFixture('doc', 'page-doc', $file);

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $result = $svc->updatePage('page-doc', [
            'title' => 'Up to date',
            'widgets' => [],
            'baseVersion' => 2000, // matches the file's current mtime
        ]);

        $this->assertNotNull($written, 'the page was written');
        $this->assertArrayHasKey(
            'baseVersion',
            $result,
            'the response must carry the new token so the next save is not a conflict with itself'
        );
    }

    public function testSaveWithoutTokenIsAllowed(): void {
        // A client that sends no token is not blocked. Older frontends, scripts and
        // imports must keep working — this rejects a save that demonstrably started
        // from an older version, never one that merely failed to say.
        $written = null;
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('doc.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/doc/doc.json');
        $file->method('getId')->willReturn(42);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getMTime')->willReturn(2000);
        $file->method('getContent')->willReturn(
            json_encode(['uniqueId' => 'page-doc', 'title' => 'Doc', 'widgets' => []])
        );
        $file->method('putContent')->willReturnCallback(function (string $json) use (&$written): bool {
            $written = json_decode($json, true);
            return true;
        });

        [$lang, $base] = $this->pageFixture('doc', 'page-doc', $file);

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $svc->updatePage('page-doc', ['title' => 'No token', 'widgets' => []]);

        $this->assertNotNull($written, 'a save with no token is applied');
    }

    public function testTokenIsNotPersistedIntoThePage(): void {
        // The token is transport-only: it must never be persisted into the page
        // JSON, where it would become a stale field that outlives the request.
        $written = null;
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('doc.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/doc/doc.json');
        $file->method('getId')->willReturn(42);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getMTime')->willReturn(2000);
        $file->method('getContent')->willReturn(
            json_encode(['uniqueId' => 'page-doc', 'title' => 'Doc', 'widgets' => []])
        );
        $file->method('putContent')->willReturnCallback(function (string $json) use (&$written): bool {
            $written = json_decode($json, true);
            return true;
        });

        [$lang, $base] = $this->pageFixture('doc', 'page-doc', $file);

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $svc->updatePage('page-doc', [
            'title' => 'Clean',
            'widgets' => [],
            'baseVersion' => 2000,
        ]);

        $this->assertArrayNotHasKey(
            'baseVersion',
            $written,
            'the concurrency token must not be stored in the page file'
        );
    }

    public function testReadOnlyFileIsRefusedBeforeAnyWrite(): void {
        // #70 preflight (update path): a read-only page file has isUpdateable === false,
        // so the write is refused with a clean 403 before putContent is ever reached.
        $wrote = [];
        $ro = $this->createMock(File::class);
        $ro->method('getName')->willReturn('about.json');
        $ro->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $ro->method('getPath')->willReturn('/IntraVox/en/about/about.json');
        $ro->method('getId')->willReturn(42);
        $ro->method('isUpdateable')->willReturn(false);
        $ro->method('getContent')->willReturn(json_encode(['uniqueId' => 'page-about', 'title' => 'Old']));
        $ro->method('putContent')->willReturnCallback(function () use (&$wrote): void {
            $wrote[] = 'putContent';
        });

        [$lang, $base] = $this->pageFixture('about', 'page-about', $ro);

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        try {
            $svc->updatePage('page-about', ['title' => 'New']);
            $this->fail('a read-only file must be refused');
        } catch (ForbiddenException $e) {
            $this->assertStringContainsString('permission', $e->getMessage());
        }
        $this->assertNotContains('putContent', $wrote, 'nothing may be written to a read-only page');
    }

    public function testWritePipelineTakesTheVersionSnapshotBeforeOverwriting(): void {
        // The load-bearing W2 invariant: the version snapshot must precede
        // putContent, or the snapshot captures the NEW content and the pre-edit
        // version is lost.
        $steps = [];
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('about.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/about/about.json');
        $file->method('getId')->willReturn(42);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getMTime')->willReturn(1000);
        $file->method('getContent')->willReturn(json_encode(['uniqueId' => 'page-about', 'title' => 'Old']));
        $file->method('putContent')->willReturnCallback(function () use (&$steps): void {
            $steps[] = 'putContent';
        });

        $version = $this->createMock(PageVersionService::class);
        $version->method('createBeforeUpdate')->willReturnCallback(function () use (&$steps): void {
            $steps[] = 'createBeforeUpdate';
        });

        [$lang, $base] = $this->pageFixture('about', 'page-about', $file);

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
            'pageVersionService' => $version,
        ]);

        $svc->updatePage('page-about', ['title' => 'New']);

        $snapshotAt = array_search('createBeforeUpdate', $steps, true);
        $writeAt = array_search('putContent', $steps, true);
        $this->assertNotFalse($snapshotAt, 'a version snapshot must be taken');
        $this->assertNotFalse($writeAt, 'the file must be written');
        $this->assertLessThan(
            $writeAt,
            $snapshotAt,
            'createBeforeUpdate must run before putContent'
        );
    }

    public function testReturnCarriesTheNewBaseVersion(): void {
        // The return contract: id is the folder basename and baseVersion is the
        // post-write mtime, while the transport-only token is never persisted.
        $written = null;
        $mtime = 1000;
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('about.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/about/about.json');
        $file->method('getId')->willReturn(42);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getMTime')->willReturn($mtime);
        $file->method('getContent')->willReturn(json_encode(['uniqueId' => 'page-about', 'title' => 'Old']));
        $file->method('putContent')->willReturnCallback(function (string $json) use (&$written): bool {
            $written = $json;
            return true;
        });

        [$lang, $base] = $this->pageFixture('about', 'page-about', $file);

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $result = $svc->updatePage('page-about', ['title' => 'New']);

        $this->assertSame('about', $result['id'], 'id is the folder basename');
        $this->assertSame($mtime, $result['baseVersion'], 'the response hands back the post-write mtime');
        $this->assertArrayNotHasKey('baseVersion', json_decode($written, true));
    }

    // --------------------------------------------------------------- createPage

    public function testCreateRejectsMissingRequiredFields(): void {
        $svc = $this->makeWriteService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: id, title');
        $svc->createPage(['title' => 'No id'], null);
    }

    public function testCreateDepthRuleRejectsTooDeepAParent(): void {
        // A parent already at the depth cap must be rejected by the self-sourced
        // PageDepthValidator BEFORE any folder write. 'en/public/a/b/c/d/e' strips
        // the language, then 'public' is depth len-1 = 5 → at the cap. The slug-dedup
        // scan runs first and touches the folder substrate, so a resolvable EN folder
        // is wired; the deep parentPath then trips the validator in createPageAtPath
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
            'en/public/a/b/c/d/e'
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
            null
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
            null
        );

        $this->assertSame('guide-2', $result['id'], 'a taken sibling slug is de-duplicated');
        $this->assertSame('guide-2', $writtenName, 'the folder is created under the de-duplicated slug');
        $this->assertStringStartsWith('page-', $result['uniqueId'], 'a uniqueId is minted');
        $this->assertStringStartsWith('tg-', $result['translationGroup'], 'a translation group is minted');
    }

    // -------------------------------------------- createPage: slug-dedup scope
    //
    // Migrated from the retired PageSlugUniquenessTest (fase-10): the UNIQUE
    // slug/folder-path branches of createPage()'s resolveExistingFolderPath
    // de-dup scope, now driven against PageWriteService DIRECTLY via
    // makeSlugService(). A page's slug is a FOLDER NAME: it only has to be unique
    // among the entries of the folder the page is written into, not anywhere in
    // the acting user's language tree. Assertions are byte-identical to the old
    // PageService-routed tests; only the construction path changed.

    /**
     * The reported bug: translating into another language must keep the name.
     *
     * nl/ holds niv5 and the acting user's language is nl, but the page is
     * written into en/ — where the name is free.
     */
    public function testTranslationKeepsSourceSlugInOtherLanguage(): void {
        $nl = $this->slugFolder('/IntraVox/nl', ['niv5' => $this->slugFolder('/IntraVox/nl/niv5')]);
        $en = $this->slugFolder('/IntraVox/en');
        $svc = $this->makeSlugService(['nl' => $nl, 'en' => $en], 'nl');

        $created = $svc->createPage($this->slugPageData('niv5', 'niv5'), 'en');

        $this->assertSame('niv5', $created['id'], 'a translation must keep the source name');
        $this->assertContains('niv5', $this->createdIn('/IntraVox/en'));
    }

    /** The scope defect: the same name under a different parent is fine. */
    public function testSameSlugUnderDifferentParentsIsAllowed(): void {
        $about = $this->slugFolder('/IntraVox/nl/about', ['team' => $this->slugFolder('/IntraVox/nl/about/team')]);
        $sales = $this->slugFolder('/IntraVox/nl/sales');
        $nl = $this->slugFolder('/IntraVox/nl', ['about' => $about, 'sales' => $sales]);
        $svc = $this->makeSlugService(['nl' => $nl], 'nl');

        $created = $svc->createPage($this->slugPageData('team', 'Team'), 'nl/sales');

        $this->assertSame('team', $created['id'], 'a sibling elsewhere must not reserve the name');
        $this->assertContains('team', $this->createdIn('/IntraVox/nl/sales'));
    }

    /**
     * In the legacy "beside" layout a page's JSON sits next to its folder, so
     * the slug is taken even when no folder of that name exists.
     */
    public function testBesideLayoutJsonBlocksSlug(): void {
        $sales = $this->slugFolder('/IntraVox/nl/sales', ['team.json']);
        $nl = $this->slugFolder('/IntraVox/nl', ['sales' => $sales]);
        $svc = $this->makeSlugService(['nl' => $nl], 'nl');

        $created = $svc->createPage($this->slugPageData('team', 'Team'), 'nl/sales');

        $this->assertSame('team-2', $created['id']);
    }

    /**
     * 'home' is written as home.json at the language root; a 'home-2' would only
     * create a page folder the homepage resolver never looks at.
     */
    public function testHomeIsNeverSuffixed(): void {
        $nl = $this->slugFolder('/IntraVox/nl', ['home.json']);
        $svc = $this->makeSlugService(['nl' => $nl], 'nl');

        $created = $svc->createPage($this->slugPageData('home', 'Home'), 'nl');

        $this->assertSame('home', $created['id']);
        // home.json is written at the language root; the only folder the home
        // branch creates is its _media sibling, never a 'home'/'home-2' page folder.
        $this->assertSame(['_media'], $this->createdIn('/IntraVox/nl'));
    }

    /**
     * A destination that does not exist yet cannot hold a collision — and the
     * resolver must not create it on the way to finding that out.
     */
    public function testMissingParentFolderSkipsDedup(): void {
        $en = $this->slugFolder('/IntraVox/en');
        $nl = $this->slugFolder('/IntraVox/nl', ['news' => $this->slugFolder('/IntraVox/nl/news')]);
        $svc = $this->makeSlugService(['nl' => $nl, 'en' => $en], 'nl');

        $created = $svc->createPage($this->slugPageData('news', 'News'), 'en/does/not/exist');

        $this->assertSame('news', $created['id']);
        $this->assertSame(
            ['does'],
            $this->createdIn('/IntraVox/en'),
            'only the write path may create folders, and only once'
        );
    }

    /**
     * With no parent path, createPageAtPath() falls back to the READ language
     * folder — so the collision check has to look there too, not in the user's
     * own language folder.
     */
    public function testNullParentPathUsesReadLanguageFolder(): void {
        $nl = $this->slugFolder('/IntraVox/nl', ['news' => $this->slugFolder('/IntraVox/nl/news')]);
        $en = $this->slugFolder('/IntraVox/en');
        $svc = $this->makeSlugService(['nl' => $nl, 'en' => $en], 'nl', 'en');

        $created = $svc->createPage($this->slugPageData('news', 'News'), null);

        $this->assertSame('news', $created['id']);
        $this->assertContains('news', $this->createdIn('/IntraVox/en'));
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

    // -------------------------------------------- slug-dedup fixtures (fase-10)

    /**
     * A folder holding $entries (names → child folders, or bare names for files),
     * used by the migrated slug-dedup tests. Unlike the trait's makeFolder it
     * RECORDS newFolder() into $this->slugCreated (so a test can assert both the
     * name a page got AND the folder it landed in) and stubs the storage/scanner
     * trio scanPageFolder() walks, plus getInternalPath()/newFile()/newFolder().
     *
     * nodeExists() answers from $entries, which is what the sibling-scoped check
     * consults; getStorage()/getCache() are a hand-rolled no-op (the scan is a
     * cache-warming side effect, irrelevant to where a page lands). Lifted from
     * the retired PageSlugUniquenessTest verbatim in behaviour.
     */
    private function slugFolder(string $path, array $entries = [], bool $creatable = true): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getStorage')->willReturn(new class {
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
        $folder->method('getInternalPath')->willReturn(ltrim($path, '/'));
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('isCreatable')->willReturn($creatable);
        $folder->method('getDirectoryListing')->willReturn(
            array_values(array_filter(
                $entries,
                fn($e) => $e instanceof \OCP\Files\Node
            ))
        );

        $folder->method('nodeExists')->willReturnCallback(
            fn($name) => in_array($name, $entries, true) || isset($entries[$name])
        );

        $folder->method('get')->willReturnCallback(function ($name) use ($entries, $path) {
            if (isset($entries[$name]) && $entries[$name] instanceof \OCP\Files\Node) {
                return $entries[$name];
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $name);
        });

        $self = $this;
        $folder->method('newFolder')->willReturnCallback(
            function ($name) use ($self, $path) {
                $self->slugCreated[$path][] = $name;
                return $self->slugFolder($path . '/' . $name);
            }
        );
        $folder->method('newFile')->willReturnCallback(
            function ($name) use ($self, $path) {
                $file = $self->createMock(File::class);
                $file->method('getName')->willReturn($name);
                $file->method('getPath')->willReturn($path . '/' . $name);
                $file->method('getId')->willReturn(abs(crc32($path . '/' . $name)));
                return $file;
            }
        );

        return $folder;
    }

    /** Names newFolder() recorded for $path. */
    private function createdIn(string $path): array {
        return $this->slugCreated[$path] ?? [];
    }

    private function slugPageData(string $id, string $title): array {
        return ['id' => $id, 'title' => $title, 'layout' => ['columns' => 1, 'rows' => []]];
    }

    /**
     * A real PageWriteService wired for a slug-dedup test: the FolderContext
     * mounts $languages under /IntraVox with the acting user's own display
     * language ($writeLang) as the write-target and $readLang (default $writeLang)
     * as the read-language fallback. The LanguageService reports the standard codes
     * available so resolveExistingFolderPath's leading-language branch fires.
     *
     * @param array<string,Folder> $languages language code → folder, mounted under /IntraVox
     * @param string $writeLang the acting user's own display language
     * @param string|null $readLang language readLanguageFolder() resolves to
     */
    private function makeSlugService(array $languages, string $writeLang, ?string $readLang = null): PageWriteService {
        $base = $this->createMock(Folder::class);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willReturn(array_values($languages));
        $base->method('get')->willReturnCallback(function ($p) use ($languages) {
            if (isset($languages[$p])) {
                return $languages[$p];
            }
            throw new \OCP\Files\NotFoundException($p);
        });

        $writeFolder = $languages[$writeLang];
        $readFolder = $languages[$readLang ?? $writeLang];

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturnCallback(
            fn(string $code) => in_array($code, ['en', 'de', 'fr', 'nl'], true)
        );
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        return $this->makeWriteService([
            'languageService' => $languageService,
            'folders' => $this->fakeFolderContext(
                readLanguageFolder: $readFolder,
                intraVox: $base,
                languageFolder: $writeFolder,
                userLanguage: $writeLang
            ),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);
    }

    // ------------------------------------------ #90 cross-language write-locate
    //
    // Migrated from the retired PageServiceCrossLanguageTest (fase-10): the UNIQUE
    // cross-language locate-for-write behaviour, now driven against PageWriteService
    // DIRECTLY via crossLangService() instead of through the PageService facade.
    //
    // Reading and writing used to resolve the language folder differently: getPage()
    // searched every language folder, while the write paths searched only the folder
    // for the user's own display language. A page stored in en/ was therefore
    // renderable but unsaveable for a user whose Nextcloud language was de/ — the
    // save failed with "Page not found" on a page that was visibly on screen. Both
    // sides now go through the locator's cross-language scan (locatePageAnyLanguage).
    //
    // These tests drive the scan through the public updatePage() entry point against
    // a fake IntraVox folder holding two language folders. The getOrCreateFolderPath
    // parent-language tests reflect the PRIVATE method off the directly-built
    // PageWriteService. Assertions are byte-identical to the old PageService-routed
    // tests; only the construction path changed.

    /**
     * The issue #90 scenario: the page lives in en/, the user writes from de/.
     * Before the fix this threw "Page not found: page-issue90".
     */
    public function testUpdateFindsPageInAnotherLanguageFolder(): void {
        $writes = [];
        $page = $this->crossLangFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-issue90', 'title' => 'About', 'widgets' => []],
            $writes
        );

        $en = $this->crossLangFolder('en', [$page]);
        $de = $this->crossLangFolder('de', []); // user's own language: empty

        $svc = $this->crossLangService($de, [$de, $en]);
        $svc->updatePage('page-issue90', ['title' => 'About us', 'widgets' => []]);

        $this->assertArrayHasKey(
            '/IntraVox/en/about.json',
            $writes,
            'the page must be written back to the language folder it actually lives in'
        );
        $this->assertSame('About us', json_decode($writes['/IntraVox/en/about.json'], true)['title']);
    }

    /**
     * The page in the user's own language folder still wins, so a same-uniqueId
     * page is never "found" in the wrong language when a local one exists.
     */
    public function testUpdatePrefersTheUsersOwnLanguageFolder(): void {
        $writes = [];
        $dePage = $this->crossLangFile(
            '/IntraVox/de/about.json',
            ['uniqueId' => 'page-shared', 'title' => 'Über uns', 'widgets' => []],
            $writes
        );
        $enPage = $this->crossLangFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-shared', 'title' => 'About', 'widgets' => []],
            $writes
        );

        $de = $this->crossLangFolder('de', [$dePage]);
        $en = $this->crossLangFolder('en', [$enPage]);

        $svc = $this->crossLangService($de, [$de, $en]);
        $svc->updatePage('page-shared', ['title' => 'Über uns alle', 'widgets' => []]);

        $this->assertArrayHasKey('/IntraVox/de/about.json', $writes);
        $this->assertArrayNotHasKey(
            '/IntraVox/en/about.json',
            $writes,
            'the local language folder must take precedence over the cross-language scan'
        );
    }

    /** A genuinely unknown page must still be reported as not found. */
    public function testUpdateStillFailsForUnknownPage(): void {
        $en = $this->crossLangFolder('en', []);
        $de = $this->crossLangFolder('de', []);

        $svc = $this->crossLangService($de, [$de, $en]);

        $this->expectException(\OCA\IntraVox\Exception\PageNotFoundException::class);
        $svc->updatePage('page-does-not-exist', ['title' => 'x', 'widgets' => []]);
    }

    /**
     * The reporter's exact topology in issue #90: the IntraVox folder holds
     * ONLY en/, while the user's Nextcloud language is German. getLanguageFolder()
     * then falls back to en/ on its own, so the page IS in the folder searched —
     * this test pins that this configuration saves cleanly.
     */
    public function testReporterTopologyEnglishOnlyFolderGermanUser(): void {
        $writes = [];
        $page = $this->crossLangFile(
            '/IntraVox/en/home.json',
            ['uniqueId' => 'page-4e98ddf1', 'title' => 'Home', 'widgets' => []],
            $writes
        );
        $en = $this->crossLangFolder('en', [$page]);

        // Only en/ exists; getLanguageFolder() already resolved to it.
        $svc = $this->crossLangService($en, [$en]);
        $svc->updatePage('page-4e98ddf1', ['title' => 'Startseite', 'widgets' => []]);

        $this->assertArrayHasKey('/IntraVox/en/home.json', $writes);
        $this->assertSame('Startseite', json_decode($writes['/IntraVox/en/home.json'], true)['title']);
    }

    /**
     * Issue #90 Case A step 3-4: after the admin installs the German demo
     * content, de/ exists and getLanguageFolder() stops falling back to en/.
     * The German user's write target is now an de/ folder that does NOT hold
     * the English page they were editing — this is the state in which the save
     * breaks, and the one locatePageAnyLanguage() has to rescue.
     */
    public function testGermanUserEditsEnglishPageOnceGermanFolderExists(): void {
        $writes = [];
        $enPage = $this->crossLangFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-4e98ddf1', 'title' => 'About', 'widgets' => []],
            $writes
        );
        $dePage = $this->crossLangFile(
            '/IntraVox/de/vorlage.json',
            ['uniqueId' => 'page-vorlage', 'title' => 'Vorlage', 'widgets' => []],
            $writes
        );
        $en = $this->crossLangFolder('en', [$enPage]);
        $de = $this->crossLangFolder('de', [$dePage]);

        // German user: write target is de/, but the page lives in en/.
        $svc = $this->crossLangService($de, [$de, $en]);
        $svc->updatePage('page-4e98ddf1', ['title' => 'Über uns', 'widgets' => []]);

        $this->assertArrayHasKey('/IntraVox/en/about.json', $writes);
        $this->assertArrayNotHasKey('/IntraVox/de/vorlage.json', $writes);
    }

    /**
     * A sub-page belongs in its PARENT's language folder. An English editor
     * adding a page under a German parent must write into de/ — not fabricate
     * an empty de-mirror under their own language (see getOrCreateFolderPath).
     */
    public function testSubPageFollowsParentLanguageNotAuthorLanguage(): void {
        $de = $this->crossLangFolder('de', []);
        $en = $this->crossLangFolder('en', []);

        // Author's own language folder is en/ (profile language), parent is de/.
        $svc = $this->crossLangService($en, [$de, $en]);
        $target = $this->callGetOrCreateFolderPath($svc, 'de/abteilungen');

        $this->assertSame(
            '/IntraVox/de/abteilungen',
            $target->getPath(),
            'a sub-page must be created under the parent language folder'
        );
    }

    /** An unknown language segment falls back to the author's own folder. */
    public function testUnknownLanguageSegmentFallsBackToAuthorFolder(): void {
        $de = $this->crossLangFolder('de', []);
        $en = $this->crossLangFolder('en', []);
        $svc = $this->crossLangService($en, [$de, $en]);

        // 'zz' is not an available language -> treated as a normal path segment
        // below the author's own language folder.
        $target = $this->callGetOrCreateFolderPath($svc, 'zz/team');
        $this->assertSame('/IntraVox/en/zz/team', $target->getPath());
    }

    /**
     * Drive the moved getOrCreateFolderPath() on the directly-built PageWriteService.
     * fase-5 Phase IV moved the method off PageService into Write/PageWriteService as a
     * PRIVATE method, so it is reflected off PageWriteService directly here — the walk
     * hits the identical fixture folders the old PageService-routed test pinned.
     */
    private function callGetOrCreateFolderPath(PageWriteService $svc, string $path): Folder {
        $m = new \ReflectionMethod(PageWriteService::class, 'getOrCreateFolderPath');
        return $m->invoke($svc, $path);
    }

    // ------------------------------------ #90 cross-language fixtures (fase-10)

    /** A page File mock that records putContent() calls into $writes. */
    private function crossLangFile(string $path, array $json, array &$writes): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getId')->willReturn(abs(crc32($path)));
        $file->method('putContent')->willReturnCallback(function ($data) use ($path, &$writes) {
            $writes[$path] = $data;
        });
        return $file;
    }

    /**
     * A language folder (e.g. /IntraVox/en) holding the given page files.
     *
     * get() resolves the files it actually contains — findPageByUniqueId()
     * probes `home.json` through get() before it walks the directory listing,
     * so a fixture that throws unconditionally would hide the homepage.
     */
    private function crossLangFolder(string $code, array $files): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn($code);
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn('/IntraVox/' . $code);
        $folder->method('getDirectoryListing')->willReturn($files);
        $byName = [];
        foreach ($files as $f) {
            $byName[$f->getName()] = $f;
        }
        $folder->method('get')->willReturnCallback(function ($p) use ($byName) {
            if (isset($byName[$p])) {
                return $byName[$p];
            }
            throw new \OCP\Files\NotFoundException($p);
        });
        // newFolder() returns a fresh child so path building can be asserted.
        $self = $this;
        $folder->method('newFolder')->willReturnCallback(
            function ($name) use ($self, $code) {
                return $self->crossLangFolder($code . '/' . $name, []);
            }
        );
        return $folder;
    }

    /**
     * A real PageWriteService wired for a cross-language test: the write-target
     * ($writeFolder) is the user's own language folder, while $allLanguages are
     * every language folder under /IntraVox that the locator's cross-language scan
     * walks. The user's display language is 'de'; the LanguageService reports the
     * standard codes available so getOrCreateFolderPath's leading-language branch
     * fires exactly as production.
     *
     * @param Folder $writeFolder the user's own language folder (write target)
     * @param Folder[] $allLanguages every language folder under /IntraVox
     */
    private function crossLangService(Folder $writeFolder, array $allLanguages): PageWriteService {
        $base = $this->createMock(Folder::class);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willReturn($allLanguages);
        // get('de') must resolve the language folder — getOrCreateFolderPath()
        // looks the parent's language up through the IntraVox root.
        $byLang = [];
        foreach ($allLanguages as $l) {
            $byLang[$l->getName()] = $l;
        }
        $base->method('get')->willReturnCallback(function ($p) use ($byLang) {
            if (isset($byLang[$p])) {
                return $byLang[$p];
            }
            throw new \OCP\Files\NotFoundException($p);
        });

        // Real installs know these codes; the auto-mock would answer false for
        // every language and make path building skip the language segment.
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturnCallback(
            fn(string $code) => in_array($code, ['en', 'de', 'fr', 'nl'], true)
        );
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        return $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'languageService' => $languageService,
            'folders' => $this->fakeFolderContext(
                intraVox: $base,
                languageFolder: $writeFolder,
                userLanguage: 'de'
            ),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);
    }

    // ---- fase-10 COMMIT 4 fidelity restore: the three assertions the migrated
    //      covers dropped (an AV pass caught them). Behaviour is byte-identical to
    //      the retired PageSlugUniquenessTest / PageUpdatePipelineTest originals.

    public function testCreateKeepsACallerSuppliedTranslationGroup(): void {
        // The keep-when-supplied half of the create-group contract
        // (PageWriteService::createPage `if (empty($data['translationGroup']))`):
        // a well-formed group passed in is KEPT, not re-minted — so copyPage must
        // unset it to avoid inheriting the source's group. The mint-when-absent half
        // is testCreateMintsUniqueIdAndTranslationGroupAndDedupesTheSlug.
        $sourceGroup = 'tg-11111111-2222-3333-4444-555555555555';
        $target = $this->makeFolder('/IntraVox/en', []);
        $target->method('newFolder')->willReturnCallback(function (string $name): Folder {
            $pageFolder = $this->makeFolder('/IntraVox/en/' . $name);
            $jsonFile = $this->createMock(File::class);
            $jsonFile->method('getName')->willReturn($name . '.json');
            $jsonFile->method('putContent')->willReturnCallback(fn(string $c): int => strlen($c));
            $jsonFile->method('getId')->willReturn(7);
            $pageFolder->method('newFile')->willReturn($jsonFile);
            $pageFolder->method('newFolder')->willReturnCallback(
                fn(string $n): Folder => $this->makeFolder('/IntraVox/en/' . $name . '/' . $n)
            );
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

        $svc = $this->makeWriteService([
            'folders' => $this->fakeFolderContext(readLanguageFolder: $target, intraVox: $target),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $kept = $svc->createPage([
            'id' => 'kept',
            'title' => 'Kept',
            'translationGroup' => $sourceGroup,
        ], null);

        $this->assertSame(
            $sourceGroup,
            $kept['translationGroup'],
            'a supplied group is kept — so copyPage must unset it to avoid inheriting'
        );
    }

    public function testUpdateRejectsAPayloadUniqueIdAndTranslationGroupHijack(): void {
        // The client tries to change uniqueId and translationGroup; BOTH must be kept
        // from the existing file, never taken from the payload (PageWriteService
        // overwrites them unconditionally from the loaded page). A regression to
        // only-if-absent semantics would silently honour a hijacked identity.
        $written = null;
        $keepGroup = 'tg-00000000-0000-0000-0000-000000000000';
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('doc.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/doc/doc.json');
        $file->method('getId')->willReturn(42);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getMTime')->willReturn(1000);
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

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $svc->updatePage('page-doc', [
            'title' => 'New',
            'uniqueId' => 'page-HIJACK',
            'translationGroup' => 'tg-99999999-9999-9999-9999-999999999999',
        ]);

        $this->assertNotNull($written, 'the page was written');
        $this->assertSame('page-doc', $written['uniqueId'], 'uniqueId comes from the existing file, not the payload');
        $this->assertSame(
            $keepGroup,
            $written['translationGroup'],
            'translationGroup is taken from the existing file, not the client payload'
        );
    }

    public function testUpdateStripsANonWhitelistedTranslationGroupFromTheExistingFile(): void {
        // The REJECT direction of the sanitizer whitelist: a bare 'grp-1' (not the
        // tg-<uuid> shape) carried on the EXISTING file does not survive
        // PageShapeSanitizer's whitelist and is stripped from the written page.
        // (testUpdatePreserves... pins the PASS direction with a well-formed group.)
        $written = null;
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('doc.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/doc/doc.json');
        $file->method('getId')->willReturn(42);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getMTime')->willReturn(1000);
        $file->method('getContent')->willReturn(json_encode([
            'uniqueId' => 'page-about',
            'title' => 'Old',
            'translationGroup' => 'grp-1',
        ]));
        $file->method('putContent')->willReturnCallback(function (string $json) use (&$written): bool {
            $written = json_decode($json, true);
            return true;
        });

        [$lang, $base] = $this->pageFixture('doc', 'page-about', $file);

        $svc = $this->makeWriteService([
            'userSession' => $this->userSessionWith($this->createMock(IUser::class)),
            'folders' => $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            'locator' => $this->fixtureLocator(),
            'shape' => $this->doubleOrBuild(PageShapeSanitizer::class),
        ]);

        $svc->updatePage('page-about', ['title' => 'New']);

        $this->assertNotNull($written, 'the page was written');
        $this->assertArrayNotHasKey(
            'translationGroup',
            $written,
            'a non-tg- translationGroup is stripped by validateAndSanitizePage'
        );
    }
}
