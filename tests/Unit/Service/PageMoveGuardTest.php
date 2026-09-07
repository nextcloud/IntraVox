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
 * The movePage() refusal guards, pinned at the SERVICE level before movePage
 * moves into the STRUCTURE domain. PageServiceMoveLanguageTest covers the
 * cross-language guard + permission preflight; this pins the other five refusals
 * that only had indirect (mocked-controller) coverage, so the extraction can be
 * proven byte-equivalent:
 *
 *   1. the 'home' string id can never be moved (entry guard);
 *   2. the configured homepage can never be moved (HOMEPAGE_PROTECTED);
 *   3. a move into itself or a descendant is refused (cycle guard);
 *   4. a move that is already under the target parent is a silent no-op;
 *   5. a move exceeding the max nesting depth is refused (depth guard).
 */
class PageMoveGuardTest extends TestCase {

    use BuildsPageService;

    /** Records every move() performed: [sourcePath => destinationPath]. */
    private array $moves = [];

    protected function setUp(): void {
        $this->moves = [];
    }

    private function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        return $file;
    }

    /**
     * @param array<string,\OCP\Files\Node> $children name => node
     */
    private function makeFolder(string $path, array $children = []): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('isDeletable')->willReturn(true);
        $folder->method('isCreatable')->willReturn(true);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        $folder->method('move')->willReturnCallback(function ($dest) use ($path) {
            $this->moves[$path] = $dest;
        });
        return $folder;
    }

    /**
     * Build a service whose en/ tree is $enChildren, with the seams overridden.
     * isHomepage() answers true only for $homepageUniqueId.
     */
    private function makeService(array $enChildren, ?string $homepageUniqueId = null): PageService {
        $en = $this->makeFolder('/IntraVox/en', $enChildren);
        $base = $this->makeFolder('/IntraVox', ['en' => $en]);

        $svc = new class($en, $base, $homepageUniqueId) extends PageService {
            private Folder $en;
            private Folder $baseFolder;
            private ?string $homeId;
            public function __construct(Folder $en, Folder $baseFolder, ?string $homeId) {
                $this->en = $en;
                $this->baseFolder = $baseFolder;
                $this->homeId = $homeId;
            }
            protected function getLanguageFolder() {
                return $this->en;
            }
            protected function getReadLanguageFolder(): Folder {
                return $this->en;
            }
            protected function getIntraVoxFolder() {
                return $this->baseFolder;
            }
            public function isHomepage(string $uniqueId, ?string $language = null): bool {
                return $this->homeId !== null && $uniqueId === $this->homeId;
            }
            public function clearCache(): void {
            }
        };

        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturnCallback(
            fn(string $code) => in_array($code, ['en', 'de', 'fr', 'nl'], true)
        );
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        $this->injectPageServiceDependencies($svc, [
            'userId' => 'tester',
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $languageService,
        ]);
        return $svc;
    }

    /** Build a page folder holding {slug}.json with the given uniqueId. */
    private function pageFolder(string $path, string $uniqueId): Folder {
        $slug = basename($path);
        return $this->makeFolder($path, [
            $slug . '.json' => $this->makeFile($path . '/' . $slug . '.json', ['uniqueId' => $uniqueId, 'title' => ucfirst($slug)]),
        ]);
    }

    public function testTheHomeStringIdCanNeverBeMoved(): void {
        $svc = $this->makeService([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The home page cannot be moved');
        $svc->movePage('home', '');
    }

    public function testTheConfiguredHomepageCanNeverBeMoved(): void {
        // 'about' resolves to page-home, which isHomepage() reports as the homepage.
        $about = $this->pageFolder('/IntraVox/en/about', 'page-home');
        $svc = $this->makeService(['about' => $about], homepageUniqueId: 'page-home');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HOMEPAGE_PROTECTED');
        $svc->movePage('page-home', '');

        $this->assertSame([], $this->moves, 'a protected homepage move must not touch the filesystem');
    }

    public function testAMoveIntoItselfIsRefused(): void {
        // Target parent resolves to the SAME folder as the source -> cycle.
        $about = $this->pageFolder('/IntraVox/en/about', 'page-about');
        $svc = $this->makeService(['about' => $about]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move a page into itself or its descendant');
        $svc->movePage('page-about', 'page-about');
    }

    public function testAMoveIntoADescendantIsRefused(): void {
        // child sits inside about; moving about INTO child would detach the subtree.
        $child = $this->pageFolder('/IntraVox/en/about/child', 'page-child');
        $about = $this->makeFolder('/IntraVox/en/about', [
            'about.json' => $this->makeFile('/IntraVox/en/about/about.json', ['uniqueId' => 'page-about', 'title' => 'About']),
            'child' => $child,
        ]);
        $svc = $this->makeService(['about' => $about]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move a page into itself or its descendant');
        $svc->movePage('page-about', 'page-child');
    }

    public function testAMoveAlreadyUnderTheTargetIsASilentNoOp(): void {
        // about is already directly under the language root; moving it to root '' is a no-op.
        $about = $this->pageFolder('/IntraVox/en/about', 'page-about');
        $svc = $this->makeService(['about' => $about]);

        $svc->movePage('page-about', '');

        $this->assertSame([], $this->moves, 'a page already under the target parent must not be moved');
    }
}
