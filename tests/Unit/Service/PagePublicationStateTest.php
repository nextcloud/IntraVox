<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PublicationSettingsService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes the publication/MetaVox state cluster (effectivePublishState,
 * isHiddenFromReaders, hasPublicationDate, publicationMetaForFiles) as it behaves
 * TODAY, so the Phase 3 extraction into Publication/PublicationStateService can be
 * proven byte-for-byte equivalent. These pin the live "lazy" scheduling model:
 * a page flips published the moment its publish time passes, with no cron.
 *
 * All state is driven through the public methods with a pre-fetched $metaForFile,
 * so no database is touched — the MetaVox DB read (getMetaVoxDataForFiles) is a
 * separate concern pinned by the guard tests below and by the Phase 3 unit tests.
 *
 * Dates are chosen ±10 years from a fixed reference so the assertions never flip
 * with the wall clock (no clock seam exists; "now" is real time in a fixed tz).
 */
class PagePublicationStateTest extends TestCase {

    use BuildsPageService;

    private const PUBLISH_FIELD = 'publish_at';
    private const EXPIRE_FIELD = 'expire_at';

    /** A datetime-local string (naive, no zone) N years from real now. */
    private function yearsFromNow(int $years): string {
        // Real "now" in UTC; the service compares in the fixed instance tz below.
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
    ): PageService {
        $svc = new class extends PageService {
            public function __construct() {
            }
        };

        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('isInstalled')->willReturn($metavox);
        $appManager->method('isEnabledForUser')->willReturn($metavox);

        $settings = $this->createMock(PublicationSettingsService::class);
        $settings->method('getPublishDateField')->willReturn($publishField);
        $settings->method('getExpirationDateField')->willReturn($expireField);

        // Fixed instance timezone so naive publish/expire strings and "now" are
        // compared in a deterministic zone (UTC), matching how yearsFromNow builds.
        $config = $this->createMock(IConfig::class);
        $config->method('getSystemValue')->willReturnCallback(
            fn($key, $default = '') => $key === 'logtimezone' ? 'UTC' : $default
        );

        $this->injectPageServiceDependencies($svc, [
            'appManager' => $appManager,
            'publicationSettings' => $settings,
            'config' => $config,
            // userId is read when the lazy MetaVoxGateway is built (Phase 3); it is
            // nullable so the harness auto-fill skips it — set it explicitly.
            'userId' => 'tester',
            'userSession' => $this->createMock(IUserSession::class),
            'logger' => $this->createMock(LoggerInterface::class),
        ]);

        return $svc;
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
