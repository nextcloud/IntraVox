<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use OCA\IntraVox\Service\FeedReaderService;
use PHPUnit\Framework\TestCase;

/**
 * The Moodle webservice token must not travel in a browser-facing URL.
 *
 * Regression test for IV-04. moodleFileUrl() used to append `token=<token>` to
 * the file URL, and the result was handed to the image proxy, which signs the
 * URL and puts it verbatim in the `url=` query parameter the browser receives.
 * For a connection configured with an admin webservice token, that published
 * administrator credentials for the LMS to every visitor of the page — in the
 * page source, in browser history, and in any access log on the way.
 *
 * The rewrite of /pluginfile.php/ to /webservice/pluginfile.php/ stays here,
 * because that part is public information. The token is attached server-side
 * in attachMoodleToken(), at fetch time, where it never leaves the server.
 */
class MoodleFileUrlTokenTest extends TestCase {
    private function moodleFileUrl(?string $url, string $baseUrl, string $token): ?string {
        // The method is private and the service has a wide constructor; the
        // behaviour under test is pure string handling, so reflection keeps the
        // test to the thing that actually regressed.
        $method = new \ReflectionMethod(FeedReaderService::class, 'moodleFileUrl');

        $service = (new \ReflectionClass(FeedReaderService::class))->newInstanceWithoutConstructor();

        return $method->invoke($service, $url, $baseUrl, $token);
    }

    public function testThePluginfilePathIsRewrittenToTheWebserviceEndpoint(): void {
        $result = $this->moodleFileUrl(
            'https://moodle.example.org/pluginfile.php/123/course/overviewfiles/cover.jpg',
            'https://moodle.example.org',
            'super-secret-admin-token',
        );

        $this->assertSame(
            'https://moodle.example.org/webservice/pluginfile.php/123/course/overviewfiles/cover.jpg',
            $result,
        );
    }

    /** The one that matters: IV-04. */
    public function testTheTokenIsNeverPlacedInTheUrl(): void {
        $result = $this->moodleFileUrl(
            'https://moodle.example.org/pluginfile.php/123/course/overviewfiles/cover.jpg',
            'https://moodle.example.org',
            'super-secret-admin-token',
        );

        $this->assertStringNotContainsString('super-secret-admin-token', (string)$result);
        $this->assertStringNotContainsString('token=', (string)$result);
    }

    /** A URL that is already a webservice URL keeps its shape, still without a token. */
    public function testAnUnrelatedUrlIsLeftAlone(): void {
        $url = 'https://cdn.example.org/image.png';

        $result = $this->moodleFileUrl($url, 'https://moodle.example.org', 'super-secret-admin-token');

        $this->assertSame($url, $result);
        $this->assertStringNotContainsString('token=', (string)$result);
    }

    public function testNullStaysNull(): void {
        $this->assertNull(
            $this->moodleFileUrl(null, 'https://moodle.example.org', 'super-secret-admin-token'),
        );
    }
}
