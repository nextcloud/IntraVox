<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\RequiresPagePermission;
use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the denyUnlessReadable() read-permission helper added to
 * RequiresPagePermission (Phase 2). It replaces the inline canRead gate copied
 * across the read endpoints; these pin that the consolidated helper reproduces
 * that gate exactly — including the fail-closed default and the byte-exact body.
 */
class RequiresPagePermissionTest extends TestCase {

    /** A throwaway host that mixes in the trait so the protected method is reachable. */
    private function host(): object {
        return new class {
            use RequiresPagePermission;
            protected function getPageService(): PageService {
                throw new \LogicException('denyUnlessReadable must not fetch the page');
            }
            protected function getPageReadService(): \OCA\IntraVox\Service\Read\PageReadService {
                throw new \LogicException('denyUnlessReadable must not fetch the page');
            }
            public function check(array $page, string $body = 'Access denied'): ?DataResponse {
                return $this->denyUnlessReadable($page, $body);
            }
        };
    }

    public function testReadablePagePassesWithNull(): void {
        $result = $this->host()->check(['permissions' => ['canRead' => true]]);
        $this->assertNull($result, 'a readable page returns null so the caller continues');
    }

    public function testUnreadablePageReturns403AccessDenied(): void {
        $result = $this->host()->check(['permissions' => ['canRead' => false]]);

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
        $this->assertSame(['error' => 'Access denied'], $result->getData());
    }

    public function testMissingPermissionsFailClosed(): void {
        // The whole point of `?? false`: an undetermined permission reads as "no".
        $this->assertInstanceOf(DataResponse::class, $this->host()->check([]));
        $this->assertInstanceOf(DataResponse::class, $this->host()->check(['permissions' => []]));
    }

    public function testCustomDenialBodyIsUsed(): void {
        $result = $this->host()->check(
            ['permissions' => ['canRead' => false]],
            'Permission denied: cannot read the source page'
        );

        $this->assertSame(
            ['error' => 'Permission denied: cannot read the source page'],
            $result->getData()
        );
    }

    public function testDoesNotFetchThePage(): void {
        // getPageService() throws; reaching it would fail this test. A readable
        // page must be evaluated purely from the passed array.
        $result = $this->host()->check(['permissions' => ['canRead' => true]]);
        $this->assertNull($result);
    }
}
