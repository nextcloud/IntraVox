<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Per-user data must never enter the distributed cache. (CACHE-NS)
 *
 * The distributed cache is shared across every user of the instance, so any
 * field whose value depends on WHO is asking leaks between them for the
 * lifetime of the entry. This actually happened: issue #70 saw an editor's
 * canWrite served to a read-only user for up to an hour, leaving the Edit
 * button visible to someone whose save then 403'd.
 *
 * That bug was fixed in 7f51849 by stripping the per-user fields before
 * storing, and the audit that produced this refactor plan predates the fix.
 * What was missing is anything that keeps it fixed: delete the unset() line and
 * the whole suite still passes. This test pins the property.
 *
 * It reads the source rather than exercising the cache because the alternative
 * is standing up a distributed cache plus two user sessions in a unit test; the
 * property being protected is exactly "this line is still here".
 */
class DistributedCacheScopeTest extends TestCase {

	/**
	 * Fields whose value depends on the requesting user. None may be written to
	 * a cache shared by all of them.
	 */
	private const PER_USER_FIELDS = [
		'permissions',
		'canEdit',
		'metaVoxAvailable',
		'translations',
	];

	private function sourceOf(string $relPath): string {
		$path = \dirname(__DIR__, 3) . '/' . $relPath;
		$this->assertFileExists($path);

		return (string)file_get_contents($path);
	}

	private function pageServiceSource(): string {
		return $this->sourceOf('lib/Service/PageService.php');
	}

	public function testPerUserFieldsAreStrippedBeforeDistributedCaching(): void {
		// The single-page read + its distributed-content cache moved to
		// PageReadService (god-class dissolution, read cluster); the strip lives
		// there now. The tree/news cache keys below still live on PageService.
		$source = $this->sourceOf('lib/Service/Read/PageReadService.php');

		// The single place a page body is written to the shared cache.
		$this->assertStringContainsString(
			'$cacheable = $sanitizedData;',
			$source,
			'the distributed page-content cache no longer builds a $cacheable copy; '
			. 'if this moved, move this test with it'
		);

		foreach (self::PER_USER_FIELDS as $field) {
			$this->assertMatchesRegularExpression(
				'/unset\(\s*\$cacheable\[[^)]*\'' . preg_quote($field, '/') . '\'/',
				$source,
				sprintf(
					'"%s" is per-user and must be unset from $cacheable before setDistributed(); '
					. 'caching it leaks one user\'s view to another (issue #70)',
					$field
				)
			);
		}
	}

	/**
	 * The tree cache is shared too. It is keyed by group hash rather than
	 * stripped, because a tree legitimately differs per permission set — users
	 * in the same groups see the same tree, users in different groups must not
	 * share an entry.
	 */
	public function testTreeCacheKeyIsScopedByGroupHash(): void {
		$source = $this->pageServiceSource();

		$this->assertMatchesRegularExpression(
			'/\$cacheKey\s*=\s*\$this->groupContext->getGroupHash\(\)/',
			$source,
			'the page-tree cache key must include the group hash, or one group\'s '
			. 'tree is served to another'
		);

		$this->assertStringContainsString(
			"\$distributedCacheKey = 'tree_' . \$cacheKey;",
			$source,
			'the distributed tree key must derive from the group-scoped cache key'
		);
	}

	/**
	 * The news cache is the third shared entry and carries the same risk.
	 */
	public function testNewsCacheKeyIsScopedByGroupHash(): void {
		$source = $this->pageServiceSource();

		$this->assertMatchesRegularExpression(
			'/\$newsCacheKey\s*=\s*\'news_\'\s*\.\s*\$language\s*\.\s*\'_\'\s*\.\s*\$this->groupContext->getGroupHash\(\)/',
			$source,
			'the news cache key must include the group hash'
		);
	}
}
