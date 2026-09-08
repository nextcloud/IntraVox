<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for issue #90 — "Saving failed / Page not found".
 *
 * Reading and writing used to resolve the language folder differently: getPage()
 * searched the effective language plus every other language folder, while the
 * write paths searched only the folder for the user's own display language. A
 * page stored in en/ was therefore renderable but unsaveable for a user whose
 * Nextcloud language was de/ — the save failed with "Page not found" on a page
 * that was visibly on screen.
 *
 * Both sides now go through locatePageAnyLanguage(). These tests drive the
 * private locator through the public updatePage() entry point against a fake
 * IntraVox folder holding two language folders.
 *
 * Following PageServiceReorderTest, the instance is built without the real
 * (25-arg) constructor and the filesystem seams are overridden.
 */
class PageServiceCrossLanguageTest extends TestCase {

    use BuildsPageService;

    /** Build a page File mock that records putContent() calls. */
    private function makeFile(string $path, array $json, array &$writes): File {
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
    private function makeLanguageFolder(string $code, array $files): Folder {
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
                return $self->makeLanguageFolder($code . '/' . $name, []);
            }
        );
        return $folder;
    }

    /**
     * @param Folder $writeFolder the user's own language folder (write target)
     * @param Folder[] $allLanguages every language folder under /IntraVox
     */
    private function makeService(Folder $writeFolder, array $allLanguages): PageService {
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

        // updatePage resolves its write-target ($writeFolder) via
        // folders()->languageFolder(), and languageOfFolder + userLanguage via the
        // intraVox() root ($base) — all through the injected FolderContext below.
        $svc = new class extends PageService {
            // Deliberately bypass the real 25-arg constructor.
            public function __construct() {
            }
            // Isolate from version creation, events, navigation sync and caches.
            protected function createVersionBeforeUpdate($file): void {
            }
            public function clearCache(): void {
            }
        };

        // updatePage() needs a session user and a uid before it resolves the
        // page. Both properties are private on PageService, so they are injected
        // here rather than widening production visibility for a test's sake.
        $user = $this->createMock(\OCP\IUser::class);
        $user->method('getUID')->willReturn('tester');
        $user->method('getDisplayName')->willReturn('Tester');
        $session = $this->createMock(\OCP\IUserSession::class);
        $session->method('getUser')->willReturn($user);

        // Display language 'de' — the user's own language folder.
        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('de');

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        // Real installs know these codes; the auto-mock would answer false for
        // every language and make path building skip the language segment.
        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturnCallback(
            fn(string $code) => in_array($code, ['en', 'de', 'fr', 'nl'], true)
        );
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        $explicit = [
            'userSession' => $session,
            'userId' => 'tester',
            'config' => $config,
            'logger' => $logger,
            'languageService' => $languageService,
            'folderContext' => $this->fakeFolderContext(
                intraVox: $base,
                languageFolder: $writeFolder,
                userLanguage: 'de'
            ),
        ];
        $this->injectPageServiceDependencies($svc, $explicit);

        return $svc;
    }

    /**
     * The issue #90 scenario: the page lives in en/, the user writes from de/.
     * Before the fix this threw "Page not found: page-issue90".
     */
    public function testUpdateFindsPageInAnotherLanguageFolder(): void {
        $writes = [];
        $page = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-issue90', 'title' => 'About', 'widgets' => []],
            $writes
        );

        $en = $this->makeLanguageFolder('en', [$page]);
        $de = $this->makeLanguageFolder('de', []); // user's own language: empty

        $svc = $this->makeService($de, [$de, $en]);
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
        $dePage = $this->makeFile(
            '/IntraVox/de/about.json',
            ['uniqueId' => 'page-shared', 'title' => 'Über uns', 'widgets' => []],
            $writes
        );
        $enPage = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-shared', 'title' => 'About', 'widgets' => []],
            $writes
        );

        $de = $this->makeLanguageFolder('de', [$dePage]);
        $en = $this->makeLanguageFolder('en', [$enPage]);

        $svc = $this->makeService($de, [$de, $en]);
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
        $en = $this->makeLanguageFolder('en', []);
        $de = $this->makeLanguageFolder('de', []);

        $svc = $this->makeService($de, [$de, $en]);

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
        $page = $this->makeFile(
            '/IntraVox/en/home.json',
            ['uniqueId' => 'page-4e98ddf1', 'title' => 'Home', 'widgets' => []],
            $writes
        );
        $en = $this->makeLanguageFolder('en', [$page]);

        // Only en/ exists; getLanguageFolder() already resolved to it.
        $svc = $this->makeService($en, [$en]);
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
        $enPage = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-4e98ddf1', 'title' => 'About', 'widgets' => []],
            $writes
        );
        $dePage = $this->makeFile(
            '/IntraVox/de/vorlage.json',
            ['uniqueId' => 'page-vorlage', 'title' => 'Vorlage', 'widgets' => []],
            $writes
        );
        $en = $this->makeLanguageFolder('en', [$enPage]);
        $de = $this->makeLanguageFolder('de', [$dePage]);

        // German user: write target is de/, but the page lives in en/.
        $svc = $this->makeService($de, [$de, $en]);
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
        $writes = [];
        $de = $this->makeLanguageFolder('de', []);
        $en = $this->makeLanguageFolder('en', []);

        // Author's own language folder is en/ (profile language), parent is de/.
        $svc = $this->makeService($en, [$de, $en]);
        $target = $this->callGetOrCreateFolderPath($svc, 'de/abteilungen');

        $this->assertSame(
            '/IntraVox/de/abteilungen',
            $target->getPath(),
            'a sub-page must be created under the parent language folder'
        );
    }

    /** An unknown language segment falls back to the author's own folder. */
    public function testUnknownLanguageSegmentFallsBackToAuthorFolder(): void {
        $de = $this->makeLanguageFolder('de', []);
        $en = $this->makeLanguageFolder('en', []);
        $svc = $this->makeService($en, [$de, $en]);

        // 'zz' is not an available language -> treated as a normal path segment
        // below the author's own language folder.
        $target = $this->callGetOrCreateFolderPath($svc, 'zz/team');
        $this->assertSame('/IntraVox/en/zz/team', $target->getPath());
    }

    /** Drive the private getOrCreateFolderPath() through reflection. */
    private function callGetOrCreateFolderPath(PageService $svc, string $path): Folder {
        $m = new \ReflectionMethod(PageService::class, 'getOrCreateFolderPath');
        return $m->invoke($svc, $path);
    }


}
