<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * The page index must record the language a page LIVES in, never the language
 * of whoever happened to save it.
 *
 * The index is keyed (unique_id, language) — a composite unique key. When
 * updatePage() recorded the editor's own language instead of the page's, the
 * UPDATE matched zero rows and fell through to INSERT, so every save by an
 * editor working outside their own language created a duplicate row under a
 * language the page was never in. Nothing cleaned those up, and the read paths
 * that are about to be built on this index would have inherited the mess.
 *
 * This became reachable with #90: before that an editor could not save a page
 * outside their own language at all, so the bug was latent.
 */
class PageIndexLanguageTest extends TestCase {

    use BuildsPageService;

    /** Calls recorded from PageIndexService::indexPage(). */
    private array $indexed = [];

    /** Whether clearAll() was called (the rebuild wipes before repopulating). */
    private bool $indexCleared = false;

    protected function setUp(): void {
        $this->indexed = [];
        $this->indexCleared = false;
    }

    private function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('isUpdateable')->willReturn(true);
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
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        return $folder;
    }

    private function makeService(Folder $userLanguageFolder, array $allLanguages): PageService {
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
            throw new \OCP\Files\NotFoundException($p);
        });

        // updatePage resolves its write-target ($userLanguageFolder) via
        // folders()->languageFolder() and languageOfFolder/userLanguage via the
        // intraVox() root ($base); rebuildIndex uses folders()->intraVox().
        // Record what the index is told, without a database.
        $index = $this->createMock(PageIndexService::class);
        $index->method('indexPage')->willReturnCallback(
            function (array $pageData, string $language, string $path, ?int $fileId = null) {
                $this->indexed[] = [
                    'uniqueId' => $pageData['uniqueId'] ?? null,
                    'language' => $language,
                    'path' => $path,
                ];
            }
        );
        $index->method('clearAll')->willReturnCallback(function () {
            $this->indexCleared = true;
        });

        $user = $this->createMock(\OCP\IUser::class);
        $user->method('getUID')->willReturn('tester');
        $user->method('getDisplayName')->willReturn('Tester');
        $session = $this->createMock(\OCP\IUserSession::class);
        $session->method('getUser')->willReturn($user);

        // The editor's own Nextcloud language is German.
        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('de');

        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturnCallback(
            fn(string $code) => in_array($code, ['en', 'de', 'fr', 'nl'], true)
        );
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        $explicit = [
            'userSession' => $session,
            'userId' => 'tester',
            'config' => $config,
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $languageService,
            'pageIndexService' => $index,
            'folderContext' => $this->fakeFolderContext(
                intraVox: $base,
                languageFolder: $userLanguageFolder,
                userLanguage: 'de'
            ),
        ];
        // Built through the real DI ctor (fase-3); the inert PageCacheInvalidator
        // no-ops clearCache.
        return $this->buildRealPageService($explicit);
    }

    /**
     * The regression: a German editor saves an English page. The index must
     * record 'en' — where the page is — not 'de', where the editor is.
     */
    public function testIndexRecordsThePagesLanguageNotTheEditors(): void {
        $pageJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-idx1', 'title' => 'About', 'widgets' => []]
        );
        $pageFolder = $this->makeFolder('/IntraVox/en/about', []);
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $pageJson,
            'about' => $pageFolder,
        ]);
        $de = $this->makeFolder('/IntraVox/de', []);

        // Editor's own language folder is de/; the page lives in en/.
        $svc = $this->makeService($de, [$de, $en]);
        $svc->updatePage('page-idx1', ['title' => 'About us', 'widgets' => []]);

        $this->assertCount(1, $this->indexed, 'the save should index exactly once');
        $this->assertSame(
            'en',
            $this->indexed[0]['language'],
            'the index must record the language the page lives in, not the editor\'s own'
        );
    }

    /**
     * The ordinary case must be unchanged: same language on both sides still
     * indexes under that language.
     */
    public function testIndexUnchangedWhenEditorAndPageShareALanguage(): void {
        $pageJson = $this->makeFile(
            '/IntraVox/de/ueber-uns.json',
            ['uniqueId' => 'page-idx2', 'title' => 'Über uns', 'widgets' => []]
        );
        $pageFolder = $this->makeFolder('/IntraVox/de/ueber-uns', []);
        $de = $this->makeFolder('/IntraVox/de', [
            'ueber-uns.json' => $pageJson,
            'ueber-uns' => $pageFolder,
        ]);
        $en = $this->makeFolder('/IntraVox/en', []);

        $svc = $this->makeService($de, [$de, $en]);
        $svc->updatePage('page-idx2', ['title' => 'Über uns alle', 'widgets' => []]);

        $this->assertCount(1, $this->indexed);
        $this->assertSame('de', $this->indexed[0]['language']);
    }

    /**
     * The rebuild walks the real tree and records what the files say — one
     * entry per page, under the language folder it actually sits in.
     */
    public function testRebuildIndexesEveryPageUnderItsOwnLanguage(): void {
        $en = $this->makeFolder('/IntraVox/en', [
            'home.json' => $this->makeFile('/IntraVox/en/home.json', ['uniqueId' => 'page-en-home', 'title' => 'Home']),
            // Canonical page shape: {slug}/{slug}.json.
            'about' => $this->makeFolder('/IntraVox/en/about', [
                'about.json' => $this->makeFile('/IntraVox/en/about/about.json', ['uniqueId' => 'page-en-about', 'title' => 'About']),
            ]),
            // A LOOSE json beside real pages is not a page: the tree cannot
            // show it and getPage cannot resolve it, so indexing it created
            // ghost list entries that 404 when clicked (the en/fleet POC files
            // on dev, found by the J3 test round).
            'stray-poc.json' => $this->makeFile('/IntraVox/en/stray-poc.json', ['uniqueId' => 'page-loose-poc', 'title' => 'POC']),
            // Per-language config files are not pages and must be skipped.
            'navigation.json' => $this->makeFile('/IntraVox/en/navigation.json', ['items' => []]),
            'footer.json' => $this->makeFile('/IntraVox/en/footer.json', ['columns' => []]),
            // Asset folders hold no pages.
            '_media' => $this->makeFolder('/IntraVox/en/_media', [
                'stray.json' => $this->makeFile('/IntraVox/en/_media/stray.json', ['uniqueId' => 'page-should-not-index']),
            ]),
        ]);
        $de = $this->makeFolder('/IntraVox/de', [
            'home.json' => $this->makeFile('/IntraVox/de/home.json', ['uniqueId' => 'page-de-home', 'title' => 'Startseite']),
        ]);

        $svc = $this->makeService($de, [$de, $en]);
        $stats = $svc->rebuildIndex();

        $this->assertSame(3, $stats['indexed'], 'three real pages, config files excluded');
        $this->assertSame(['de' => 1, 'en' => 2], $stats['languages']);

        $byId = [];
        foreach ($this->indexed as $row) {
            $byId[$row['uniqueId']] = $row['language'];
        }
        $this->assertSame('en', $byId['page-en-about'] ?? null);
        $this->assertSame('de', $byId['page-de-home'] ?? null);
        $this->assertArrayNotHasKey(
            'page-should-not-index',
            $byId,
            'JSON inside _media is not a page'
        );
        $this->assertArrayNotHasKey(
            'page-loose-poc',
            $byId,
            'a loose JSON without its own page folder is not a page'
        );
    }

    /** A dry run reports the same counts but writes nothing. */
    public function testRebuildDryRunWritesNothing(): void {
        $en = $this->makeFolder('/IntraVox/en', [
            'home.json' => $this->makeFile('/IntraVox/en/home.json', ['uniqueId' => 'page-dry', 'title' => 'Home']),
        ]);

        $svc = $this->makeService($en, [$en]);
        $stats = $svc->rebuildIndex(true);

        $this->assertSame(1, $stats['indexed'], 'a dry run still reports what it would do');
        $this->assertSame([], $this->indexed, 'a dry run must not touch the index');
        $this->assertFalse($this->indexCleared, 'a dry run must not clear the index');
    }

    /**
     * A JSON file without a uniqueId is counted as scanned but not indexed, so
     * the command can report the gap instead of silently dropping it.
     */
    public function testRebuildSkipsFilesWithoutAUniqueId(): void {
        $en = $this->makeFolder('/IntraVox/en', [
            'home.json' => $this->makeFile('/IntraVox/en/home.json', ['uniqueId' => 'page-ok', 'title' => 'Home']),
            // Page-model file (own folder) without a uniqueId: scanned, not indexed.
            'broken' => $this->makeFolder('/IntraVox/en/broken', [
                'broken.json' => $this->makeFile('/IntraVox/en/broken/broken.json', ['title' => 'No id']),
            ]),
        ]);

        $svc = $this->makeService($en, [$en]);
        $stats = $svc->rebuildIndex();

        $this->assertSame(2, $stats['scanned']);
        $this->assertSame(1, $stats['indexed']);
    }

    /**
     * A page whose folder sits outside any language folder (legacy layouts,
     * odd mounts) must still index rather than throw — the index update is
     * explicitly non-blocking, the page is already saved by this point.
     */
    public function testIndexFallsBackToTheEditorLanguageWhenPathHasNone(): void {
        $pageJson = $this->makeFile(
            '/IntraVox/loose.json',
            ['uniqueId' => 'page-idx3', 'title' => 'Loose', 'widgets' => []]
        );
        // The page folder IS the IntraVox root — languageOfFolder() returns null.
        $root = $this->makeFolder('/IntraVox', [
            'loose.json' => $pageJson,
        ]);

        $svc = $this->makeService($root, [$root]);
        $svc->updatePage('page-idx3', ['title' => 'Loose page', 'widgets' => []]);

        $this->assertCount(1, $this->indexed);
        $this->assertSame(
            'de',
            $this->indexed[0]['language'],
            'with no language in the path, fall back to the editor\'s own'
        );
    }


}
