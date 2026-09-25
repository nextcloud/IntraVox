<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\PublicShare;

use OCA\IntraVox\Service\PublicShare\SharePageReader;
use OCA\IntraVox\Service\PublicShareService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Share\IShare;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * allowedWidgetValues() reads the share's pages once per request, not once per
 * question.
 *
 * The feed guard asks it six times for every feed in a batch — connectionId,
 * feedUrl, and the four secondary selectors (contentType, listId, jiraProject,
 * courseId, moodleForumId). Three feed widgets on one page therefore walked the
 * share tree and JSON-decoded every page inside it eighteen times. Measured on
 * the Mastodon page: 0.72s per feed against a WARM feed cache, scaling linearly
 * to 2.2s for three feeds whose upstream fetches together cost under a second.
 * The time was never the network; it was re-reading the same pages.
 *
 * The contract under test is that memoising changes only the cost:
 *
 *  - the same values still come back, for every key;
 *  - the page files are read once, however many questions are asked;
 *  - two different shares in one request never see each other's pages.
 *
 * The last one is why the memo is keyed on the share node's file id rather than
 * held in a single field: a request may legitimately touch more than one share,
 * and a shared key would let one share's allowlist answer for another — a
 * widening of access, which is exactly what this allowlist exists to prevent.
 */
class ShareWidgetValuesMemoTest extends TestCase {
    /** A page JSON file whose read count is observable. */
    private function pageFile(array $page, int $id, ?int &$reads = null): File {
        $file = $this->createMock(File::class);
        $file->method('getId')->willReturn($id);
        $file->method('getName')->willReturn('page.json');
        $file->method('getContent')->willReturnCallback(function () use ($page, &$reads) {
            $reads++;
            return json_encode($page);
        });

        return $file;
    }

    /** A share whose node is a folder holding $files. */
    private function share(array $files, int $nodeId): IShare {
        $folder = $this->createMock(Folder::class);
        $folder->method('getId')->willReturn($nodeId);
        $folder->method('getDirectoryListing')->willReturn($files);

        $share = $this->createMock(IShare::class);
        $share->method('getNode')->willReturn($folder);

        return $share;
    }

    private function service(): PublicShareService {
        $svc = (new \ReflectionClass(PublicShareService::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(PublicShareService::class, 'logger'))
            ->setValue($svc, $this->createMock(LoggerInterface::class));
        // The real reader, not a mock — it holds the memo these tests pin.
        (new \ReflectionProperty(PublicShareService::class, 'pageReader'))
            ->setValue($svc, new SharePageReader());

        return $svc;
    }

    /** A page carrying one feed widget with the given config. */
    private function feedPage(array $widget): array {
        return ['layout' => ['rows' => [['widgets' => [['type' => 'feed'] + $widget]]]]];
    }

    /**
     * The regression test: six questions, one read.
     *
     * This is what the feed guard does for a single feed. Before the memo it
     * cost six full walks of the share tree.
     */
    public function testThePagesAreReadOnceHoweverManyKeysAreAsked(): void {
        $reads = 0;
        $share = $this->share([
            $this->pageFile($this->feedPage(['feedUrl' => 'https://example.org/a.rss']), 11, $reads),
        ], 100);

        $svc = $this->service();
        foreach (['connectionId', 'feedUrl', 'contentType', 'listId', 'jiraProject', 'courseId'] as $key) {
            $svc->allowedWidgetValues($share, 'feed', $key);
        }

        $this->assertSame(1, $reads, 'the share pages should be read once per request');
    }

    /** Memoising must not change which values come back. */
    public function testTheValuesAreUnchangedByTheMemo(): void {
        $reads = 0;
        $share = $this->share([
            $this->pageFile($this->feedPage([
                'feedUrl' => 'https://example.org/a.rss',
                'listId' => 'lijst-7',
            ]), 11, $reads),
        ], 100);

        $svc = $this->service();

        $this->assertSame(['https://example.org/a.rss'], $svc->allowedWidgetValues($share, 'feed', 'feedUrl'));
        $this->assertSame(['lijst-7'], $svc->allowedWidgetValues($share, 'feed', 'listId'));
        // A key nothing configures stays unconstrained — an empty allowlist.
        $this->assertSame([], $svc->allowedWidgetValues($share, 'feed', 'jiraProject'));
        // Repeating a question gives the same answer from the memo.
        $this->assertSame(['https://example.org/a.rss'], $svc->allowedWidgetValues($share, 'feed', 'feedUrl'));
    }

    /**
     * Two shares in one request keep their own pages. A memo that ignored the
     * share identity would let the first share's feeds answer for the second —
     * a widening of access, not a speed-up.
     */
    public function testTwoSharesInOneRequestDoNotShareAnAllowlist(): void {
        $readsA = 0;
        $readsB = 0;
        $shareA = $this->share([
            $this->pageFile($this->feedPage(['feedUrl' => 'https://a.example/a.rss']), 11, $readsA),
        ], 100);
        $shareB = $this->share([
            $this->pageFile($this->feedPage(['feedUrl' => 'https://b.example/b.rss']), 22, $readsB),
        ], 200);

        $svc = $this->service();

        $this->assertSame(['https://a.example/a.rss'], $svc->allowedWidgetValues($shareA, 'feed', 'feedUrl'));
        $this->assertSame(['https://b.example/b.rss'], $svc->allowedWidgetValues($shareB, 'feed', 'feedUrl'));
        $this->assertSame(1, $readsA);
        $this->assertSame(1, $readsB);
    }

    /**
     * Fail-closed is unchanged: a page that cannot be read publishes nothing,
     * and an unreadable scope yields an empty allowlist rather than "allow".
     */
    public function testUnreadablePagesStillPublishNothing(): void {
        $broken = $this->createMock(File::class);
        $broken->method('getId')->willReturn(11);
        $broken->method('getName')->willReturn('page.json');
        $broken->method('getContent')->willReturn('{ this is not json');

        $svc = $this->service();

        $this->assertSame([], $svc->allowedWidgetValues($this->share([$broken], 100), 'feed', 'feedUrl'));
    }
}
