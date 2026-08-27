<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\ApiController;
use PHPUnit\Framework\TestCase;

/**
 * What openapi.json promises about the two listings is what the code does.
 *
 * The audit that opened this release found twelve of eighteen sampled operations
 * materially wrong, and both listings were among them: PageSummary omitted
 * `status` although every row carries it, and the media listing documented a
 * `files` key while the handler returns `media` — a spec-conforming client read a
 * key that does not exist and saw nothing, with no error to explain it.
 *
 * Those are exactly the defects a coverage guard cannot see. check-openapi.js
 * proves an operation EXISTS; only something that compares the promise against the
 * implementation can show it is TRUE. This is the cheap, static half of that: it
 * cannot run the endpoint, but it can catch a documented constant or key that no
 * longer matches the source.
 */
class ListingSpecContractTest extends TestCase {
    private array $spec;
    /** The page listing lives here... */
    private string $source;
    /** ...and the media listing moved to its own controller in the PR-A split. */
    private string $mediaSource;

    protected function setUp(): void {
        parent::setUp();
        $this->spec = json_decode(file_get_contents(__DIR__ . '/../../../openapi.json'), true);
        $this->source = file_get_contents(__DIR__ . '/../../../lib/Controller/ApiController.php');
        $this->mediaSource = file_get_contents(__DIR__ . '/../../../lib/Controller/MediaApiController.php');
    }

    private function constant(string $name): int {
        return (new \ReflectionClass(ApiController::class))->getConstant($name);
    }


    public function testTheDocumentedListingCapMatchesTheConstant(): void {
        $described = $this->spec['paths']['/api/pages']['get']['description'];

        $this->assertStringContainsString(
            (string)$this->constant('MAX_PAGES_IN_LISTING'),
            $described,
            'The cap named in the docs must be the cap the code applies'
        );
    }

    public function testTheDocumentedMaximumPageSizeMatchesTheConstant(): void {
        $limit = null;
        foreach ($this->spec['paths']['/api/pages']['get']['parameters'] as $param) {
            if ($param['name'] === 'limit') {
                $limit = $param;
            }
        }

        $this->assertNotNull($limit, 'limit must be documented; it changes the response shape');
        $this->assertSame($this->constant('MAX_PAGE_SIZE'), $limit['schema']['maximum']);
        $this->assertSame($this->constant('DEFAULT_PAGE_SIZE'), $limit['schema']['default']);
    }

    /** There must be no offset parameter — the choice of keyset is the contract. */
    public function testOffsetIsNotDocumentedAsAParameter(): void {
        $names = array_column($this->spec['paths']['/api/pages']['get']['parameters'], 'name');

        $this->assertNotContains('offset', $names);
        $this->assertContains('cursor', $names);
    }

    public function testTheMediaListingDocumentsTheKeyTheHandlerActuallyReturns(): void {
        $schema = $this->spec['paths']['/api/pages/{pageId}/media/list']['get']
            ['responses']['200']['content']['application/json']['schema'];

        $this->assertArrayHasKey(
            'media',
            $schema['properties'],
            "The handler returns ['media' => ...]; documenting 'files' sends clients looking for a key that is not there"
        );
        $this->assertStringContainsString("'media' => array_slice(", $this->mediaSource);
    }

    /** Both listings advertise the truncation headers they actually send. */
    public function testBothListingsDocumentTheirTruncationHeaders(): void {
        foreach (['/api/pages', '/api/pages/{pageId}/media/list'] as $path) {
            $headers = $this->spec['paths'][$path]['get']['responses']['200']['headers'] ?? [];

            $this->assertArrayHasKey('X-IntraVox-Cap', $headers, "{$path} sends this header");
            $this->assertArrayHasKey('X-IntraVox-Truncated', $headers, "{$path} sends this header");
        }

        // One assertion per source, because the two listings are two classes
        // since the PR-A split. Checking only $this->source would leave the
        // media half of "both listings" unproven.
        $this->assertStringContainsString("addHeader('X-IntraVox-Cap'", $this->source);
        $this->assertStringContainsString("addHeader('X-IntraVox-Cap'", $this->mediaSource);
        $this->assertStringContainsString("addHeader('X-IntraVox-Truncated'", $this->source);
    }

    /** PageSummary must name every field the listing actually returns. */
    public function testPageSummaryNamesStatus(): void {
        $properties = $this->spec['components']['schemas']['PageSummary']['properties'];

        foreach (['uniqueId', 'title', 'modified', 'status', 'permissions'] as $field) {
            $this->assertArrayHasKey($field, $properties, "The listing returns {$field}");
        }
    }
}
