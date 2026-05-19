<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Http;

use OCA\IntraVox\Http\EtagBuilder;
use PHPUnit\Framework\TestCase;

class EtagBuilderTest extends TestCase {
    public function testBuildIsDeterministicForSameInputs(): void {
        $a = EtagBuilder::build('page-1', 1700000000);
        $b = EtagBuilder::build('page-1', 1700000000);
        $this->assertSame($a, $b);
    }

    public function testBuildChangesWhenVersionChanges(): void {
        $a = EtagBuilder::build('page-1', 1700000000);
        $b = EtagBuilder::build('page-1', 1700000001);
        $this->assertNotSame($a, $b);
    }

    public function testBuildChangesWhenResourceChanges(): void {
        $a = EtagBuilder::build('page-1', 1700000000);
        $b = EtagBuilder::build('page-2', 1700000000);
        $this->assertNotSame($a, $b);
    }

    public function testBuildIsolatesUserContext(): void {
        $shared = EtagBuilder::build('navigation', 1700000000);
        $alice = EtagBuilder::build('navigation', 1700000000, 'alice');
        $bob = EtagBuilder::build('navigation', 1700000000, 'bob');

        $this->assertNotSame($shared, $alice);
        $this->assertNotSame($alice, $bob);
    }

    public function testEmptyUserContextIsTreatedAsNoContext(): void {
        $withNull = EtagBuilder::build('page-1', 42, null);
        $withEmpty = EtagBuilder::build('page-1', 42, '');
        $this->assertSame($withNull, $withEmpty);
    }

    public function testBuildAcceptsStringVersionToken(): void {
        $etag = EtagBuilder::build('page-1', 'content-hash-abc');
        $this->assertMatchesRegularExpression('/^"[a-f0-9]{32}"$/', $etag);
    }

    public function testBuildIsQuotedStrongEtag(): void {
        $etag = EtagBuilder::build('x', 1);
        $this->assertSame('"', $etag[0]);
        $this->assertSame('"', $etag[strlen($etag) - 1]);
    }

    public function testUserContextFromGroupsIsOrderIndependent(): void {
        $a = EtagBuilder::userContextFromGroups(['admins', 'editors', 'users']);
        $b = EtagBuilder::userContextFromGroups(['users', 'admins', 'editors']);
        $this->assertSame($a, $b);
    }

    public function testUserContextFromGroupsDiffersBetweenDifferentSets(): void {
        $a = EtagBuilder::userContextFromGroups(['admins']);
        $b = EtagBuilder::userContextFromGroups(['users']);
        $this->assertNotSame($a, $b);
    }

    public function testUserContextFromGroupsReturnsShortStableLength(): void {
        $ctx = EtagBuilder::userContextFromGroups(['x']);
        $this->assertSame(12, strlen($ctx));
    }
}
