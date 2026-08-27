<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Sanitize;

use OCA\IntraVox\Service\Sanitize\OutboundUrlValidator;
use PHPUnit\Framework\TestCase;

/**
 * validate() must fail CLOSED.
 *
 * Formerly FeedUrlValidationTest, which tested FeedReaderService::validateUrl.
 * There were three copies of that check; two of them still had the original
 * bug. They are now one class, so the test moves with it — and gains the cases
 * for the copies that were weaker.
 *
 * The old check resolved a host with gethostbynamel() and only inspected the
 * result `if (is_array($ips))`. That function returns A records and nothing
 * else: false when a name does not resolve, and false again when a host
 * publishes AAAA records only. Both landed in the else-that-was-not-there, so
 * the guard skipped precisely the hosts most worth guarding — `http://[::1]/`
 * went straight through to the fetcher.
 *
 * Why this mattered beyond feeds: the ICS copy is reached from
 * PublicShareController::getEventsByShare, which is #[PublicPage] and takes the
 * URL as the `externalIcsUrls` request parameter. No login required.
 */
class OutboundUrlValidatorTest extends TestCase {
    private OutboundUrlValidator $validator;

    protected function setUp(): void {
        parent::setUp();
        $this->validator = new OutboundUrlValidator();
    }

    /** @param string[] $schemes */
    private function reject(string $url, array $schemes = OutboundUrlValidator::SCHEMES_HTTP): ?string {
        try {
            $this->validator->validate($url, $schemes);
            return null;
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }
    }

    public function testIpv6LoopbackLiteralIsRefused(): void {
        $this->assertNotNull(
            $this->reject('http://[::1]/feed.xml'),
            'http://[::1]/ used to pass: gethostbynamel() returned false and the check was skipped'
        );
    }

    public function testIpv4LoopbackLiteralIsRefused(): void {
        $this->assertNotNull($this->reject('http://127.0.0.1/feed.xml'));
    }

    public function testPrivateRangeLiteralIsRefused(): void {
        $this->assertNotNull($this->reject('http://192.168.1.1/feed.xml'));
        $this->assertNotNull($this->reject('http://10.0.0.5/feed.xml'));
    }

    public function testUnresolvableHostIsRefusedRatherThanWavedThrough(): void {
        $message = $this->reject('http://intravox-nonexistent-host-for-tests.invalid/feed.xml');
        $this->assertNotNull(
            $message,
            'A name we cannot resolve is a name we cannot vouch for; it must not be fetched'
        );
    }

    public function testNonHttpSchemesAreRefused(): void {
        $this->assertNotNull($this->reject('file:///etc/passwd'));
        $this->assertNotNull($this->reject('gopher://example.com/'));
    }

    public function testAPublicAddressIsStillAccepted(): void {
        // A literal public address takes the no-DNS path, so this case stays
        // deterministic in CI with no network.
        $this->assertNull(
            $this->reject('https://93.184.216.34/feed.xml'),
            'The guard must not become so strict that ordinary feeds stop working'
        );
    }

    /**
     * The consolidation must not widen the stricter caller. ICS accepts https
     * only; plain http is a downgrade it never allowed.
     */
    public function testHttpsOnlyModeRefusesPlainHttp(): void {
        $this->assertNotNull(
            $this->reject('http://93.184.216.34/cal.ics', OutboundUrlValidator::SCHEMES_HTTPS_ONLY),
            'ICS feeds are https-only and must stay that way after consolidation'
        );
        $this->assertNull(
            $this->reject('https://93.184.216.34/cal.ics', OutboundUrlValidator::SCHEMES_HTTPS_ONLY)
        );
    }

    /**
     * The public-share ICS path is the reason this is a security fix and not a
     * tidy-up: an anonymous request parameter reached the weaker copy.
     */
    public function testHttpsOnlyModeStillRefusesInternalAddresses(): void {
        $this->assertNotNull($this->reject('https://[::1]/cal.ics', OutboundUrlValidator::SCHEMES_HTTPS_ONLY));
        $this->assertNotNull($this->reject('https://127.0.0.1/cal.ics', OutboundUrlValidator::SCHEMES_HTTPS_ONLY));
        $this->assertNotNull($this->reject('https://169.254.169.254/latest/meta-data/', OutboundUrlValidator::SCHEMES_HTTPS_ONLY));
    }

    /**
     * The measured scope of the old bug, so a future "simplification" back to
     * gethostbynamel() alone fails here rather than in production.
     *
     * An IPv4 literal was never the hole: gethostbynamel('127.0.0.1') returns
     * the address itself. What slipped through was anything gethostbynamel()
     * reports as false — IPv6 literals, AAAA-only hosts, and names that do not
     * resolve.
     */
    public function testTheThreeGapsTheOldCheckLeftOpen(): void {
        // 1. IPv6 literal — the one confirmed to reach the fetcher.
        $this->assertNotNull($this->reject('http://[::1]/x'));
        $this->assertNotNull($this->reject('http://[fe80::1]/x'));

        // 2. A name that does not resolve must be refused, not waved through.
        $this->assertNotNull($this->reject('http://intravox-nx-host.invalid/x'));

        // 3. And the case that was never broken must keep working.
        $this->assertNotNull($this->reject('http://127.0.0.1/x'));
    }

    public function testLabelAppearsInTheMessageSoCallersStayDistinguishable(): void {
        $this->assertStringContainsString(
            'ICS URL',
            (string) $this->validator_message('not a url', 'ICS URL')
        );
    }

    private function validator_message(string $url, string $label): ?string {
        try {
            $this->validator->validate($url, OutboundUrlValidator::SCHEMES_HTTPS_ONLY, $label);
            return null;
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }
    }
}
