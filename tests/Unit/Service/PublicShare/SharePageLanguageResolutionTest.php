<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\PublicShare;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PublicShare\SharePageIndexResolver;
use OCA\IntraVox\Service\PublicShareService;
use OCA\IntraVox\Service\SetupService;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A public link names a page, never a language — so the resolver has to find it.
 *
 * It used to guess. getPageByShare() called PageReadService::getPage() purely to
 * read the language, that read resolved through the session user's mount, an
 * anonymous visitor has no session user, and the guess fell back to 'en'. The
 * resolver then searched the 'en' tree first (581 folders on the instance this
 * was measured on), missed, and swept every language folder in turn before
 * reaching the Dutch page. Every public page took 2.2 seconds. So did a page id
 * that did not exist at all, because a wrong guess costs the same to disprove.
 *
 * The index already answers the question directly — findByUniqueId() keys on
 * unique_id alone — so resolveSharePage() asks it once, language-agnostically,
 * and keeps the walks as the fallback they always were.
 *
 * What these tests pin:
 *
 *  - the index resolves a page in a language nobody asked for, WITHOUT touching
 *    another language tree (the regression test for the 2.2s);
 *  - a stale index still falls through to the walk, because locateViaIndex()
 *    verifies the uniqueId it finds;
 *  - no locator at all still resolves, via the sweep;
 *  - the language reported back is the one the page was FOUND in, not the one
 *    the caller guessed — the breadcrumb is built from it.
 */
class SharePageLanguageResolutionTest extends TestCase {
    private const UID = 'page-39cde8e7';

    /** A page JSON file that reports $uniqueId when read. */
    private function pageFile(string $uniqueId, string $path): File {
        $file = $this->createMock(File::class);
        $file->method('getContent')->willReturn(json_encode([
            'uniqueId' => $uniqueId,
            'title' => 'Mastodon',
        ]));
        $file->method('getPath')->willReturn($path);

        return $file;
    }

    /**
     * A service whose IntraVox root holds the given language folders.
     *
     * The root doubles as the argument locateViaIndex() receives for BOTH root
     * and primary folder — that is what makes the index lookup language-
     * agnostic, because languageOfFolder() returns null when the two are the
     * same node.
     *
     * @param array<string,Folder> $languages folder name => folder mock
     */
    private function service(?PageLocator $locator, array $languages = []): PublicShareService {
        $root = $this->createMock(Folder::class);
        $root->method('getPath')->willReturn('/IntraVox');
        $root->method('nodeExists')->willReturnCallback(
            fn(string $name) => isset($languages[$name])
        );
        $root->method('get')->willReturnCallback(function (string $name) use ($languages) {
            if (!isset($languages[$name])) {
                throw new \OCP\Files\NotFoundException($name);
            }
            return $languages[$name];
        });
        $root->method('getDirectoryListing')->willReturn(array_values($languages));

        $setup = $this->createMock(SetupService::class);
        $setup->method('getSharedFolder')->willReturn($root);

        $logger = $this->createMock(LoggerInterface::class);

        $svc = (new \ReflectionClass(PublicShareService::class))->newInstanceWithoutConstructor();
        $set = fn(string $p, $v) => (new \ReflectionProperty(PublicShareService::class, $p))->setValue($svc, $v);
        $set('setupService', $setup);
        $set('logger', $logger);
        // Always set explicitly, including the null case: an uninitialised
        // typed property throws on access rather than reading as null.
        $set('pageLocator', $locator);
        // The real resolver, not a mock — it holds the language-agnostic index
        // trick these tests exist to pin.
        $set('indexResolver', new SharePageIndexResolver($logger));

        return $svc;
    }

    /** A language folder that behaves like a real, empty one for the walk. */
    private function languageFolder(string $name): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn($name);
        $folder->method('getPath')->willReturn('/IntraVox/' . $name);
        $folder->method('getType')->willReturn(\OCP\Files\FileInfo::TYPE_FOLDER);
        $folder->method('getDirectoryListing')->willReturn([]);

        return $folder;
    }

    private function resolve(PublicShareService $svc, ?string $language, string $shareTarget = '/'): ?array {
        $m = new \ReflectionMethod(PublicShareService::class, 'resolveSharePage');
        return $m->invoke($svc, self::UID, $language, $shareTarget);
    }

    /**
     * The regression test for the 2.2 seconds.
     *
     * The caller knows no language. One index query finds the Dutch page, and
     * no language tree is walked at all — asserted by failing the test if
     * anything asks the 'en' folder for its contents.
     */
    public function testTheIndexResolvesAPageInALanguageNobodyAskedFor(): void {
        $nl = $this->languageFolder('nl');

        $en = $this->createMock(Folder::class);
        $en->method('getName')->willReturn('en');
        $en->expects($this->never())->method('getDirectoryListing');

        $locator = $this->createMock(PageLocator::class);
        $locator->expects($this->once())->method('locateViaIndex')->willReturn([
            'file' => $this->pageFile(self::UID, '/IntraVox/nl/feeds/mastodon/mastodon.json'),
            'folder' => $nl,
            'isHome' => false,
        ]);
        $locator->method('languageOfFolder')->willReturn('nl');

        $result = $this->resolve($this->service($locator, ['en' => $en, 'nl' => $nl]), null);

        $this->assertNotNull($result);
        $this->assertSame('nl', $result['language']);
        $this->assertSame('Mastodon', $result['title']);
        $this->assertSame('/IntraVox/nl/feeds/mastodon/mastodon.json', $result['path']);
    }

    /**
     * The language that comes back is the one the page LIVES in, not the one
     * the caller guessed. getMediaByShare() still passes 'en' — the breadcrumb
     * must not follow that guess into the wrong navigation.json.
     */
    public function testTheResolvedLanguageWinsOverTheRequestedOne(): void {
        $nl = $this->languageFolder('nl');

        $locator = $this->createMock(PageLocator::class);
        $locator->method('locateViaIndex')->willReturn([
            'file' => $this->pageFile(self::UID, '/IntraVox/nl/mastodon.json'),
            'folder' => $nl,
            'isHome' => false,
        ]);
        $locator->method('languageOfFolder')->willReturn('nl');

        $result = $this->resolve($this->service($locator, ['nl' => $nl]), 'en', '/en');

        $this->assertSame('nl', $result['language']);
    }

    /**
     * A stale index must never serve another page. locateViaIndex() re-reads
     * the file and verifies its uniqueId, so a row pointing at a moved or
     * overwritten page returns null and the walk decides — here it finds
     * nothing, which is the honest answer rather than the wrong one.
     */
    public function testAStaleIndexFallsThroughToTheWalk(): void {
        $locator = $this->createMock(PageLocator::class);
        $locator->method('locateViaIndex')->willReturn(null);

        $result = $this->resolve(
            $this->service($locator, ['nl' => $this->languageFolder('nl')]),
            null
        );

        $this->assertNull($result);
    }

    /**
     * No locator at all — the shape unit tests build, and the shape a failing
     * index degrades to. The sweep still runs; nothing throws.
     */
    public function testWithoutALocatorTheSweepStillDecides(): void {
        $result = $this->resolve(
            $this->service(null, ['en' => $this->languageFolder('en'), 'nl' => $this->languageFolder('nl')]),
            null
        );

        $this->assertNull($result);
    }

    /**
     * An index that throws is not allowed to break a share. The walks below it
     * are the same ones that ran before the index existed.
     */
    public function testAThrowingIndexIsSurvivable(): void {
        $locator = $this->createMock(PageLocator::class);
        $locator->method('locateViaIndex')->willThrowException(new \RuntimeException('db down'));

        $result = $this->resolve(
            $this->service($locator, ['nl' => $this->languageFolder('nl')]),
            null
        );

        $this->assertNull($result);
    }
}
