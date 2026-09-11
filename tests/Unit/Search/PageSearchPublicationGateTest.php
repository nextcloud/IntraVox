<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Search;

use OCA\IntraVox\Search\PageSearchProvider;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Publication\PublicationStateService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unified search must not surface unpublished pages. (SEARCH-ACL)
 *
 * PageSearchProvider had no publication gate on either of its two paths — the
 * title index and the full-text scan — so drafts, scheduled and expired pages
 * were returned to every user who could read the folder. The index table even
 * carries a status column and an index on (language, status); the query
 * selected status and then never filtered on it.
 *
 * The leak is the title and the URL of a page that is deliberately not live
 * yet: a reorganisation, a departure, an announcement being drafted.
 *
 * Everywhere else the rule is isHiddenFromReaders() plus a canWrite escape
 * hatch so editors still find their own drafts (ApiController does this in
 * seven places). Search now uses the same gate.
 */
class PageSearchPublicationGateTest extends TestCase {

	use BuildsPageRead;

	/** The publication gate now lives on PublicationStateService, not PageService. */
	private PublicationStateService $publicationState;
	/** The page getPage() returns (fase-4 C6: getPage moved to PageReadService). */
	private array $page = [];

	private function provider(?\OCA\IntraVox\Service\Read\PageReadService $pageRead = null): PageSearchProvider {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new PageSearchProvider(
			$this->createMock(PageService::class),   // searchPages path, not exercised here
			$pageRead ?? $this->fakePageReadReturning($this->page),
			$this->createMock(PageIndexService::class),
			$this->createMock(IConfig::class),
			$l10n,
			$this->createMock(IURLGenerator::class),
			$this->publicationState,
		);
	}

	/** Drive the private gate the indexed path uses. */
	private function isHidden(?string $uniqueId, ?\OCA\IntraVox\Service\Read\PageReadService $pageRead = null): bool {
		$method = new \ReflectionMethod(PageSearchProvider::class, 'isHiddenFromThisUser');

		return (bool)$method->invoke($this->provider($pageRead), $uniqueId);
	}

	/**
	 * getPage() now comes from PageReadService (fase-4 C6); the hidden/visible
	 * decision comes from PublicationStateService::isHiddenFromReaders, stubbed here.
	 */
	private function pageReturning(array $page, bool $hidden): void {
		$this->page = $page;
		$this->publicationState = $this->createMock(PublicationStateService::class);
		$this->publicationState->method('isHiddenFromReaders')->willReturn($hidden);
	}

	public function testPublishedPageIsVisible(): void {
		$this->pageReturning(['uniqueId' => 'p1', 'permissions' => ['canWrite' => false]], false);

		$this->assertFalse($this->isHidden('p1'));
	}

	/** The regression: a draft must not appear for a reader. */
	public function testDraftIsHiddenFromReader(): void {
		$this->pageReturning(['uniqueId' => 'p1', 'permissions' => ['canWrite' => false]], true);

		$this->assertTrue($this->isHidden('p1'));
	}

	/** ...but an editor must still find their own drafts. */
	public function testDraftIsVisibleToUserWithWritePermission(): void {
		$this->pageReturning(['uniqueId' => 'p1', 'permissions' => ['canWrite' => true]], true);

		$this->assertFalse($this->isHidden('p1'));
	}

	/** A page with no permissions block is treated as read-only. */
	public function testDraftWithoutPermissionsBlockIsHidden(): void {
		$this->pageReturning(['uniqueId' => 'p1'], true);

		$this->assertTrue($this->isHidden('p1'));
	}

	/** Fail closed: an unloadable page costs a hit rather than leaking one. */
	public function testUnloadablePageIsHidden(): void {
		$this->publicationState = $this->createMock(PublicationStateService::class);
		// A PageReadService whose getPage throws: build one whose cache getPageData
		// throws, so the cache-hit short-circuit propagates it verbatim.
		$cache = $this->createMock(\OCA\IntraVox\Service\Cache\PageCacheService::class);
		$cache->method('getPageData')->willThrowException(new \RuntimeException('gone'));
		$pageRead = $this->fakePageReadReturning(null);
		(new \ReflectionProperty(\OCA\IntraVox\Service\Read\PageReadService::class, 'cache'))
			->setValue($pageRead, $cache);

		$this->assertTrue($this->isHidden('p1', $pageRead));
	}

	public function testEmptyPageIsHidden(): void {
		$this->publicationState = $this->createMock(PublicationStateService::class);
		// getPage returns [] — but PageReadService treats a null cache entry as a
		// miss and would resolve for real; an empty array IS a hit, so return [].
		$this->assertTrue($this->isHidden('p1', $this->fakePageReadReturning([])));
	}

	public function testMissingUniqueIdIsHidden(): void {
		$this->publicationState = $this->createMock(PublicationStateService::class);

		$this->assertTrue($this->isHidden(null));
		$this->assertTrue($this->isHidden(''));
	}

	/**
	 * The full-text path applies the same rule inline. Pin both loops in the
	 * source so one cannot be dropped while the other keeps its gate.
	 */
	public function testBothSearchPathsApplyTheGate(): void {
		$source = (string)file_get_contents(
			\dirname(__DIR__, 3) . '/lib/Search/PageSearchProvider.php'
		);

		$this->assertStringContainsString(
			'$this->publicationState->isHiddenFromReaders($result)',
			$source,
			'the full-text search loop must gate on the publication state'
		);
		$this->assertStringContainsString(
			'$this->isHiddenFromThisUser($row[\'unique_id\'] ?? null)',
			$source,
			'the indexed search loop must gate on the publication state'
		);
	}
}
