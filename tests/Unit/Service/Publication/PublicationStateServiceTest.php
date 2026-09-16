<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Publication;

use OCA\IntraVox\Service\Publication\MetaVoxGateway;
use OCA\IntraVox\Service\Publication\PublicationStateService;
use OCA\IntraVox\Service\PublicationSettingsService;
use OCP\IConfig;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * Direct unit tests for PublicationStateService (Phase 3, commit 2). PageService
 * still delegates here and PagePublicationStateTest exercises that path; this
 * tests the class directly so the coverage survives once the delegators are
 * removed (commit 4). Same live scheduling model: a future publish date -> scheduled,
 * a passed one -> published, expiration -> expired.
 */
class PublicationStateServiceTest extends TestCase {

    private const PUBLISH = 'publish_at';
    private const EXPIRE = 'expire_at';

    private function yearsFromNow(int $years): string {
        $dt = new \DateTime('now', new \DateTimeZone('UTC'));
        $dt->modify(($years >= 0 ? '+' : '') . $years . ' years');
        return $dt->format('Y-m-d\TH:i:s');
    }

    private function service(bool $metavox = true, string $publish = self::PUBLISH, string $expire = self::EXPIRE): PublicationStateService {
        $settings = $this->createMock(PublicationSettingsService::class);
        $settings->method('getPublishDateField')->willReturn($publish);
        $settings->method('getExpirationDateField')->willReturn($expire);

        $config = $this->createMock(IConfig::class);
        $config->method('getSystemValue')->willReturnCallback(
            fn($key, $default = '') => $key === 'logtimezone' ? 'UTC' : $default
        );

        // The gateway is only consulted for isMetaVoxAvailable() here (meta is
        // passed in), so a partial mock over the real class is enough.
        $gateway = $this->getMockBuilder(MetaVoxGateway::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isMetaVoxAvailable', 'getMetaVoxDataForFiles'])
            ->getMock();
        $gateway->method('isMetaVoxAvailable')->willReturn($metavox);
        $gateway->method('getMetaVoxDataForFiles')->willReturn([]);

        return new PublicationStateService(
            $settings,
            $config,
            $this->createMock(IUserSession::class),
            $gateway,
        );
    }

    public function testManualStatusGovernsWithoutFieldsOrMetaVox(): void {
        $this->assertSame('draft', $this->service(publish: '', expire: '')->effectivePublishState(['status' => 'draft']));
        $this->assertSame('published', $this->service(metavox: false)->effectivePublishState(['status' => 'published']));
    }

    public function testFuturePublishDateIsScheduled(): void {
        $meta = [self::PUBLISH => $this->yearsFromNow(10)];
        $this->assertSame('scheduled', $this->service()->effectivePublishState(['status' => 'published'], $meta));
        $this->assertSame('scheduled', $this->service()->effectivePublishState(['status' => 'draft'], $meta));
    }

    public function testPastPublishDatePublishesOverManualDraft(): void {
        $meta = [self::PUBLISH => $this->yearsFromNow(-10)];
        $this->assertSame('published', $this->service()->effectivePublishState(['status' => 'draft'], $meta));
    }

    public function testExpiredWhenExpirationPassed(): void {
        $meta = [self::PUBLISH => $this->yearsFromNow(-10), self::EXPIRE => $this->yearsFromNow(-1)];
        $this->assertSame('expired', $this->service()->effectivePublishState(['status' => 'published'], $meta));
    }

    public function testIsHiddenMirrorsNonPublished(): void {
        $svc = $this->service();
        $this->assertFalse($svc->isHiddenFromReaders(['status' => 'published'], []));
        $this->assertTrue($svc->isHiddenFromReaders(['status' => 'draft'], []));
    }

    public function testHasPublicationDate(): void {
        $svc = $this->service();
        $this->assertTrue($svc->hasPublicationDate([], [self::PUBLISH => $this->yearsFromNow(1)]));
        $this->assertFalse($svc->hasPublicationDate([], []));
        $this->assertFalse($this->service(metavox: false)->hasPublicationDate([], [self::PUBLISH => $this->yearsFromNow(1)]));
    }

    public function testPublicationMetaForFilesGuards(): void {
        $this->assertSame([], $this->service(publish: '', expire: '')->publicationMetaForFiles([1]));
        $this->assertSame([], $this->service()->publicationMetaForFiles([]));
    }
}
