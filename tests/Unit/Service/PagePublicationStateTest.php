<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Publication\MetaVoxGateway;
use OCA\IntraVox\Service\Publication\PublicationStateService;
use OCA\IntraVox\Service\PublicationSettingsService;
use OCP\IConfig;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * Characterizes the publication scheduling cluster (effectivePublishState,
 * isHiddenFromReaders, hasPublicationDate, publicationMetaForFiles). Written in
 * Phase 0 against PageService's public API; in Phase 3 that API moved to
 * Publication/PublicationStateService behind delegators, and once the delegators
 * were removed this test drives the extracted service directly. The rich edge-case
 * matrix (unparseable dates, empty meta, expiration-on-draft, not-yet-expired) is
 * kept here — it is deeper than the class's own PublicationStateServiceTest.
 *
 * All state is driven with a pre-fetched $metaForFile, so no database is touched.
 * Dates are ±10 years from real now in a fixed UTC instance timezone, so the
 * assertions never flip with the wall clock.
 */
class PagePublicationStateTest extends TestCase {

    private const PUBLISH_FIELD = 'publish_at';
    private const EXPIRE_FIELD = 'expire_at';

    /** A datetime-local string (naive, no zone) N years from real now. */
    private function yearsFromNow(int $years): string {
        $dt = new \DateTime('now', new \DateTimeZone('UTC'));
        $dt->modify(($years >= 0 ? '+' : '') . $years . ' years');
        return $dt->format('Y-m-d\TH:i:s');
    }

    /**
     * @param bool $metavox whether MetaVox reports installed+enabled
     * @param string $publishField configured publish-date field ('' = none)
     * @param string $expireField configured expiration-date field ('' = none)
     */
    private function makeService(
        bool $metavox = true,
        string $publishField = self::PUBLISH_FIELD,
        string $expireField = self::EXPIRE_FIELD
    ): PublicationStateService {
        $settings = $this->createMock(PublicationSettingsService::class);
        $settings->method('getPublishDateField')->willReturn($publishField);
        $settings->method('getExpirationDateField')->willReturn($expireField);

        // Fixed instance timezone so naive publish/expire strings and "now" are
        // compared in a deterministic zone (UTC), matching how yearsFromNow builds.
        $config = $this->createMock(IConfig::class);
        $config->method('getSystemValue')->willReturnCallback(
            fn($key, $default = '') => $key === 'logtimezone' ? 'UTC' : $default
        );

        // Only isMetaVoxAvailable/getMetaVoxDataForFiles are consulted (meta is
        // passed in), so a partial mock over the real gateway is enough.
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

    // --- effectivePublishState: the no-scheduling / no-MetaVox fallback ---

    public function testNoFieldsConfiguredFallsBackToManualStatus(): void {
        $svc = $this->makeService(publishField: '', expireField: '');

        $this->assertSame('published', $svc->effectivePublishState(['status' => 'published']));
        $this->assertSame('draft', $svc->effectivePublishState(['status' => 'draft']));
        // Missing status defaults to published.
        $this->assertSame('published', $svc->effectivePublishState([]));
    }

    public function testMetaVoxUnavailableFallsBackToManualStatusEvenWithFields(): void {
        $svc = $this->makeService(metavox: false);

        $this->assertSame('draft', $svc->effectivePublishState(['status' => 'draft']));
        $this->assertSame('published', $svc->effectivePublishState(['status' => 'published']));
    }

    // --- effectivePublishState: the live scheduling model ---

    public function testFuturePublishDateIsScheduledEvenWhenStatusPublished(): void {
        $svc = $this->makeService();
        $meta = [self::PUBLISH_FIELD => $this->yearsFromNow(10)];

        $this->assertSame(
            'scheduled',
            $svc->effectivePublishState(['status' => 'published'], $meta),
            'a future publish date wins over the manual published flag'
        );
    }

    public function testFuturePublishDateIgnoresManualDraftAndStaysScheduled(): void {
        $svc = $this->makeService();
        $meta = [self::PUBLISH_FIELD => $this->yearsFromNow(10)];

        $this->assertSame('scheduled', $svc->effectivePublishState(['status' => 'draft'], $meta));
    }

    public function testPastPublishDateOverridesManualDraftToPublished(): void {
        $svc = $this->makeService();
        $meta = [self::PUBLISH_FIELD => $this->yearsFromNow(-10)];

        $this->assertSame(
            'published',
            $svc->effectivePublishState(['status' => 'draft'], $meta),
            'a passed publish date publishes the page despite the manual draft flag'
        );
    }

    public function testManualDraftHoldsWhenNoPublishDateSet(): void {
        $svc = $this->makeService();
        // Only an expiration field value; no publish date.
        $meta = [self::EXPIRE_FIELD => $this->yearsFromNow(10)];

        $this->assertSame('draft', $svc->effectivePublishState(['status' => 'draft'], $meta));
    }

    public function testExpiredWhenExpirationHasPassed(): void {
        $svc = $this->makeService();
        $meta = [
            self::PUBLISH_FIELD => $this->yearsFromNow(-10),
            self::EXPIRE_FIELD => $this->yearsFromNow(-5),
        ];

        $this->assertSame('expired', $svc->effectivePublishState(['status' => 'published'], $meta));
    }

    public function testNotExpiredWhenExpirationIsInFuture(): void {
        $svc = $this->makeService();
        $meta = [
            self::PUBLISH_FIELD => $this->yearsFromNow(-10),
            self::EXPIRE_FIELD => $this->yearsFromNow(10),
        ];

        $this->assertSame('published', $svc->effectivePublishState(['status' => 'published'], $meta));
    }

    public function testExpirationAppliesEvenToAManuallyDraftPageThatPublishDatePublished(): void {
        $svc = $this->makeService();
        $meta = [
            self::PUBLISH_FIELD => $this->yearsFromNow(-10),
            self::EXPIRE_FIELD => $this->yearsFromNow(-1),
        ];
        // Publish date passed -> published, then expiration wins -> expired.
        $this->assertSame('expired', $svc->effectivePublishState(['status' => 'draft'], $meta));
    }

    public function testUnparseablePublishDateIsTreatedAsNoDateSoManualStatusGoverns(): void {
        $svc = $this->makeService();
        $meta = [self::PUBLISH_FIELD => 'not-a-date'];

        // parseDateTime returns null -> publishAt null -> manual draft holds.
        $this->assertSame('draft', $svc->effectivePublishState(['status' => 'draft'], $meta));
        $this->assertSame('published', $svc->effectivePublishState(['status' => 'published'], $meta));
    }

    public function testEmptyMetaArrayFallsThroughToManualStatus(): void {
        $svc = $this->makeService();

        $this->assertSame('draft', $svc->effectivePublishState(['status' => 'draft'], []));
        $this->assertSame('published', $svc->effectivePublishState(['status' => 'published'], []));
    }

    // --- isHiddenFromReaders: exactly "state !== published" ---

    public function testIsHiddenFromReadersMirrorsNonPublishedState(): void {
        $svc = $this->makeService();

        $published = ['status' => 'published'];
        $this->assertFalse($svc->isHiddenFromReaders($published, []));

        $draft = ['status' => 'draft'];
        $this->assertTrue($svc->isHiddenFromReaders($draft, []));

        $scheduled = ['status' => 'published'];
        $scheduledMeta = [self::PUBLISH_FIELD => $this->yearsFromNow(10)];
        $this->assertTrue($svc->isHiddenFromReaders($scheduled, $scheduledMeta));

        $expiredMeta = [
            self::PUBLISH_FIELD => $this->yearsFromNow(-10),
            self::EXPIRE_FIELD => $this->yearsFromNow(-1),
        ];
        $this->assertTrue($svc->isHiddenFromReaders(['status' => 'published'], $expiredMeta));
    }

    // --- hasPublicationDate ---

    public function testHasPublicationDateFalseWithoutFieldsOrMetaVox(): void {
        $this->assertFalse(
            $this->makeService(publishField: '', expireField: '')
                ->hasPublicationDate(['status' => 'published'], [self::PUBLISH_FIELD => $this->yearsFromNow(1)])
        );
        $this->assertFalse(
            $this->makeService(metavox: false)
                ->hasPublicationDate(['status' => 'published'], [self::PUBLISH_FIELD => $this->yearsFromNow(1)])
        );
    }

    public function testHasPublicationDateTrueWhenAConfiguredFieldHasAValue(): void {
        $svc = $this->makeService();

        $this->assertTrue($svc->hasPublicationDate([], [self::PUBLISH_FIELD => $this->yearsFromNow(1)]));
        $this->assertTrue($svc->hasPublicationDate([], [self::EXPIRE_FIELD => $this->yearsFromNow(1)]));
        $this->assertFalse($svc->hasPublicationDate([], []));
        // A value under a non-configured field name does not count.
        $this->assertFalse($svc->hasPublicationDate([], ['some_other_field' => $this->yearsFromNow(1)]));
    }

    // --- publicationMetaForFiles guards (no DB) ---

    public function testPublicationMetaForFilesReturnsEmptyWithoutConfiguredFields(): void {
        $svc = $this->makeService(publishField: '', expireField: '');
        $this->assertSame([], $svc->publicationMetaForFiles([1, 2, 3]));
    }

    public function testPublicationMetaForFilesReturnsEmptyForEmptyFileList(): void {
        $svc = $this->makeService();
        $this->assertSame([], $svc->publicationMetaForFiles([]));
    }
}
