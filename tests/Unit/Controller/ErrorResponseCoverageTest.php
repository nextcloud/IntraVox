<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * Every operation documents the failures it can actually produce.
 *
 * The Tier B contract run reported 53 undocumented status codes across 98
 * endpoints. The overwhelming majority were 401: sixty authenticated operations
 * described their success and said nothing about what happens without a valid
 * session. That is not an edge case — it is the single most common non-2xx
 * response the API gives, and a generated client had no branch for it.
 *
 * Why static checks missed it: an absent response is not invalid OpenAPI.
 * check-openapi.js verifies that a documented operation is well-formed, redocly
 * warns about a missing 4xx but that warning sat at 25 and read as background
 * noise. Only calling the endpoints turned it into a number.
 *
 * Two rules, mirroring how the API actually behaves:
 *
 *  - An operation that requires authentication can answer 401. Always. It comes
 *    from Nextcloud before the app is reached.
 *  - An anonymous operation cannot answer 401 (there is nothing to authenticate)
 *    but can answer 429, because the share and feed endpoints carry
 *    AnonRateLimit and it is counted per address.
 */
class ErrorResponseCoverageTest extends TestCase {
    private const VERBS = ['get', 'post', 'put', 'delete', 'patch'];

    private array $spec;

    protected function setUp(): void {
        parent::setUp();
        $this->spec = json_decode(
            file_get_contents(__DIR__ . '/../../../openapi.json'),
            true
        );
    }

    /** @return list<array{verb:string,path:string,op:array}> */
    private function operations(): array {
        $out = [];
        foreach ($this->spec['paths'] as $path => $item) {
            foreach ($item as $verb => $op) {
                if (in_array($verb, self::VERBS, true) && is_array($op)) {
                    $out[] = ['verb' => strtoupper($verb), 'path' => $path, 'op' => $op];
                }
            }
        }

        return $out;
    }

    /** An empty requirement object marks the anonymous operations. */
    private function isAnonymous(array $op): bool {
        return ($op['security'] ?? null) === [[]];
    }

    public function testEveryAuthenticatedOperationDocuments401(): void {
        $missing = [];

        foreach ($this->operations() as ['verb' => $verb, 'path' => $path, 'op' => $op]) {
            if ($this->isAnonymous($op)) {
                continue;
            }
            if (!isset($op['responses']['401'])) {
                $missing[] = "{$verb} {$path}";
            }
        }

        sort($missing);

        $this->assertSame(
            [],
            $missing,
            "Authenticated operations with no documented 401:\n  " . implode("\n  ", $missing)
        );
    }

    /**
     * And an anonymous operation must not claim one.
     *
     * A 401 on a share endpoint would tell a client to go and authenticate, which
     * is impossible and misleading — the token in the path IS the credential, and
     * a bad one yields 403.
     */
    public function testAnonymousOperationsDoNotClaim401(): void {
        $wrong = [];

        foreach ($this->operations() as ['verb' => $verb, 'path' => $path, 'op' => $op]) {
            if ($this->isAnonymous($op) && isset($op['responses']['401'])) {
                $wrong[] = "{$verb} {$path}";
            }
        }

        $this->assertSame([], $wrong, 'A share token cannot be "unauthorized"; an invalid one is 403');
    }

    /**
     * Anonymous endpoints that carry AnonRateLimit document their 429.
     *
     * Read from the attributes rather than a list here, so adding a limit to a
     * new share endpoint fails this test until the spec catches up.
     */
    public function testRateLimitedAnonymousOperationsDocument429(): void {
        $limits = [];
        foreach (glob(__DIR__ . '/../../../lib/Controller/*.php') as $file) {
            $controller = lcfirst(preg_replace('/Controller$/', '', basename($file, '.php')));
            preg_match_all(
                '/#\[AnonRateLimit\(limit:\s*(\d+),\s*period:\s*(\d+)\)\][^;{]*?public function (\w+)/s',
                file_get_contents($file),
                $matches,
                PREG_SET_ORDER
            );
            foreach ($matches as [, $limit, , $method]) {
                $limits[$controller . '#' . $method] = $limit;
            }
        }

        $this->assertNotEmpty($limits, 'No AnonRateLimit found — the pattern went stale');

        // Needs node on PATH: the route table is parsed by a JS helper, because
        // appinfo/routes.php is the one source both this suite and the frontend
        // guards read. Without node, shell_exec() returns null and the failure
        // surfaces as "json_decode(): Argument #1 must be of type string, null
        // given" -- which says nothing about node. The guard below does.
        $routeJson = shell_exec(
            'cd ' . escapeshellarg(__DIR__ . '/../../../')
            . " && node -e \"const p=require('./scripts/lib/route-parser.js');console.log(JSON.stringify(p.parseRoutes()))\""
        );
        self::assertIsString(
            $routeJson,
            'could not read the route table -- is node on PATH? '
            . 'This test parses appinfo/routes.php through scripts/lib/route-parser.js.'
        );
        $routes = json_decode($routeJson, true);

        $missing = [];
        foreach ($routes as $route) {
            if (!isset($limits[$route['name']])) {
                continue;
            }
            $op = $this->spec['paths'][$route['url']][strtolower($route['verb'])] ?? null;
            if ($op !== null && !isset($op['responses']['429'])) {
                $missing[] = "{$route['verb']} {$route['url']} ({$route['name']})";
            }
        }

        sort($missing);

        $this->assertSame(
            [],
            $missing,
            "Anonymous rate-limited operations with no documented 429:\n  " . implode("\n  ", $missing)
        );
    }
}
