<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Exception\PageConflictException;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * A save built on a stale copy must be refused, not silently applied.
 *
 * Page JSON is written whole — putContent() replaces the entire document — so
 * the second of two concurrent saves did not merge, it erased everything the
 * first had written. PageLockService prevents the common case, but its locks
 * expire after 15 minutes without a heartbeat, so a tab left open comes back
 * holding stale content with no lock to stop it. These tests cover that gap.
 *
 * The version token is the FILE's mtime, deliberately not the `modified` field
 * inside the JSON: updatePage() never stamps that field, so it is whatever the
 * client last sent and comparing it would compare a value against itself.
 */
class PageConcurrencyTest extends TestCase {

    use BuildsPageService;

    /** Content written by the service, keyed by path. */
    private array $writes = [];

    protected function setUp(): void {
        $this->writes = [];
    }

    private function makeFile(string $path, array $json, int $mtime): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getId')->willReturn(abs(crc32($path)));
        $file->method('getMTime')->willReturn($mtime);
        $file->method('putContent')->willReturnCallback(function ($data) use ($path) {
            $this->writes[$path] = $data;
        });
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

    private function makeService(Folder $languageFolder): PageService {
        $base = $this->createMock(Folder::class);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willReturn([$languageFolder]);
        $base->method('get')->willReturnCallback(function ($p) use ($languageFolder) {
            if ($p === $languageFolder->getName()) {
                return $languageFolder;
            }
            throw new \OCP\Files\NotFoundException($p);
        });

        $svc = new class($languageFolder, $base) extends PageService {
            private Folder $languageFolder;
            private Folder $baseFolder;
            public function __construct(Folder $languageFolder, Folder $baseFolder) {
                $this->languageFolder = $languageFolder;
                $this->baseFolder = $baseFolder;
            }
            protected function getLanguageFolder() {
                return $this->languageFolder;
            }
            protected function getReadLanguageFolder(): Folder {
                return $this->languageFolder;
            }
            protected function getIntraVoxFolder() {
                return $this->baseFolder;
            }
            protected function createVersionBeforeUpdate($file): void {
            }
            public function clearCache(): void {
            }
        };

        $user = $this->createMock(\OCP\IUser::class);
        $user->method('getUID')->willReturn('tester');
        $user->method('getDisplayName')->willReturn('Tester');
        $session = $this->createMock(\OCP\IUserSession::class);
        $session->method('getUser')->willReturn($user);
        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('en');
        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturn(true);
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        $explicit = [
            'userSession' => $session,
            'userId' => 'tester',
            'config' => $config,
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $languageService,
        ];
        $this->injectPageServiceDependencies($svc, $explicit);
        return $svc;
    }

    /** Fixture: one page whose file on disk has mtime $mtime. */
    private function fixture(int $mtime = 2000): PageService {
        $page = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-conc', 'title' => 'About', 'widgets' => []],
            $mtime
        );
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $page,
            'about' => $this->makeFolder('/IntraVox/en/about', []),
        ]);
        return $this->makeService($en);
    }

    /**
     * The regression: editor B loaded the page before editor A saved it, so B's
     * baseVersion predates the file on disk. B's save must be refused rather
     * than erasing A's work.
     */
    public function testStaleSaveIsRefused(): void {
        $svc = $this->fixture(2000);

        $this->expectException(PageConflictException::class);
        $svc->updatePage('page-conc', [
            'title' => 'B overwrites A',
            'widgets' => [],
            'baseVersion' => 1000, // loaded before the file's current mtime
        ]);
    }

    /** …and nothing is written when it is refused. */
    public function testStaleSaveWritesNothing(): void {
        $svc = $this->fixture(2000);

        try {
            $svc->updatePage('page-conc', [
                'title' => 'B overwrites A',
                'widgets' => [],
                'baseVersion' => 1000,
            ]);
            $this->fail('a stale save must be refused');
        } catch (PageConflictException $e) {
            // expected
        }

        $this->assertSame([], $this->writes, 'a refused save must not touch the file');
    }

    /** A save from the current version goes through. */
    public function testCurrentSaveSucceeds(): void {
        $svc = $this->fixture(2000);

        $result = $svc->updatePage('page-conc', [
            'title' => 'Up to date',
            'widgets' => [],
            'baseVersion' => 2000,
        ]);

        $this->assertArrayHasKey('/IntraVox/en/about.json', $this->writes);
        $this->assertArrayHasKey(
            'baseVersion',
            $result,
            'the response must carry the new token so the next save is not a conflict with itself'
        );
    }

    /**
     * A client that sends no token is not blocked. Older frontends, scripts and
     * imports must keep working — this rejects a save that demonstrably started
     * from an older version, never one that merely failed to say.
     */
    public function testSaveWithoutTokenIsAllowed(): void {
        $svc = $this->fixture(2000);

        $svc->updatePage('page-conc', ['title' => 'No token', 'widgets' => []]);

        $this->assertArrayHasKey('/IntraVox/en/about.json', $this->writes);
    }

    /**
     * The token is transport-only: it must never be persisted into the page
     * JSON, where it would become a stale field that outlives the request.
     */
    public function testTokenIsNotPersistedIntoThePage(): void {
        $svc = $this->fixture(2000);

        $svc->updatePage('page-conc', [
            'title' => 'Clean',
            'widgets' => [],
            'baseVersion' => 2000,
        ]);

        $written = json_decode($this->writes['/IntraVox/en/about.json'], true);
        $this->assertArrayNotHasKey(
            'baseVersion',
            $written,
            'the concurrency token must not be stored in the page file'
        );
    }


}
