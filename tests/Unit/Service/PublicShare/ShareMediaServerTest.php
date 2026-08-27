<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\PublicShare;

use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\PublicShare\ShareMediaServer;
use PHPUnit\Framework\TestCase;

/**
 * What an anonymous share visitor may be served.
 *
 * The two share endpoints used to carry their own copy of "check the MIME,
 * build a StreamResponse with security headers". This pins the policy that
 * consolidating them produced, including the parts that look like accidents
 * and are not.
 */
class ShareMediaServerTest extends TestCase {
    private ShareMediaServer $server;

    protected function setUp(): void {
        parent::setUp();
        $this->server = new ShareMediaServer();
    }

    /**
     * The regression this class exists to prevent.
     *
     * The obvious "cleanup" is to drop ANONYMOUS_MEDIA_TYPES and reuse
     * PageMediaService::ALLOWED_MEDIA_TYPES. That would silently stop serving
     * .bmp and .mov on every existing public share, because the anonymous list
     * is WIDER. If someone narrows it, this fails and says why.
     */
    public function testTheAnonymousListIsASupersetOfTheAuthenticatedOne(): void {
        $missing = array_diff(
            PageMediaService::ALLOWED_MEDIA_TYPES,
            ShareMediaServer::ANONYMOUS_MEDIA_TYPES
        );

        $this->assertSame(
            [],
            array_values($missing),
            'Anything the authenticated path serves must also be servable on a share'
        );

        foreach (['image/bmp', 'video/quicktime'] as $extra) {
            $this->assertTrue(
                $this->server->isServableMedia($extra),
                "$extra is served on public shares today; dropping it breaks existing links"
            );
        }
    }

    public function testExecutableAndDocumentTypesAreRefused(): void {
        foreach ([
            'text/html',
            'application/javascript',
            'application/x-php',
            'application/pdf',
            'application/octet-stream',
        ] as $mime) {
            $this->assertFalse(
                $this->server->isServableMedia($mime),
                "$mime must not be servable as page media"
            );
        }
    }

    public function testOrdinaryImageAndVideoTypesAreServable(): void {
        foreach (['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                  'video/mp4', 'video/webm', 'video/ogg'] as $mime) {
            $this->assertTrue($this->server->isServableMedia($mime), "$mime should be servable");
        }
    }

    /**
     * GroupFolder's cache reports application/octet-stream for files it has not
     * probed. Without the extension fallback a perfectly good PNG would fail the
     * allowlist and 404.
     */
    public function testTheOctetStreamFallbackRecoversTheRealType(): void {
        foreach ([
            'photo.png' => 'image/png',
            'photo.JPG' => 'image/jpeg',
            'clip.mov' => 'video/quicktime',
            'scan.bmp' => 'image/bmp',
            'logo.svg' => 'image/svg+xml',
        ] as $name => $expected) {
            $this->assertSame($expected, $this->server->mimeTypeOf($this->nodeReporting('application/octet-stream', $name)));
        }
    }

    /** An unknown extension stays octet-stream, and therefore stays unservable. */
    public function testAnUnknownExtensionIsNotGuessedIntoSomethingServable(): void {
        $mime = $this->server->mimeTypeOf($this->nodeReporting('application/octet-stream', 'payload.bin'));

        $this->assertSame('application/octet-stream', $mime);
        $this->assertFalse($this->server->isServableMedia($mime));
    }

    /** A file whose MIME is already known is never second-guessed. */
    public function testAKnownMimeTypeIsUsedAsIs(): void {
        $this->assertSame(
            'image/png',
            $this->server->mimeTypeOf($this->nodeReporting('image/png', 'anything.mov')),
            'the reported type wins; the extension is only a fallback'
        );
    }

    private function nodeReporting(string $mime, string $name): \OCP\Files\Node {
        $node = $this->createMock(\OCP\Files\Node::class);
        $node->method('getMimeType')->willReturn($mime);
        $node->method('getName')->willReturn($name);
        return $node;
    }
}
