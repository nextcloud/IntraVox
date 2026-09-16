<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Publication;

use OCA\IntraVox\Service\Publication\MetaVoxGateway;
use OCP\App\IAppManager;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Direct unit tests for the MetaVoxGateway extracted from PageService (Phase 3).
 * Covers the availability check, the guard behaviour of the DB reads without a
 * real database, and the groupfolderIdForFile memo accessor.
 */
class MetaVoxGatewayTest extends TestCase {

    private function gateway(bool $metavox, ?string $userId = 'tester'): MetaVoxGateway {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('isInstalled')->willReturn($metavox);
        $appManager->method('isEnabledForUser')->willReturn($metavox);

        return new MetaVoxGateway(
            $this->createMock(IDBConnection::class),
            $appManager,
            $userId,
            $this->createMock(LoggerInterface::class),
        );
    }

    public function testIsMetaVoxAvailableReflectsInstalledAndEnabled(): void {
        $this->assertTrue($this->gateway(true)->isMetaVoxAvailable());
        $this->assertFalse($this->gateway(false)->isMetaVoxAvailable());
    }

    public function testAvailabilityFailsClosedOnAnException(): void {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('isInstalled')->willThrowException(new \RuntimeException('boom'));
        $g = new MetaVoxGateway(
            $this->createMock(IDBConnection::class),
            $appManager,
            'tester',
            $this->createMock(LoggerInterface::class),
        );
        $this->assertFalse($g->isMetaVoxAvailable());
    }

    public function testGetMetaVoxDataReturnsEmptyWhenUnavailable(): void {
        $this->assertSame([], $this->gateway(false)->getMetaVoxDataForFiles([1, 2, 3]));
    }

    public function testGetMetaVoxDataReturnsEmptyForEmptyFileList(): void {
        $this->assertSame([], $this->gateway(true)->getMetaVoxDataForFiles([]));
    }

    public function testFieldLabelsReturnEmptyWhenUnavailable(): void {
        $this->assertSame([], $this->gateway(false)->getMetaVoxFieldLabels());
    }

    public function testGroupfolderIdForFileReturnsNullWhenNothingFetched(): void {
        // No getMetaVoxDataForFiles has run, so the memo is empty -> null.
        $this->assertNull($this->gateway(true)->groupfolderIdForFile(42));
    }

    public function testSearchMetaVoxValuesGuards(): void {
        $g = $this->gateway(true);
        $this->assertNull($g->searchMetaVoxValues([], 'x', []), 'empty meta -> null');
        $this->assertNull($g->searchMetaVoxValues(['a' => 'b'], '', []), 'empty query -> null');
    }

    public function testCanViewFieldFailsClosedForAnonymous(): void {
        // userId '' means anonymous; canViewMetaVoxField returns false (and caches).
        $g = $this->gateway(true, userId: '');
        $this->assertFalse($g->canViewMetaVoxField('stad', 1));
    }
}
