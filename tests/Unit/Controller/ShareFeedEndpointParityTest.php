<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * Every share feed endpoint must do what the others do.
 *
 * This exists because the same bug shipped twice. The image proxy signs URLs
 * for `/apps/intravox/api/feed/image`, which is #[NoAdminRequired]: an
 * anonymous visitor gets a 401 for every picture and sees alt text where the
 * photos should be. The fix is one line — `setShareToken($token)` before the
 * fetch — and it was added to `getFeedByShare` only. When the batch endpoint
 * arrived it lacked that line, and the images broke again in exactly the same
 * way, on exactly the same page.
 *
 * Reviewing a diff does not catch that: the new endpoint looks complete on its
 * own. What catches it is asking whether every endpoint in this family carries
 * the same obligations, which is what these tests do by reading the source.
 *
 * Source inspection rather than behaviour, deliberately. PublicShareController
 * takes eighteen collaborators and each endpoint wants a live share; the
 * property under test is "nobody forgot a line", and a missing line is visible
 * in the file. A behavioural test would be better at proving the line works —
 * FeedImageProxyTest already does that — and worse at proving it is everywhere.
 */
class ShareFeedEndpointParityTest extends TestCase {
    private string $bron;

    protected function setUp(): void {
        parent::setUp();
        $pad = __DIR__ . '/../../../lib/Controller/PublicShareController.php';
        $this->assertFileExists($pad);
        $this->bron = (string)file_get_contents($pad);
    }

    /**
     * The body of one method, from its signature to the closing brace at the
     * same indentation.
     */
    private function methode(string $naam): string {
        $start = strpos($this->bron, "public function {$naam}(");
        $this->assertNotFalse($start, "PublicShareController::{$naam}() should exist");

        $eind = strpos($this->bron, "\n    }", $start);
        $this->assertNotFalse($eind, "could not find the end of {$naam}()");

        return substr($this->bron, $start, $eind - $start);
    }

    /** Every endpoint that serves feed content through a share. */
    public static function feedEndpoints(): array {
        return [
            'single feed' => ['getFeedByShare'],
            'batch' => ['getFeedBatchByShare'],
            'article body' => ['getArticleByShare'],
        ];
    }

    /**
     * The regression itself: without this the images 401 for every anonymous
     * visitor, and the page looks broken rather than unauthorised.
     *
     * @dataProvider feedEndpoints
     */
    public function testEveryFeedEndpointSignsImagesForTheShareRoute(string $naam): void {
        $this->assertStringContainsString(
            'setShareToken($token)',
            $this->methode($naam),
            "{$naam}() must set the share token on the image proxy, or anonymous "
            . 'visitors get a 401 for every image the response points at'
        );
    }

    /**
     * Order matters and is invisible in review: the URLs are signed while the
     * feed is parsed, so setting the token afterwards signs nothing.
     *
     * @dataProvider feedEndpoints
     */
    public function testTheTokenIsSetBeforeTheFetch(string $naam): void {
        $body = $this->methode($naam);
        $token = strpos($body, 'setShareToken($token)');

        foreach (['fetchFeed(', 'handleFetchFeedBatch(', 'fetchArticle(', 'handleFetchArticle('] as $aanroep) {
            $fetch = strpos($body, $aanroep);
            if ($fetch === false) {
                continue;
            }
            $this->assertLessThan(
                $fetch,
                $token,
                "{$naam}(): setShareToken() must come before {$aanroep} — the URLs "
                . 'are generated during the fetch, not after it'
            );
        }
    }

    /**
     * A share may only read what it publishes. The single-feed endpoint learned
     * this the hard way (SHARE-CFG); an endpoint added later without the check
     * would be a way around it.
     *
     * @dataProvider feedEndpoints
     */
    public function testEveryFeedEndpointChecksWhatTheSharePublishes(string $naam): void {
        $this->assertStringContainsString(
            'refuseUnpublishedFeedSelectors',
            $this->methode($naam),
            "{$naam}() must run the selector allowlist, or a share token becomes a "
            . 'way to read feeds and connections the share never published'
        );
    }

    /**
     * Every one of them opens the share first. openShare() is what checks the
     * token shape, link sharing, the password and the groupfolder; reaching the
     * feed layer without it would skip all four.
     *
     * @dataProvider feedEndpoints
     */
    public function testEveryFeedEndpointOpensTheShareFirst(string $naam): void {
        $body = $this->methode($naam);

        $this->assertStringContainsString('openShare($token', $body, "{$naam}() must open the share");
        $this->assertStringContainsString(
            'instanceof Response',
            $body,
            "{$naam}() must return openShare()'s refusal rather than continue"
        );
    }

    /**
     * Anonymous endpoints are rate limited per IP. One without a limit is a
     * free amplifier: every call makes the server fetch from a third party.
     *
     * @dataProvider feedEndpoints
     */
    public function testEveryFeedEndpointIsRateLimited(string $naam): void {
        $voor = substr($this->bron, 0, strpos($this->bron, "public function {$naam}("));
        $laatsteBlok = substr($voor, strrpos($voor, '#[PublicPage]'));

        $this->assertStringContainsString(
            'AnonRateLimit',
            $laatsteBlok,
            "{$naam}() must carry an AnonRateLimit attribute"
        );
    }
}
