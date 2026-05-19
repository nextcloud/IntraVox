<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Performance;

use OCA\IntraVox\Performance\RequestTimer;
use PHPUnit\Framework\TestCase;

class RequestTimerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        RequestTimer::reset();
    }

    public function testStopWithoutStartReturnsNull(): void {
        $this->assertNull(RequestTimer::stop('never-started'));
    }

    public function testStartAndStopRecordsPositiveDuration(): void {
        RequestTimer::start('op');
        usleep(1000); // 1 ms
        $duration = RequestTimer::stop('op');

        $this->assertNotNull($duration);
        $this->assertGreaterThan(0.0, $duration);
        $this->assertLessThan(1.0, $duration);
    }

    public function testGetRecordingsReturnsAllDurationsForName(): void {
        RequestTimer::start('repeat');
        usleep(500);
        RequestTimer::stop('repeat');

        RequestTimer::start('repeat');
        usleep(500);
        RequestTimer::stop('repeat');

        $this->assertCount(2, RequestTimer::getRecordings('repeat'));
    }

    public function testGetRecordingsForUnknownNameReturnsEmptyArray(): void {
        $this->assertSame([], RequestTimer::getRecordings('nope'));
    }

    public function testGetAllRecordingsGroupsByName(): void {
        RequestTimer::start('a');
        RequestTimer::stop('a');
        RequestTimer::start('b');
        RequestTimer::stop('b');

        $all = RequestTimer::getAllRecordings();

        $this->assertArrayHasKey('a', $all);
        $this->assertArrayHasKey('b', $all);
    }

    public function testResetClearsState(): void {
        RequestTimer::start('x');
        RequestTimer::stop('x');

        RequestTimer::reset();

        $this->assertSame([], RequestTimer::getAllRecordings());
    }

    public function testStopRemovesMarkSoSecondStopReturnsNull(): void {
        RequestTimer::start('once');
        RequestTimer::stop('once');
        $this->assertNull(RequestTimer::stop('once'));
    }
}
