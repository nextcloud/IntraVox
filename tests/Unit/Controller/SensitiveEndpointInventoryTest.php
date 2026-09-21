<?php
declare(strict_types=1);
namespace OCA\IntraVox\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * The credential- and directory-touching surface is a list, not a discovery
 * exercise — a companion to PublicEndpointInventoryTest for the authenticated
 * side.
 *
 * A cluster of the 09-2026 security review sat on #[NoAdminRequired] endpoints
 * that reach a stored credential or the whole user directory: the feed
 * connection helpers drove an admin-configured Jira/SharePoint/Moodle
 * connection, the feed-token endpoints managed a per-user token, the People
 * endpoints enumerated every account, and the LMS OAuth flow linked a token to
 * an account. Each of those must gate on IntraVox access (or session identity),
 * not merely on being logged in — mere authentication is not enough when the
 * endpoint speaks with someone else's credentials or exposes everyone's data.
 *
 * This pins which methods on those controllers are reachable by any logged-in
 * user (#[NoAdminRequired]). A NEW one is not forbidden — it is a decision that
 * has to be made deliberately: adding one means adding it here, which makes the
 * diff read, in review, as "this widens the credential/directory surface — is
 * it gated?". Removing the attribute (making it admin-only) equally forces an
 * update here.
 *
 * This does not assert the gate itself — the per-endpoint gate has its own
 * unit tests (FeedReaderAdminGateTest, FeedTokenAccessGateTest,
 * PeopleAccessGateTest, LmsOAuthCallbackBindingTest). It asserts that the
 * SET does not change unnoticed.
 */
class SensitiveEndpointInventoryTest extends TestCase {
	/**
	 * The controllers whose #[NoAdminRequired] surface touches a stored
	 * credential, the user directory, or a cross-account token flow.
	 */
	private const CONTROLLERS = [
		'FeedReaderController',
		'FeedController',
		'PeopleController',
		'LmsOAuthController',
	];

	/**
	 * Every #[NoAdminRequired] method on those controllers, as
	 * controller => methods. Each is reachable by any logged-in user; the
	 * comment on the gate in the controller says what limits it beyond that.
	 */
	private const EXPECTED = [
		'FeedController' => [
			'getToken',        // read own token (gated: denyUnlessIntraVoxAccess)
			'regenerateToken', // gated: denyUnlessIntraVoxAccess
			'revokeToken',     // gated: denyUnlessIntraVoxAccess
			'updateConfig',    // gated: denyUnlessIntraVoxAccess
		],
		'FeedReaderController' => [
			'getArticle',        // reads the cache entry its own config addresses
			'getConnections',    // names only to non-admins by design (FEED-CRED)
			'getCourses',        // gated: denyUnlessIntraVoxAccess
			'getFeed',           // caller's own config, no stored credential
			'getFeedBatch',      // same, several at once
			'getJiraProjects',   // gated: denyUnlessIntraVoxAccess
			'getMoodleForums',   // gated: denyUnlessIntraVoxAccess
			'getPreview',        // caller's own config, no stored credential
			'getSharePointLists',// gated: denyUnlessIntraVoxAccess
			'proxyImage',        // HMAC-signed external URLs only
		],
		'LmsOAuthController' => [
			'callback',        // bound to the session that started the flow
			'disconnect',
			'getUserConnections',
			'saveManualToken',
			'startOAuth',
		],
		'PeopleController' => [
			'facetPreflight',  // gated: denyUnlessIntraVoxAccess
			'getGroups',       // gated: denyUnlessIntraVoxAccess
			'getPeople',       // gated: denyUnlessIntraVoxAccess
			'getUserFields',   // gated: denyUnlessIntraVoxAccess
			'getUsers',        // gated: denyUnlessIntraVoxAccess
			'searchUsers',     // gated: denyUnlessIntraVoxAccess
		],
	];

	/** @return array<string,list<string>> */
	private function actualSensitiveEndpoints(): array {
		$dir = \dirname(__DIR__, 3) . '/lib/Controller';
		$found = [];

		foreach (self::CONTROLLERS as $controller) {
			$path = $dir . '/' . $controller . '.php';
			if (!is_file($path)) {
				$this->fail("Expected controller is missing: $controller");
			}
			$source = (string)file_get_contents($path);
			$lines = preg_split('/\r\n|\n/', $source) ?: [];

			$previousEnd = -1;
			foreach ($lines as $index => $line) {
				if (preg_match('/^\s*(?:public|private|protected) function \w+/', $line) !== 1) {
					continue;
				}

				if (preg_match('/^\s*public function (\w+)/', $line, $m) === 1 && $m[1] !== '__construct') {
					// Same window trick as PublicEndpointInventoryTest: only look
					// back to the previous method's end (capped at 20 lines) so an
					// attribute in the class preamble is not mis-attributed to the
					// first method.
					$from = max($previousEnd + 1, $index - 20);
					$head = implode("\n", array_slice($lines, $from, $index - $from));
					if (str_contains($head, '#[NoAdminRequired]')) {
						$found[$controller][] = $m[1];
					}
				}

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

	public function testTheCredentialAndDirectorySurfaceIsExactlyWhatWeExpect(): void {
		$expected = self::EXPECTED;
		foreach ($expected as &$methods) {
			sort($methods);
		}
		ksort($expected);

		$this->assertSame(
			$expected,
			$this->actualSensitiveEndpoints(),
			"The set of #[NoAdminRequired] endpoints on the credential/directory "
			. "controllers changed.\nA new one reaches a stored credential or the "
			. "user directory for any logged-in user. Confirm it gates on IntraVox "
			. "access (or session identity), give it a gate test, then record it in "
			. self::class . "::EXPECTED."
		);
	}
}
