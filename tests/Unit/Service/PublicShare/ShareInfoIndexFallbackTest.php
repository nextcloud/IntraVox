<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\PublicShare;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PublicShareService;
use OCA\IntraVox\Service\SetupService;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * findPageFileInfo() may take the index, but never at the cost of being wrong.
 *
 * The walk it replaces reads and JSON-decodes every page file in the language
 * tree until one matches — 62.7 ms on a page four levels deep, and it grows
 * with the number of pages rather than the depth of the one being looked up.
 * The index turns that into one query.
 *
 * The risk is not speed but truth: an index can be stale after a move, a
 * delete or an import, and share-info decides whether a page is publicly
 * reachable. So the contract under test is narrow and absolute — the fast path
 * is allowed to be *silent*, never *wrong*:
 *
 *  - when the index answers, the result is shaped exactly like the walk's;
 *  - when the index points at a file carrying a different uniqueId, its answer
 *    is discarded and the walk decides;
 *  - when there is no index at all, the walk decides.
 *
 * The verification itself lives in PageLocator::locateViaIndex(), which this
 * delegates to precisely so there is one implementation of the subtle part
 * (the index stores the page's own folder; the JSON sits beside it, except for
 * home). These tests pin that PublicShareService uses it correctly.
 */
class ShareInfoIndexFallbackTest extends TestCase {
    private const UID = 'abc123';

    /** A page JSON file that reports $uniqueId when read. */
    private function pageFile(string $uniqueId, string $path = '/IntraVox/en/foo.json'): File {
        $file = $this->createMock(File::class);
        $file->method('getContent')->willReturn(json_encode([
            'uniqueId' => $uniqueId,
            'title' => 'Titel van ' . $uniqueId,
        ]));
        $file->method('getPath')->willReturn($path);

        return $file;
    }

    /**
     * A service wired with a language folder and an optional locator. The
     * filesystem walk is not stubbed: the language folder lists nothing, so the
     * walk finds nothing and "the walk decided" is observable as null.
     */
    private function service(?PageLocator $locator, ?Folder $langFolder = null): PublicShareService {
        $langFolder ??= $this->createMock(Folder::class);
        $langFolder->method('getDirectoryListing')->willReturn([]);

        $root = $this->createMock(Folder::class);
        $root->method('nodeExists')->with('en')->willReturn(true);
        $root->method('get')->with('en')->willReturn($langFolder);

        $setup = $this->createMock(SetupService::class);
        $setup->method('getSharedFolder')->willReturn($root);

        $svc = (new \ReflectionClass(PublicShareService::class))->newInstanceWithoutConstructor();
        $set = fn(string $p, $v) => (new \ReflectionProperty(PublicShareService::class, $p))->setValue($svc, $v);
        $set('setupService', $setup);
        $set('logger', $this->createMock(LoggerInterface::class));
        $set('pageLocator', $locator);

        return $svc;
    }

    private function findPageFileInfo(PublicShareService $svc): ?array {
        $m = new \ReflectionMethod(PublicShareService::class, 'findPageFileInfo');
        return $m->invoke($svc, self::UID, 'en');
    }

    /**
     * The happy path, and the shape it must produce: callers read 'path',
     * 'node', 'data' and 'title' off this, so the fast route has to fill all
     * four exactly as the walk does.
     */
    public function testIndexHitReturnsTheWalksShape(): void {
        $file = $this->pageFile(self::UID, '/IntraVox/en/afdelingen/marketing.json');

        $locator = $this->createMock(PageLocator::class);
        $locator->method('locateViaIndex')->willReturn(['file' => $file]);

        $uit = $this->findPageFileInfo($this->service($locator));

        $this->assertNotNull($uit, 'an index hit must answer');
        $this->assertSame('/IntraVox/en/afdelingen/marketing.json', $uit['path']);
        $this->assertSame($file, $uit['node']);
        $this->assertSame(self::UID, $uit['data']['uniqueId']);
        $this->assertSame('Titel van ' . self::UID, $uit['title']);
    }

    /**
     * The one that matters. A stale index pointing at a file that carries a
     * different uniqueId must not be served: share-info would then report
     * another page's sharing state for this one.
     *
     * PageLocator returns null in that case — it verifies before answering —
     * so what this pins is that PublicShareService then really does fall
     * through to the walk instead of treating null as "no page".
     */
    public function testAStaleIndexNeverServesAnotherPage(): void {
        $locator = $this->createMock(PageLocator::class);
        $locator->method('locateViaIndex')->willReturn(null);

        $uit = $this->findPageFileInfo($this->service($locator));

        // The stubbed language folder is empty, so the walk finds nothing.
        // Null here proves the walk ran; a non-null result would mean the
        // index's rejected answer leaked through.
        $this->assertNull($uit);
    }

    /**
     * An index row whose file no longer parses as JSON is not an answer.
     * Treating it as one would hand callers a null 'data' and a null title.
     */
    public function testUnparseableIndexTargetFallsBackToTheWalk(): void {
        $kapot = $this->createMock(File::class);
        $kapot->method('getContent')->willReturn('<!DOCTYPE html><html>404</html>');
        $kapot->method('getPath')->willReturn('/IntraVox/en/foo.json');

        $locator = $this->createMock(PageLocator::class);
        $locator->method('locateViaIndex')->willReturn(['file' => $kapot]);

        $this->assertNull($this->findPageFileInfo($this->service($locator)));
    }

    /**
     * No index wired at all — the shape a unit test or a stripped container
     * produces. The walk must still run rather than the lookup erroring.
     */
    public function testWithoutALocatorTheWalkStillRuns(): void {
        $this->assertNull($this->findPageFileInfo($this->service(null)));
    }

    /**
     * An index that throws must degrade, not propagate: a failing index is a
     * performance problem, and turning it into a broken share button would
     * make it a correctness one.
     */
    public function testAThrowingIndexDegradesToTheWalk(): void {
        $locator = $this->createMock(PageLocator::class);
        $locator->method('locateViaIndex')->willThrowException(new \RuntimeException('index down'));

        $this->assertNull($this->findPageFileInfo($this->service($locator)));
    }
}
