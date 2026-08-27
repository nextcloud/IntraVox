<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\PublicShare;

use OCA\IntraVox\Service\PublicShare\ShareTreeShaper;
use PHPUnit\Framework\TestCase;

/**
 * A share scope that matches nothing publishes nothing. (SCOPE-FAILOPEN)
 *
 * extractSubtreeByScope() ended in `return $tree` — the FULL language tree — when
 * it could not find the scoped node. So a share scoped to nl/afdeling whose page
 * had since been moved or deleted stopped being a narrow share and started
 * publishing every page in that language to anonymous visitors.
 *
 * A missing subtree is a reason to show nothing, never everything. The language
 * root remains the one case where returning the whole tree is correct: sharing
 * "nl" really does share all of nl.
 *
 * Moved with the method from PublicShareController to ShareTreeShaper in the
 * controller split (PR-B). It no longer needs reflection: the transform is pure
 * and public, so the test calls it directly.
 */
class ShareScopeFailClosedTest extends TestCase {

	private function extract(array $tree, string $scopePath): array {
		return (new ShareTreeShaper())->extractSubtreeByScope($tree, $scopePath);
	}

	/** @return list<array<string,mixed>> */
	private function tree(): array {
		return [
			[
				'path' => 'nl/afdeling',
				'title' => 'Afdeling',
				'children' => [
					['path' => 'nl/afdeling/hr', 'title' => 'HR', 'children' => []],
				],
			],
			['path' => 'nl/nieuws', 'title' => 'Nieuws', 'children' => []],
			['path' => 'nl/geheim', 'title' => 'Geheim project', 'children' => []],
		];
	}

	/** Sharing the language root really does share the language. */
	public function testLanguageRootReturnsTheWholeTree(): void {
		$this->assertSame($this->tree(), $this->extract($this->tree(), 'nl'));
	}

	public function testAMatchingScopeReturnsJustThatSubtree(): void {
		$result = $this->extract($this->tree(), 'nl/afdeling');

		$this->assertCount(1, $result);
		$this->assertSame('nl/afdeling', $result[0]['path']);
	}

	public function testANestedScopeIsFound(): void {
		$result = $this->extract($this->tree(), 'nl/afdeling/hr');

		$this->assertCount(1, $result);
		$this->assertSame('nl/afdeling/hr', $result[0]['path']);
	}

	/**
	 * The regression. On the pre-fix code this returned all three top-level
	 * nodes — including "Geheim project" — to an anonymous visitor.
	 */
	public function testAScopeThatMatchesNothingReturnsNothing(): void {
		$result = $this->extract($this->tree(), 'nl/verplaatst-of-verwijderd');

		$this->assertSame([], $result, 'an unmatched scope must publish nothing');
	}

	/** Including when the tree is empty to begin with. */
	public function testAnEmptyTreeStaysEmpty(): void {
		$this->assertSame([], $this->extract([], 'nl/afdeling'));
	}

	/** A deep scope that does not exist must not fall back to a shallower match. */
	public function testADeepScopeThatDoesNotExistReturnsNothing(): void {
		$result = $this->extract($this->tree(), 'nl/afdeling/hr/salarissen');

		$this->assertSame([], $result);
	}
}
