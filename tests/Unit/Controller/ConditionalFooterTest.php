<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\FooterController;
use OCA\IntraVox\Service\FooterService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

/**
 * The footer answers 304 when the caller already holds it.
 *
 * Both this controller and NavigationController computed an ETag and sent it,
 * and neither ever read If-None-Match back. So the header was pure decoration:
 * a client that had the current footer asked for it again and got the whole
 * thing, every time, while the CHANGELOG described the endpoint as
 * conditionally cached.
 *
 * Worth being precise about what this buys: the ETag is an md5 of the finished
 * payload, so the footer is built and permission-checked before the hash exists.
 * A 304 saves bandwidth, never server work. That is still the right trade for a
 * response every page load asks for, but it is not a performance fix and should
 * not be sold as one.
 */
class ConditionalFooterTest extends TestCase {
    private const FOOTER = ['columns' => [], 'copyright' => '© VoxCloud'];

    private function controller(string $ifNoneMatch = ''): FooterController {
        $request = $this->createMock(IRequest::class);
        $request->method('getHeader')->willReturnCallback(
            static fn(string $name) => $name === 'If-None-Match' ? $ifNoneMatch : ''
        );

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('getFolderPermissions')->willReturn(['canRead' => true, 'canWrite' => false]);

        $footerService = $this->createMock(FooterService::class);
        $footerService->method('getFooter')->willReturn(self::FOOTER);

        return new FooterController('intravox', $request, $footerService, $permissionService);
    }

    /** The ETag the controller will produce for the fixture above. */
    private function currentEtag(): string {
        $body = self::FOOTER;
        $body['permissions'] = ['canRead' => true, 'canWrite' => false];
        $body['canEdit'] = false;

        return '"' . md5(json_encode($body)) . '"';
    }

    public function testAFreshRequestGetsTheFooterAndAnEtag(): void {
        $response = $this->controller()->get();

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame($this->currentEtag(), $response->getHeaders()['ETag']);
        $this->assertSame('© VoxCloud', $response->getData()['copyright']);
    }

    public function testAMatchingIfNoneMatchGets304WithNoBody(): void {
        $response = $this->controller($this->currentEtag())->get();

        $this->assertSame(Http::STATUS_NOT_MODIFIED, $response->getStatus());
        $this->assertSame([], $response->getData());
        $this->assertSame($this->currentEtag(), $response->getHeaders()['ETag']);
    }

    public function testAStaleIfNoneMatchStillGetsTheFooter(): void {
        $response = $this->controller('"an-older-hash"')->get();

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame('© VoxCloud', $response->getData()['copyright']);
    }

    /**
     * An absent header must not match an absent ETag.
     *
     * The guard is `$ifNoneMatch !== '' && $etag !== ''`; drop either half and a
     * request with no If-None-Match at all starts collecting 304s for a body it
     * has never seen.
     */
    public function testAnEmptyHeaderNeverMatches(): void {
        $response = $this->controller('')->get();

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
    }
}
