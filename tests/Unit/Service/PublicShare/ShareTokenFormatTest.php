<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\PublicShare;

use OCA\IntraVox\Service\PublicShareService;
use PHPUnit\Framework\TestCase;

/**
 * Pins PublicShareService::isValidShareTokenFormat after it was consolidated out
 * of PageController + PublicShareController (Phase 6.2). Pure shape check with no
 * collaborators, so the service is built without its (heavy) constructor.
 */
class ShareTokenFormatTest extends TestCase {

    private function validator(): PublicShareService {
        return (new \ReflectionClass(PublicShareService::class))->newInstanceWithoutConstructor();
    }

    public function testNullOrEmptyIsRejected(): void {
        $svc = $this->validator();
        $this->assertFalse($svc->isValidShareTokenFormat(null));
        $this->assertFalse($svc->isValidShareTokenFormat(''));
    }

    public function testTooShortOrTooLongIsRejected(): void {
        $svc = $this->validator();
        $this->assertFalse($svc->isValidShareTokenFormat('abc123'), '6 chars < 10');
        $this->assertFalse($svc->isValidShareTokenFormat(str_repeat('a', 33)), '33 chars > 32');
    }

    public function testNonAlphanumericIsRejected(): void {
        $svc = $this->validator();
        $this->assertFalse($svc->isValidShareTokenFormat('valid-token-1'), 'hyphen not allowed');
        $this->assertFalse($svc->isValidShareTokenFormat('valid token1'), 'space not allowed');
    }

    public function testValidTokenIsAccepted(): void {
        $svc = $this->validator();
        $this->assertTrue($svc->isValidShareTokenFormat('validtoken123'));
        $this->assertTrue($svc->isValidShareTokenFormat(str_repeat('a', 10)), 'exactly 10');
        $this->assertTrue($svc->isValidShareTokenFormat(str_repeat('a', 32)), 'exactly 32');
    }
}
