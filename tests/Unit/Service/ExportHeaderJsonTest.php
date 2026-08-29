<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * export.json must be valid JSON, also when MetaVox is installed. (EXP-JSON)
 *
 * ExportService streams export.json by hand: it encodes the header object,
 * removes the closing brace, appends `"pages": [` and writes the pages one at a
 * time. That keeps peak memory flat on a 200 MB export, and it is fine — as long
 * as exactly ONE brace comes off.
 *
 * It did not. The code was:
 *
 *     $headerJson = rtrim($headerJson, "\n}");
 *
 * rtrim() strips a CHARACTER SET, not a suffix. Without MetaVox the header ends
 * in a single "}" and the bug is invisible. With MetaVox installed the header
 * gains a nested metavox.fieldDefinitions object, ends in "}}}", and all three
 * braces were removed. The result was an export.json that no parser accepts, so
 * every export from a MetaVox instance produced a ZIP that importFromZip()
 * rejects as invalid JSON.
 *
 * Found by round-tripping a real 197 MB export off dev, which is the only way it
 * WOULD be found: the code path is fine in isolation and the failure only shows
 * up once you try to read the result back.
 *
 * This test reproduces the assembly on both header shapes rather than standing
 * up ExportService with its collaborators — the bug was in the string handling,
 * so that is what is pinned.
 */
class ExportHeaderJsonTest extends TestCase {
    private const FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /** The assembly as ExportService does it, with the fix in place. */
    private function assemble(array $header, array $pages): string {
        $headerJson = json_encode($header, self::FLAGS);
        $headerJson = substr($headerJson, 0, strrpos($headerJson, '}'));

        $doc = rtrim($headerJson) . ",\n  \"pages\": [\n";
        $doc .= implode(",\n", array_map(
            static fn (array $p): string => '    ' . json_encode($p, self::FLAGS),
            $pages
        ));

        return $doc . "\n  ]\n}";
    }

    /** The shape that always worked: no nested object at the end. */
    public function testAHeaderWithoutMetaVoxProducesValidJson(): void {
        $doc = $this->assemble(
            ['exportVersion' => '1.2', 'language' => 'en'],
            [['uniqueId' => 'page-a'], ['uniqueId' => 'page-b']]
        );

        $decoded = json_decode($doc, true);
        $this->assertNotNull($decoded, 'export.json must parse: ' . json_last_error_msg());
        $this->assertSame('en', $decoded['language']);
        $this->assertCount(2, $decoded['pages']);
    }

    /**
     * THE regression. This header ends in "}}}" and is what an instance with
     * MetaVox actually writes.
     */
    public function testAHeaderWithMetaVoxAlsoProducesValidJson(): void {
        $doc = $this->assemble(
            [
                'exportVersion' => '1.2',
                'language' => 'en',
                'metavox' => [
                    'version' => '2.2.0',
                    'fieldDefinitions' => ['file_gf_thema' => ['type' => 'choice']],
                ],
            ],
            [['uniqueId' => 'page-a']]
        );

        $decoded = json_decode($doc, true);
        $this->assertNotNull(
            $decoded,
            'a MetaVox export must still parse: ' . json_last_error_msg()
        );
        $this->assertSame('2.2.0', $decoded['metavox']['version']);
        $this->assertArrayHasKey('file_gf_thema', $decoded['metavox']['fieldDefinitions']);
        $this->assertCount(1, $decoded['pages']);
    }

    /**
     * Why it broke, stated directly, so nobody reintroduces the shorter spelling.
     */
    public function testRtrimWithABraceCharsetEatsMoreThanOneBrace(): void {
        $json = json_encode(['a' => ['b' => ['c' => 1]]]);
        $this->assertStringEndsWith('}}}', $json);

        $this->assertSame(
            '{"a":{"b":{"c":1',
            rtrim($json, "\n}"),
            'rtrim strips every trailing brace — this is the bug'
        );
        $this->assertSame(
            '{"a":{"b":{"c":1}}',
            substr($json, 0, strrpos($json, '}')),
            'strrpos removes exactly one, which is what the streaming needs'
        );
    }

    /** An export with no pages at all must still be readable. */
    public function testAnEmptyExportIsStillValidJson(): void {
        $doc = $this->assemble(['exportVersion' => '1.2', 'language' => 'nl'], []);

        $decoded = json_decode($doc, true);
        $this->assertNotNull($decoded, json_last_error_msg());
        $this->assertSame([], $decoded['pages']);
    }
}
