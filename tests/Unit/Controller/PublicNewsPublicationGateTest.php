<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\PublicShareController;
use OCA\IntraVox\Service\Publication\PublicationStateService;
use PHPUnit\Framework\TestCase;

/**
 * Public news respects publish and expiration dates. (READER-GATE)
 *
 * SystemFileService drops manual drafts, and its comment claimed "the share
 * endpoints in ApiController" enforced the publish/expiration dates that live in
 * MetaVox. They did not: getNewsByShare() returned the service result straight to
 * the caller, so a page scheduled for next month or expired last week appeared in
 * the public news list of a share.
 *
 * The gate that was missing is the same one the rest of the app uses,
 * isHiddenFromReaders(). There is no editor on a public share, so there is no
 * canWrite escape hatch here — hidden means hidden.
 */
class PublicNewsPublicationGateTest extends TestCase {

	/** @param list<string> $hiddenIds ids isHiddenFromReaders() should reject */
	private function controller(array $hiddenIds): PublicShareController {
		// The publication gate moved to PublicationStateService (Phase 3); the
		// controller calls isHiddenFromReaders on it, so that is what we stub.
		$publicationState = $this->createMock(PublicationStateService::class);
		$publicationState->method('isHiddenFromReaders')->willReturnCallback(
			static fn (array $page): bool => in_array($page['uniqueId'] ?? '', $hiddenIds, true)
		);

		$controller = (new \ReflectionClass(PublicShareController::class))->newInstanceWithoutConstructor();
		(new \ReflectionProperty(PublicShareController::class, 'publicationState'))->setValue($controller, $publicationState);

		return $controller;
	}

	/** @param list<array<string,mixed>> $items */
	private function filter(PublicShareController $controller, array $items): array {
		$method = new \ReflectionMethod(PublicShareController::class, 'filterUnpublishedNewsItems');

		return $method->invoke($controller, $items);
	}

	/** @return list<array<string,mixed>> */
	private function items(): array {
		return [
			['uniqueId' => 'live', 'title' => 'Published', 'status' => 'published', 'fileId' => 1],
			['uniqueId' => 'soon', 'title' => 'Scheduled', 'status' => 'published', 'fileId' => 2],
			['uniqueId' => 'old', 'title' => 'Expired', 'status' => 'published', 'fileId' => 3],
		];
	}

	/** The regression: a scheduled page must not appear in public news. */
	public function testScheduledAndExpiredItemsAreDropped(): void {
		$result = $this->filter($this->controller(['soon', 'old']), $this->items());

		$this->assertCount(1, $result);
		$this->assertSame('live', $result[0]['uniqueId']);
	}

	public function testPublishedItemsSurvive(): void {
		$result = $this->filter($this->controller([]), $this->items());

		$this->assertCount(3, $result);
	}

	/** The list must stay a list: the frontend indexes it. */
	public function testResultIsReindexed(): void {
		$result = $this->filter($this->controller(['live']), $this->items());

		$this->assertSame([0, 1], array_keys($result));
	}

	public function testEmptyInputStaysEmpty(): void {
		$this->assertSame([], $this->filter($this->controller([]), []));
	}

	/**
	 * The trap this fix nearly fell into: isHiddenFromReaders() reads $page['status']
	 * and $page['fileId']. A news item that carries neither defaults to "published",
	 * which makes the whole gate a silent no-op. SystemFileService must therefore
	 * put both on every item it builds.
	 */
	public function testNewsItemsCarryTheFieldsTheGateNeeds(): void {
		$source = (string)file_get_contents(
			\dirname(__DIR__, 3) . '/lib/Service/SystemFileService.php'
		);

		// Scope to findNewsPagesRecursive(): the same 'status' line also appears
		// in buildPageTreeRecursive(), so a whole-file search would pass even
		// with the news item left unguarded.
		$start = strpos($source, 'private function findNewsPagesRecursive');
		$this->assertNotFalse($start, 'findNewsPagesRecursive() moved or was renamed');
		$body = substr($source, $start);

		$this->assertStringContainsString(
			"'status' => \$data['status'] ?? 'published',",
			$body,
			'without status the publication gate cannot see a manual draft'
		);
		$this->assertStringContainsString(
			"'fileId' => \$jsonFile->getId(),",
			$body,
			'without fileId the gate cannot resolve the publish/expiration dates'
		);
	}
}
