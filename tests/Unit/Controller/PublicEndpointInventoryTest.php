<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * The anonymous surface is a list, not a discovery exercise. (F6)
 *
 * Reviewer question one of the refactor plan is "what does an anonymous visitor
 * reach?". Before F6 the answer was "grep 3900 lines of ApiController for
 * PublicPage and hope you found them all". The share endpoints now live in
 * PublicShareController, and this test pins the rest of the surface so a new
 * #[PublicPage] cannot appear unnoticed.
 *
 * A new public endpoint is not forbidden — it is a decision. Adding one means
 * adding it here, which is the point: the diff shows up in review as "this
 * changes what anonymous visitors can reach".
 */
class PublicEndpointInventoryTest extends TestCase {

	/**
	 * Every #[PublicPage] method in the app, as controller => methods.
	 *
	 * PublicShareController is the home for share endpoints. The others are
	 * deliberate exceptions, each with a reason:
	 *
	 *   ApiController::health         monitoring probe, returns app + version
	 *   PageController::*             the HTML pages themselves, not the API
	 *   FeedController::*             the RSS feed, which is token-authenticated
	 *
	 * As of F6d that is the whole list: no controller outside this one serves
	 * an anonymous share endpoint any more.
	 */
	private const EXPECTED = [
		'PublicShareController' => [
			// Reads one article body from the feed cache, guarded by the same
			// selector allowlist as the list it came from.
			'getArticleByShare',
			'getEventsByShare',
			// Several of this share's feeds in one request, each entry through
			// the same selector allowlist as a single fetch.
			'getFeedBatchByShare',
			'getFeedByShare',
			'getMediaByShare',
			'getNavigationByShare',
			'getNewsByShare',
			'getPageByShare',
			'getPageTreeByShare',
			'getPeopleByShare',
			'getResourcesMediaByShare',
			'getResourcesMediaWithFolderByShare',
			'proxyImageByShare',
		],
		'ApiController' => ['health'],
		'PageController' => ['index', 'shareAccess', 'shareAuthenticate'],
		'FeedController' => ['getFeed', 'getFeedMedia'],
	];

	/** @return array<string,list<string>> */
	private function actualPublicEndpoints(): array {
		$dir = \dirname(__DIR__, 3) . '/lib/Controller';
		$found = [];

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
		foreach ($iterator as $entry) {
			if (!$entry->isFile() || $entry->getExtension() !== 'php') {
				continue;
			}

			$source = (string)file_get_contents($entry->getPathname());
			$lines = preg_split('/\r\n|\n/', $source) ?: [];
			$controller = $entry->getBasename('.php');

			$previousEnd = -1;
			foreach ($lines as $index => $line) {
				if (preg_match('/^\s*(?:public|private|protected) function \w+/', $line) !== 1) {
					continue;
				}

				if (preg_match('/^\s*public function (\w+)/', $line, $m) === 1 && $m[1] !== '__construct') {
					// The head of the FIRST method in a file would otherwise reach
					// back over the whole preamble, so a #[PublicPage] anywhere in
					// the imports or class docblock would be attributed to it.
					$from = max($previousEnd + 1, $index - 20);
					$head = implode("\n", array_slice($lines, $from, $index - $from));
					if (str_contains($head, '#[PublicPage]')) {
						$found[$controller][] = $m[1];
					}
				}

				// Skip to the end of this method so the next head starts clean.
				$depth = 0;
				$started = false;
				for ($q = $index; $q < count($lines); $q++) {
					$depth += substr_count($lines[$q], '{') - substr_count($lines[$q], '}');
					if (str_contains($lines[$q], '{')) {
						$started = true;
					}
					if ($started && $depth === 0) {
						$previousEnd = $q;
						break;
					}
				}
			}
		}

		foreach ($found as &$methods) {
			sort($methods);
		}
		ksort($found);

		return $found;
	}

	/**
	 * The regression this exists for: an endpoint that quietly becomes public.
	 */
	public function testTheAnonymousSurfaceIsExactlyWhatWeExpect(): void {
		$expected = self::EXPECTED;
		foreach ($expected as &$methods) {
			sort($methods);
		}
		ksort($expected);

		$this->assertSame(
			$expected,
			$this->actualPublicEndpoints(),
			"The set of anonymous endpoints changed.\n"
			. "If that is intentional, update self::EXPECTED — the point is that it "
			. "shows up in review as a change to what unauthenticated visitors reach."
		);
	}

	/** Every share endpoint in the API lives in the one controller. */
	public function testShareApiEndpointsLiveInPublicShareController(): void {
		$actual = $this->actualPublicEndpoints();

		foreach ($actual as $controller => $methods) {
			if ($controller === 'PublicShareController') {
				continue;
			}

			foreach ($methods as $method) {
				// No exemptions left: F6d moved the last four. A share endpoint
				// anywhere else is now a failure, not a known gap.
				$this->assertStringNotContainsString(
					'ByShare',
					$method,
					"$controller::$method() looks like a share endpoint and belongs in PublicShareController"
				);
			}
		}
	}

	/** ApiController is the authenticated API and keeps only its health probe. */
	public function testApiControllerHasNoShareEndpointsLeft(): void {
		$actual = $this->actualPublicEndpoints();

		$this->assertSame(
			['health'],
			$actual['ApiController'] ?? [],
			'ApiController should carry no public endpoint other than the health probe'
		);
	}
}
