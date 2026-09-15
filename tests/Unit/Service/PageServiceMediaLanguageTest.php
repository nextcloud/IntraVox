<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsCacheFixtures;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsFolderFixtures;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsServiceDoubles;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for issue #92 — "Upload failed: page not found".
 *
 * Issue #90 made pages resolve across every language folder, but the media
 * cluster kept picking a language folder for the USER: getLanguageFolder() (the
 * profile language) on the upload paths, getReadLanguageFolder() (own →
 * recommended → en) on the listing path — and then looked for the page only
 * there. Media lives next to its page, so the only folder that matters is the
 * one holding the page.
 *
 * When those disagreed, every media operation failed on a page that was plainly
 * on screen: the upload threw "Page not found" even though the same request had
 * already passed its permission check through the cross-language getPage(), and
 * the Shared Library listed nothing so previews stayed blank.
 *
 * All media paths now resolve through locatePageForMedia(). These tests drive
 * them through the public entry points against a fake IntraVox folder holding
 * two language folders, following PageServiceCrossLanguageTest.
 */
class PageServiceMediaLanguageTest extends TestCase {

    use BuildsFolderFixtures;

    use BuildsServiceDoubles;

    use BuildsCacheFixtures;

    /** A page JSON file. */
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

    /** A media file inside a _media / _resources folder. */
    private function makeMediaFile(string $path, string $mime = 'image/png'): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        // One declaration covers both spellings — see the File stub.
        $file->method('getMimetype')->willReturn($mime);
        $file->method('getSize')->willReturn(1234);
        $file->method('getMTime')->willReturn(1700000000);
        return $file;
    }

    /**
     * A generic folder that resolves the children it holds and records the
     * files created inside it, so "which folder was written to" is assertable.
     *
     * @param array $children name => node
     */
    private function makeFolder(string $path, array $children, array &$created = []): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        $folder->method('newFile')->willReturnCallback(
            function ($name) use ($path, &$created) {
                $created[] = $path . '/' . $name;
                $new = $this->createMock(File::class);
                $new->method('getName')->willReturn($name);
                $new->method('getPath')->willReturn($path . '/' . $name);
                return $new;
            }
        );
        $folder->method('newFolder')->willReturnCallback(
            function ($name) use ($path, &$created) {
                $created[] = $path . '/' . $name . '/';
                return $this->makeFolder($path . '/' . $name, [], $created);
            }
        );
        return $folder;
    }

    /**
     * The media orchestrator over the cross-language fixture — the real owner of the
     * #92 cross-language resolution. Every media op (getMediaList / checkMediaExists /
     * uploadMedia / uploadMediaWithOriginalName) is driven through it directly now:
     * their PageService facades were retired (read ones in fase-4, uploads in the
     * fase-6 consumer campaign).
     *
     * @param array<int,Folder> $allLanguages
     */
    private function mediaOrchestrator(Folder $readFolder, array $allLanguages): \OCA\IntraVox\Service\Media\PageMediaOrchestrator {
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
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $locator = new \OCA\IntraVox\Service\Locator\PageLocator(
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $logger
        );
        $sanitizer = $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\MediaSanitizer::class);
        // The engine is REAL (not a mock): checkMediaExists/getMediaList read the
        // fixture folders through it, exactly as the old buildRealPageService path
        // built it via the media() lazy accessor.
        $engine = new \OCA\IntraVox\Service\Media\PageMediaService($locator, $sanitizer, $logger);
        return new \OCA\IntraVox\Service\Media\PageMediaOrchestrator(
            $engine,
            $this->createMock(\OCA\IntraVox\Service\Cache\PageCacheService::class),
            $this->fakeFolderContext(
                readLanguageFolder: $readFolder,
                intraVox: $base,
                userLanguage: 'de',
                primaryLanguage: 'en'
            ),
            $locator,
            new \OCA\IntraVox\Service\Util\PageIdUtils(),
            $sanitizer,
            $this->fakeCacheInvalidator()
        );
    }

    /**
     * A PNG on disk, so uploads pass MIME sniffing and image validation.
     * @return array the $_FILES-shaped array uploadMedia* expects
     */
    private function makeUpload(string $name = 'photo.png'): array {
        $tmp = tempnam(sys_get_temp_dir(), 'ivtest');
        // Smallest valid 1x1 PNG.
        file_put_contents($tmp, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        return ['name' => $name, 'tmp_name' => $tmp, 'size' => filesize($tmp), 'error' => UPLOAD_ERR_OK];
    }

    /**
     * Build the two-language fixture used by most tests: the page lives in en/,
     * the user reads de/. Returns [service, createdPaths].
     */
    private function twoLanguageFixture(array &$created): \OCA\IntraVox\Service\Media\PageMediaOrchestrator {
        $pageJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-issue92', 'title' => 'About', 'widgets' => []]
        );
        // about.json has a matching about/ subfolder, so that folder is the
        // page folder and its _media is where the upload must land.
        $pageFolder = $this->makeFolder('/IntraVox/en/about', [], $created);
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $pageJson,
            'about' => $pageFolder,
        ], $created);
        $de = $this->makeFolder('/IntraVox/de', [], $created);

        // fase-6 consumer campaign: uploadMedia* moved off PageService — the upload
        // tests drive PageMediaOrchestrator directly (its real owner), like the
        // read tests already did.
        return $this->mediaOrchestrator($de, [$de, $en]);
    }

    /**
     * Case A from the issue: the upload must find the page in en/ and create
     * the media folder there. Before the fix this threw "Page not found".
     */
    public function testUploadFindsPageInAnotherLanguageFolder(): void {
        $created = [];
        $svc = $this->twoLanguageFixture($created);

        $filename = $svc->uploadMedia('page-issue92', $this->makeUpload());

        $this->assertNotEmpty($filename);
        $this->assertContains(
            '/IntraVox/en/about/_media/',
            $created,
            'media must be created under the page\'s own language folder, not the reader\'s'
        );
    }

    /** The same for the original-filename upload path used by the picker. */
    public function testUploadWithOriginalNameFindsPageInAnotherLanguage(): void {
        $created = [];
        $svc = $this->twoLanguageFixture($created);

        $result = $svc->uploadMediaWithOriginalName(
            'page-issue92',
            $this->makeUpload('holiday.png'),
            'page',
            false
        );

        $this->assertSame('holiday.png', $result['filename'] ?? null);
        $this->assertContains('/IntraVox/en/about/_media/', $created);
    }

    /**
     * A genuinely unknown page must still be reported as not found — and as a
     * PageNotFoundException, so the controller answers 404 with a log line
     * instead of the unlogged 500 that left issue #92 with no log entries.
     */
    public function testUploadStillFailsForUnknownPage(): void {
        $created = [];
        $en = $this->makeFolder('/IntraVox/en', [], $created);
        $de = $this->makeFolder('/IntraVox/de', [], $created);
        $svc = $this->mediaOrchestrator($de, [$de, $en]);

        $this->expectException(PageNotFoundException::class);
        $svc->uploadMediaWithOriginalName('page-nope', $this->makeUpload(), 'page', false);
    }

    /**
     * Case B: the Shared Library listed from the reader's language while the
     * widget resolved images from the page's, so it showed names whose previews
     * always 404'd. The listing must come from the page's own language.
     */
    public function testResourcesListingComesFromThePagesLanguage(): void {
        $created = [];
        $asset = $this->makeMediaFile('/IntraVox/en/_resources/logo.png');
        $enResources = $this->makeFolder('/IntraVox/en/_resources', ['logo.png' => $asset], $created);
        $pageJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-issue92', 'title' => 'About', 'widgets' => []]
        );
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $pageJson,
            '_resources' => $enResources,
        ], $created);
        // The reader's own language has an EMPTY shared library; listing from
        // here is exactly the bug.
        $de = $this->makeFolder('/IntraVox/de', [
            '_resources' => $this->makeFolder('/IntraVox/de/_resources', [], $created),
        ], $created);

        $svc = $this->mediaOrchestrator($de, [$de, $en]);
        $list = $svc->getMediaList('page-issue92', 'resources', '');

        $this->assertCount(1, $list, 'the shared library of the page\'s language must be listed');
        $this->assertSame('logo.png', $list[0]['name']);
    }

    /**
     * The duplicate check must inspect the same folder the upload writes to.
     * When it did not, it answered "no duplicate" for a file that was about to
     * be overwritten — or prompted about one the upload would never touch.
     */
    public function testDuplicateCheckLooksInThePagesLanguage(): void {
        $created = [];
        $existing = $this->makeMediaFile('/IntraVox/en/about/_media/photo.png');
        $mediaFolder = $this->makeFolder('/IntraVox/en/about/_media', ['photo.png' => $existing], $created);
        $pageFolder = $this->makeFolder('/IntraVox/en/about', ['_media' => $mediaFolder], $created);
        $pageJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-issue92', 'title' => 'About', 'widgets' => []]
        );
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $pageJson,
            'about' => $pageFolder,
        ], $created);
        $de = $this->makeFolder('/IntraVox/de', [], $created);

        $svc = $this->mediaOrchestrator($de, [$de, $en]);

        $this->assertTrue(
            $svc->checkMediaExists('page-issue92', 'photo.png', 'page'),
            'an existing file in the page\'s own language must be detected'
        );
        $this->assertFalse(
            $svc->checkMediaExists('page-issue92', 'absent.png', 'page')
        );
    }

    /**
     * The page in the reader's own language still wins, so the cross-language
     * scan never steals a same-uniqueId page from under a local one.
     */
    public function testOwnLanguageStillTakesPrecedence(): void {
        $created = [];
        $dePageFolder = $this->makeFolder('/IntraVox/de/about', [], $created);
        $de = $this->makeFolder('/IntraVox/de', [
            'about.json' => $this->makeFile(
                '/IntraVox/de/about.json',
                ['uniqueId' => 'page-shared', 'title' => 'Über uns', 'widgets' => []]
            ),
            'about' => $dePageFolder,
        ], $created);

        $enPageFolder = $this->makeFolder('/IntraVox/en/about', [], $created);
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $this->makeFile(
                '/IntraVox/en/about.json',
                ['uniqueId' => 'page-shared', 'title' => 'About', 'widgets' => []]
            ),
            'about' => $enPageFolder,
        ], $created);

        $svc = $this->mediaOrchestrator($de, [$de, $en]);
        $svc->uploadMedia('page-shared', $this->makeUpload());

        $this->assertContains('/IntraVox/de/about/_media/', $created);
        $this->assertNotContains(
            '/IntraVox/en/about/_media/',
            $created,
            'the local language folder must take precedence over the cross-language scan'
        );
    }

    /**
     * The home page's media lives in the language folder itself rather than in
     * a page subfolder, so the language must be derived from that folder too.
     */
    public function testHomePageMediaResolvesToItsOwnLanguageFolder(): void {
        $created = [];
        $homeJson = $this->makeFile(
            '/IntraVox/en/home.json',
            ['uniqueId' => 'page-home92', 'title' => 'Home', 'widgets' => []]
        );
        $en = $this->makeFolder('/IntraVox/en', ['home.json' => $homeJson], $created);
        $de = $this->makeFolder('/IntraVox/de', [], $created);

        $svc = $this->mediaOrchestrator($de, [$de, $en]);
        $svc->uploadMedia('page-home92', $this->makeUpload());

        $this->assertContains(
            '/IntraVox/en/_media/',
            $created,
            'home media belongs to the language folder holding home.json'
        );
    }


}
