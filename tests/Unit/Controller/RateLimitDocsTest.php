<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * The documented rate limits are the real ones.
 *
 * api-reference.md now names a number per endpoint, and integrators pace their
 * imports on it. A table of numbers maintained by hand next to attributes changed
 * in code is exactly how openapi.json ended up describing 45% of this API, so it
 * gets a guard rather than good intentions.
 *
 * This checks the direction that actually hurts: a limit in code that the docs do
 * not mention, or mention with the wrong number. An integrator pacing at the
 * documented rate then walks into a 429 it was told would not come.
 *
 * It scans EVERY controller, not just ApiController. The first version looked at
 * one file and consequently missed createComment's 20/minute for as long as it
 * existed — a guard that covers one of eight controllers reports green while
 * seven of them drift.
 */
class RateLimitDocsTest extends TestCase {
    private const DOCS = __DIR__ . '/../../../docs/architecture/api-reference.md';
    private const CONTROLLER_DIR = __DIR__ . '/../../../lib/Controller';

    /**
     * Every #[UserRateLimit] in every controller, as handler => limit.
     *
     * Anonymous limits are excluded on purpose: AnonRateLimit is counted per
     * client rather than per user and the share endpoints carry a uniform
     * 30–60, which the docs describe as a range rather than per handler.
     */
    private function userRateLimits(): array {
        $found = [];

        foreach (glob(self::CONTROLLER_DIR . '/*.php') as $file) {
            preg_match_all(
                '/#\[UserRateLimit\(limit:\s*(\d+),\s*period:\s*(\d+)\)\][^;{]*?public function (\w+)/s',
                file_get_contents($file),
                $matches,
                PREG_SET_ORDER
            );

            // routes.php identifies a handler as controller#method, and so must
            // this: two controllers can and do share a method name.
            $controller = lcfirst(preg_replace('/Controller$/', '', basename($file, '.php')));

            foreach ($matches as [, $limit, $period, $method]) {
                $found[] = [
                    'handler' => $controller . '#' . $method,
                    'method' => $method,
                    'limit' => $limit,
                    'period' => $period,
                    'file' => basename($file),
                ];
            }
        }

        return $found;
    }

    public function testEveryRateLimitInCodeIsDocumentedWithItsRealNumber(): void {
        $docs = file_get_contents(self::DOCS);
        $limits = $this->userRateLimits();

        $this->assertNotEmpty($limits, 'No rate limits found — the pattern went stale');
        $this->assertGreaterThanOrEqual(
            10,
            count($limits),
            'Far fewer than expected; the attribute pattern probably stopped matching'
        );

        $undocumented = [];
        foreach ($limits as $hit) {
            $this->assertSame(
                '60',
                $hit['period'],
                "{$hit['method']} uses a non-minute window; the docs table says 'per minute'"
            );

            // The handler name and its number must both appear in the docs.
            if (!str_contains($docs, $hit['method']) || !str_contains($docs, "| {$hit['limit']} / minute |")) {
                $undocumented[] = "{$hit['file']}::{$hit['method']} ({$hit['limit']}/minute)";
            }
        }

        sort($undocumented);

        $this->assertSame(
            [],
            $undocumented,
            "Rate limits in code that api-reference.md does not name:\n  " . implode("\n  ", $undocumented)
        );
    }

    /**
     * And the spec says so too.
     *
     * A limit documented in prose but missing from openapi.json means a generated
     * client has no 429 branch at all — it sees an undeclared status and treats a
     * routine throttle as a protocol error.
     */
    public function testEveryRateLimitedHandlerDeclares429InTheSpec(): void {
        $spec = json_decode(
            file_get_contents(__DIR__ . '/../../../openapi.json'),
            true
        );

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

        $byHandler = [];
        foreach ($routes as $route) {
            $byHandler[$route['name']][] = [$route['verb'], $route['url']];
        }

        $missing = [];
        foreach ($this->userRateLimits() as $hit) {
            foreach ($byHandler[$hit['handler']] ?? [] as [$verb, $url]) {
                $op = $spec['paths'][$url][strtolower($verb)] ?? null;
                if ($op === null) {
                    continue;
                }
                if (!isset($op['responses']['429'])) {
                    $missing[] = "{$verb} {$url} ({$hit['method']}, {$hit['limit']}/minute)";
                }
            }
        }

        sort($missing);

        $this->assertSame(
            [],
            $missing,
            "Rate-limited operations with no documented 429:\n  " . implode("\n  ", $missing)
        );
    }

    /** The migration escape hatch must stay documented, including turning it off again. */
    public function testTheMigrationBypassIsDocumentedWithItsCleanup(): void {
        $docs = file_get_contents(self::DOCS);

        $this->assertStringContainsString('apply_allowlist_to_ratelimit', $docs);
        $this->assertStringContainsString('config:app:delete', $docs, 'Telling people how to open it without how to close it is worse than silence');
    }
}
