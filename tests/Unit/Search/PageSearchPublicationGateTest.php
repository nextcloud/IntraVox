<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Search;

use OCA\IntraVox\Search\PageSearchProvider;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Publication\PublicationStateService;
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

	/** The publication gate now lives on PublicationStateService, not PageService. */
	private PublicationStateService $publicationState;

	private function provider(PageService $pageService): PageSearchProvider {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new PageSearchProvider(
			$pageService,
			$this->createMock(PageIndexService::class),
			$this->createMock(IConfig::class),
			$l10n,
			$this->createMock(IURLGenerator::class),
			$this->publicationState,
		);
	}

	/** Drive the private gate the indexed path uses. */
	private function isHidden(PageService $pageService, ?string $uniqueId): bool {
		$method = new \ReflectionMethod(PageSearchProvider::class, 'isHiddenFromThisUser');

		return (bool)$method->invoke($this->provider($pageService), $uniqueId);
	}

	/**
	 * getPage() still comes from PageService; the hidden/visible decision now
	 * comes from PublicationStateService::isHiddenFromReaders, which this stubs.
	 */
	private function pageServiceReturning(array $page, bool $hidden): PageService {
		$service = $this->createMock(PageService::class);
		$service->method('getPage')->willReturn($page);

		$this->publicationState = $this->createMock(PublicationStateService::class);
		$this->publicationState->method('isHiddenFromReaders')->willReturn($hidden);

		return $service;
	}

	public function testPublishedPageIsVisible(): void {
		$service = $this->pageServiceReturning(['uniqueId' => 'p1', 'permissions' => ['canWrite' => false]], false);

		$this->assertFalse($this->isHidden($service, 'p1'));
	}

	/** The regression: a draft must not appear for a reader. */
	public function testDraftIsHiddenFromReader(): void {
		$service = $this->pageServiceReturning(['uniqueId' => 'p1', 'permissions' => ['canWrite' => false]], true);

		$this->assertTrue($this->isHidden($service, 'p1'));
	}

	/** ...but an editor must still find their own drafts. */
	public function testDraftIsVisibleToUserWithWritePermission(): void {
		$service = $this->pageServiceReturning(['uniqueId' => 'p1', 'permissions' => ['canWrite' => true]], true);

		$this->assertFalse($this->isHidden($service, 'p1'));
	}

	/** A page with no permissions block is treated as read-only. */
	public function testDraftWithoutPermissionsBlockIsHidden(): void {
		$service = $this->pageServiceReturning(['uniqueId' => 'p1'], true);

		$this->assertTrue($this->isHidden($service, 'p1'));
	}

	/** Fail closed: an unloadable page costs a hit rather than leaking one. */
	public function testUnloadablePageIsHidden(): void {
		$this->publicationState = $this->createMock(PublicationStateService::class);
		$service = $this->createMock(PageService::class);
		$service->method('getPage')->willThrowException(new \RuntimeException('gone'));

		$this->assertTrue($this->isHidden($service, 'p1'));
	}

	public function testEmptyPageIsHidden(): void {
		$this->publicationState = $this->createMock(PublicationStateService::class);
		$service = $this->createMock(PageService::class);
		$service->method('getPage')->willReturn([]);

		$this->assertTrue($this->isHidden($service, 'p1'));
	}

	public function testMissingUniqueIdIsHidden(): void {
		$this->publicationState = $this->createMock(PublicationStateService::class);
		$service = $this->createMock(PageService::class);

		$this->assertTrue($this->isHidden($service, null));
		$this->assertTrue($this->isHidden($service, ''));
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
