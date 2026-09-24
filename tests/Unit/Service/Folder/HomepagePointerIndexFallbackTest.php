<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Folder;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;

/**
 * The homepage pointer resolves through the index, and never widens what the
 * walk would have found.
 *
 * A language folder may carry a `homepage.json` pointing at the page that serves
 * as its homepage. Resolving that pointer used to mean findPageByUniqueId() — a
 * recursive walk of the whole language tree. Measured on dev: 302ms on an `nl`
 * of 429 folders. And it is not paid once: effectiveLanguage() probes each
 * candidate language to decide which folder to READ from, so every authenticated
 * request paid it before doing any work of its own. One page load fires eight
 * API calls, and each one paid it again.
 *
 * The index answers the same question in one query. What these tests pin is that
 * it stays an answer to the SAME question:
 *
 *  - the index resolves the pointer and the walk does not run (the regression
 *    test for the 302ms — it fails the moment someone reinstates the walk);
 *  - a stale or unknown index falls through to the walk, which still decides;
 *  - a throwing index is survivable and does not silently skip to home.json;
 *  - an index hit OUTSIDE the language folder is refused, because
 *    findByUniqueId() treats language as a tie-break rather than a filter and
 *    UNIQUE(unique_id, language) permits one id in two languages. Without that
 *    guard the fast path could resolve where the scoped walk finds nothing,
 *    flipping hasRealContent() for the folder — a behaviour change, not a
 *    speed-up;
 *  - the LANGUAGE folder is passed as locateViaIndex's primary folder, not the
 *    root. The root would make the lookup language-agnostic, which is right for
 *    a public share link and wrong here: this probe is per-language by
 *    definition.
 */
class HomepagePointerIndexFallbackTest extends TestCase {
    private const UID = 'page-7fe1d5d9';

    /** A JSON file mock that reports $json when read. */
    private function jsonFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));

        return $file;
    }

    /**
     * A language folder holding a homepage.json pointer at self::UID, and
     * nothing else — so if the pointer does not resolve, steps 2 and 3 find no
     * home.json and the whole resolve returns null. That makes "the pointer
     * resolved" observable as a non-null result.
     */
    private function langFolder(string $path = '/IntraVox/nl'): Folder {
        $children = [
            'homepage.json' => $this->jsonFile($path . '/homepage.json', ['homepageUniqueId' => self::UID]),
        ];

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
            throw new NotFoundException($path . '/' . $p);
        });

        return $folder;
    }

    /** The page the pointer designates, as either route returns it. */
    private function target(string $path): array {
        return [
            'file' => $this->jsonFile($path, ['uniqueId' => self::UID, 'title' => 'Over IntraVox']),
            'folder' => $this->createMock(Folder::class),
            'isHome' => false,
        ];
    }

    private function context(PageLocator $locator, Folder $base): FolderContext {
        $config = $this->createMock(IConfig::class);
        $config->method('getUserValue')->willReturn('nl');

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('getPrimaryLanguage')->willReturn('nl');

        return new FolderContext(
            $this->createMock(IRootFolder::class),
            'tester',
            $config,
            $languageService,
            new LanguageResolver(),
            $locator,
            $base // intraVoxOverride: skip the real mount walk
        );
    }

    /** The /IntraVox base folder holding one language. */
    private function baseFolder(Folder $nl): Folder {
        $base = $this->createMock(Folder::class);
        $base->method('getName')->willReturn('IntraVox');
        $base->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('nodeExists')->willReturnCallback(fn($n) => $n === 'nl');
        $base->method('get')->willReturnCallback(function ($c) use ($nl) {
            if ($c === 'nl') {
                return $nl;
            }
            throw new NotFoundException('/IntraVox/' . $c);
        });

        return $base;
    }

    private function resolve(FolderContext $ctx, Folder $langFolder): ?array {
        $m = new \ReflectionMethod(FolderContext::class, 'resolveLanguageHomepageDataUncached');

        return $m->invoke($ctx, $langFolder);
    }

    /**
     * The regression test for the 302ms: one index query answers, and the tree
     * walk is never touched.
     */
    public function testTheIndexResolvesThePointerWithoutTheWalk(): void {
        $nl = $this->langFolder();

        $locator = $this->createMock(PageLocator::class);
        $locator->expects($this->once())
            ->method('locateViaIndex')
            ->willReturn($this->target('/IntraVox/nl/over-intravox/over-intravox.json'));
        $locator->expects($this->never())->method('findPageByUniqueId');

        $data = $this->resolve($this->context($locator, $this->baseFolder($nl)), $nl);

        $this->assertNotNull($data);
        $this->assertSame('Over IntraVox', $data['title']);
    }

    /** A stale or unknown index hands the decision back to the walk. */
    public function testAStaleIndexFallsThroughToTheWalk(): void {
        $nl = $this->langFolder();

        $locator = $this->createMock(PageLocator::class);
        $locator->method('locateViaIndex')->willReturn(null);
        $locator->expects($this->once())
            ->method('findPageByUniqueId')
            ->willReturn($this->target('/IntraVox/nl/over-intravox/over-intravox.json'));

        $data = $this->resolve($this->context($locator, $this->baseFolder($nl)), $nl);

        $this->assertNotNull($data);
        $this->assertSame('Over IntraVox', $data['title']);
    }

    /**
     * An index that throws must not take the pointer down with it: the walk
     * still runs, rather than the resolve falling through to home.json.
     */
    public function testAThrowingIndexStillResolvesViaTheWalk(): void {
        $nl = $this->langFolder();

        $locator = $this->createMock(PageLocator::class);
        $locator->method('locateViaIndex')->willThrowException(new \RuntimeException('db down'));
        $locator->method('findPageByUniqueId')
            ->willReturn($this->target('/IntraVox/nl/over-intravox/over-intravox.json'));

        $data = $this->resolve($this->context($locator, $this->baseFolder($nl)), $nl);

        $this->assertNotNull($data);
        $this->assertSame('Over IntraVox', $data['title']);
    }

    /**
     * An index hit in ANOTHER language folder is refused, and the scoped walk
     * decides instead. Here the walk finds nothing, which is what today's
     * behaviour produces — the fast path must not widen it.
     */
    public function testACrossLanguageIndexHitIsRefused(): void {
        $nl = $this->langFolder();

        $locator = $this->createMock(PageLocator::class);
        // Same uniqueId, but the file lives under /IntraVox/en.
        $locator->method('locateViaIndex')
            ->willReturn($this->target('/IntraVox/en/about/about.json'));
        $locator->expects($this->once())->method('findPageByUniqueId')->willReturn(null);

        $data = $this->resolve($this->context($locator, $this->baseFolder($nl)), $nl);

        $this->assertNull($data);
    }

    /**
     * The LANGUAGE folder is the primary folder, so the index keeps its
     * per-language tie-break. Passing the root would drop that preference —
     * correct for a share link, wrong for this probe.
     */
    public function testTheLanguageFolderIsPassedAsPrimaryFolder(): void {
        $nl = $this->langFolder();
        $seen = null;

        $locator = $this->createMock(PageLocator::class);
        $locator->method('locateViaIndex')
            ->willReturnCallback(function ($root, $uid, $primary) use (&$seen) {
                $seen = $primary;
                $this->assertSame(self::UID, $uid);

                return $this->target('/IntraVox/nl/over-intravox/over-intravox.json');
            });

        $this->resolve($this->context($locator, $this->baseFolder($nl)), $nl);

        $this->assertSame($nl, $seen, 'the probed language folder must be the primary folder');
    }
}
