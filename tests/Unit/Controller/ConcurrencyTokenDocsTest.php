<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * baseVersion and its 409 are documented.
 *
 * They were not, and the omission was the most expensive kind: silent. A read
 * returns `baseVersion` (the file mtime at that moment); send it back on PUT and
 * a write that started from an older copy is refused with 409. Leave it out and
 * you get last-write-wins.
 *
 * Neither the field nor the 409 appeared anywhere in openapi.json. A client
 * generated from that spec had no way to know the mechanism existed, so it
 * silently overwrote concurrent edits — and the only signal was the other
 * person's work quietly disappearing.
 *
 * Found by running the contract test against dev with real credentials. Every
 * static check passed: the field is simply absent, and absence is not a
 * violation of anything. It took looking at an actual response body.
 *
 * Verified live before documenting: a PUT carrying baseVersion=1 against a real
 * page returned 409 with the reload message, and the page was not modified.
 *
 * What this pins is the DOCUMENTATION, not the mechanism — PageService owns the
 * behaviour. The risk here is that the code keeps working while the spec forgets
 * to mention it, which is exactly what happened.
 */
class ConcurrencyTokenDocsTest extends TestCase {
    private array $spec;

    protected function setUp(): void {
        parent::setUp();
        $this->spec = json_decode(
            file_get_contents(__DIR__ . '/../../../openapi.json'),
            true
        );
    }

    public static function pageWriteProvider(): array {
        return [
            'internal' => ['/api/pages/{id}'],
            'external' => ['/api/v1/pages/{id}'],
        ];
    }

    /** @dataProvider pageWriteProvider */
    public function testUpdateDocumentsTheConflictStatus(string $path): void {
        $put = $this->spec['paths'][$path]['put'] ?? null;
        $this->assertNotNull($put, "{$path} PUT must stay documented");

        $this->assertArrayHasKey(
            '409',
            $put['responses'],
            "{$path} answers 409 on a stale write; a client that does not expect it treats a refused overwrite as a crash"
        );
        $this->assertStringContainsString(
            'baseVersion',
            $put['responses']['409']['description'],
            'The 409 must name what caused it, or it reads as a generic conflict'
        );
    }

    /** @dataProvider pageWriteProvider */
    public function testUpdateExplainsTheToken(string $path): void {
        $put = $this->spec['paths'][$path]['put'];

        $this->assertStringContainsString(
            'baseVersion',
            $put['description'] ?? '',
            'The mechanism has to be described, not just its failure mode'
        );

        // Omitting it is allowed, and that is the sharp edge: a client that never
        // sends it gets last-write-wins with no error. Saying so is the point.
        $this->assertMatchesRegularExpression(
            '/last-write-wins/i',
            $put['description'],
            'The consequence of omitting the token is what a client author needs to read'
        );
    }

    /** The field itself is described on the schema it arrives in. */
    public function testPageSchemaDescribesBaseVersion(): void {
        $page = $this->spec['components']['schemas']['Page'];

        $this->assertArrayHasKey('baseVersion', $page['properties']);
        $this->assertStringContainsString(
            'TRANSPORT ONLY',
            $page['properties']['baseVersion']['description'],
            'It is never stored; a client that treats it as page data will send back a stale value forever'
        );
    }

    /**
     * And the behaviour it documents still exists in the service. The updatePage
     * body — and with it the concurrency mechanism — moved to Write/
     * PageWriteService during the god-class dissolution, so the mechanism lives
     * there now.
     */
    public function testTheMechanismIsStillImplemented(): void {
        $source = file_get_contents(__DIR__ . '/../../../lib/Service/Write/PageWriteService.php');

        $this->assertStringContainsString('PageConflictException', $source);
        $this->assertStringContainsString(
            "unset(\$data['baseVersion'])",
            $source,
            'The token must keep being stripped before the page is written'
        );
    }
}
