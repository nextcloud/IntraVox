<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Sanitize;

use OCA\IntraVox\Service\Sanitize\ColorSanitizer;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;
use OCA\IntraVox\Service\Sanitize\UrlSanitizer;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * What sanitizeWidget() lets through, per widget type. (F7 / QG-5)
 *
 * 672 lines and no coverage: the largest single method in the app was also the
 * one deciding which caller-supplied keys survive into stored page JSON. It is
 * reached on every page save and on every import, so "what does this let
 * through" is the question a reviewer most needs answered — and until now the
 * only answer was to read all 672 lines.
 *
 * The guarantee these tests pin is structural rather than incidental. Each
 * branch builds a FRESH $sanitized array and copies fields into it one by one,
 * so an unknown key is dropped because it is never copied, not because
 * something removed it. That is the right design, and it is worth a test that
 * fails if someone ever "fixes" it into a blocklist.
 *
 * Every case therefore asserts both directions, per the F7 exit criterion:
 * the keys a type keeps, and a key it must refuse.
 */
class WidgetSanitizerSpecTest extends TestCase {

	private PageShapeSanitizer $sanitizer;

	protected function setUp(): void {
		parent::setUp();

		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')->willReturnCallback(
			static fn(string $app, string $key, $default = null) => $default
		);

		$this->sanitizer = new PageShapeSanitizer(
			$config,
			$this->createMock(LoggerInterface::class),
			new HtmlSanitizer(),
			new UrlSanitizer(),
			new ColorSanitizer(),
		);
	}

	/** The twelve types the allowlist admits. */
	public static function widgetTypes(): array {
		return [
			['text'], ['heading'], ['image'], ['links'], ['divider'], ['video'],
			['news'], ['people'], ['calendar'], ['feed'], ['photo-story'], ['file-story'],
		];
	}

	// ---------- the allowlist itself ----------

	/**
	 * An unknown type is not sanitized into something harmless — it is dropped.
	 */
	public function testUnknownWidgetTypeIsRejectedEntirely(): void {
		$this->assertNull($this->sanitizer->sanitizeWidget(['type' => 'script']));
		$this->assertNull($this->sanitizer->sanitizeWidget(['type' => 'iframe']));
		$this->assertNull($this->sanitizer->sanitizeWidget(['type' => '']));
	}

	public function testWidgetWithoutTypeIsRejected(): void {
		$this->assertNull($this->sanitizer->sanitizeWidget(['content' => 'orphan']));
	}

	/**
	 * The core property, asserted for every type at once: a key nobody named is
	 * a key nobody gets. This is what makes the method an allowlist.
	 *
	 * @dataProvider widgetTypes
	 */
	public function testEveryTypeDropsKeysItDoesNotKnow(string $type): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => $type,
			'onclick' => 'alert(1)',
			'__proto__' => ['polluted' => true],
			'isAdmin' => true,
			'permissions' => 31,
			'ownerId' => 'someone-else',
		]);

		$this->assertIsArray($result, "$type should be a known widget type");

		foreach (['onclick', '__proto__', 'isAdmin', 'permissions', 'ownerId'] as $forbidden) {
			$this->assertArrayNotHasKey(
				$forbidden,
				$result,
				"$type widget must not carry through '$forbidden'"
			);
		}
	}

	/** Every type keeps the three structural fields, and coerces them. */
	public function testEveryTypeKeepsAndClampsTheStructuralFields(): void {
		foreach (self::widgetTypes() as [$type]) {
			$result = $this->sanitizer->sanitizeWidget([
				'type' => $type,
				'column' => 99,
				'order' => '7',
			]);

			$this->assertSame($type, $result['type']);
			$this->assertSame(5, $result['column'], "$type: column clamps to MAX_COLUMNS");
			$this->assertSame(7, $result['order'], "$type: order is cast to int");
		}
	}

	public function testColumnIsClampedUpwardsToo(): void {
		$result = $this->sanitizer->sanitizeWidget(['type' => 'text', 'column' => 0]);
		$this->assertSame(1, $result['column']);
	}

	// ---------- per type: what it keeps, and one thing it refuses ----------

	public function testTextKeepsSanitizedHtmlAndDropsScript(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'text',
			'content' => '<p>Hallo <strong>daar</strong></p><script>alert(1)</script>',
			'rawHtml' => '<script>alert(2)</script>',
		]);

		$this->assertStringContainsString('<strong>daar</strong>', $result['content']);
		$this->assertStringNotContainsString('<script', $result['content']);
		$this->assertArrayNotHasKey('rawHtml', $result);
	}

	public function testHeadingClampsLevelAndStripsMarkup(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'heading',
			'content' => '<script>alert(1)</script>Titel',
			'level' => 99,
			'href' => 'javascript:alert(1)',
		]);

		$this->assertSame(6, $result['level']);
		$this->assertStringNotContainsString('<script', $result['content']);
		$this->assertArrayNotHasKey('href', $result);
	}

	public function testHeadingLevelFloorsAtOne(): void {
		$result = $this->sanitizer->sanitizeWidget(['type' => 'heading', 'level' => -3]);
		$this->assertSame(1, $result['level']);
	}

	public function testImageKeepsItsOwnKeysAndConstrainsEnums(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'image',
			'src' => 'photo.jpg',
			'alt' => 'Een foto',
			'objectFit' => 'evil',
			'objectPosition' => 'evil',
			'mediaFolder' => 'evil',
			'linkType' => 'evil',
			'srcset' => 'x',
		]);

		$this->assertSame('Een foto', $result['alt']);
		$this->assertSame('cover', $result['objectFit'], 'unknown objectFit falls back');
		$this->assertSame('center', $result['objectPosition']);
		$this->assertSame('page', $result['mediaFolder']);
		$this->assertSame('none', $result['linkType']);
		$this->assertArrayNotHasKey('srcset', $result);
	}

	public function testImageLinkUrlGoesThroughTheUrlAllowlist(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'image',
			'linkType' => 'external',
			'linkUrl' => 'javascript:alert(1)',
		]);

		$this->assertSame('external', $result['linkType']);
		$this->assertStringNotContainsString('javascript:', $result['linkUrl']);
	}

	/**
	 * The measured display metadata (naturalWidth/naturalHeight/bgColor) must
	 * SURVIVE sanitization — the sanitizer is an allowlist, so an unlisted field
	 * is silently dropped and the whole no-layout-shift feature would then break
	 * the moment a page is saved. This pins it against that silent regression.
	 */
	public function testImageKeepsMeasuredDisplayMetadata(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'image',
			'src' => 'photo.jpg',
			'naturalWidth' => 1200,
			'naturalHeight' => 797,
			'bgColor' => '#8A6D4F',
		]);

		$this->assertSame(1200, $result['naturalWidth']);
		$this->assertSame(797, $result['naturalHeight']);
		$this->assertSame('#8a6d4f', $result['bgColor'], 'hex is lower-cased, kept');
	}

	public function testImageDropsNonPositiveOrGarbageDimensions(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'image',
			'src' => 'photo.jpg',
			'naturalWidth' => 0,
			'naturalHeight' => -5,
		]);

		$this->assertArrayNotHasKey('naturalWidth', $result, '0 is dropped, not stored');
		$this->assertArrayNotHasKey('naturalHeight', $result, 'negative is dropped');
	}

	public function testImageRejectsANonHexBgColorSoItCannotInjectCss(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'image',
			'src' => 'photo.jpg',
			// A CSS-injection attempt via the placeholder colour must be refused.
			'bgColor' => 'red; background-image: url(javascript:alert(1))',
		]);

		$this->assertArrayNotHasKey('bgColor', $result, 'only #rrggbb survives');
	}

	public function testLinksClampsColumnsAndRefusesUnknownLayout(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'links',
			'columns' => 99,
			'layout' => 'evil',
			'items' => [],
			'target' => '_blank',
		]);

		$this->assertSame(4, $result['columns']);
		$this->assertSame('list', $result['layout']);
		$this->assertArrayNotHasKey('target', $result);
	}

	public function testDividerConstrainsStyleAndHeightFormat(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'divider',
			'style' => 'evil',
			'height' => 'expression(alert(1))',
			'margin' => '9999px',
		]);

		$this->assertSame('solid', $result['style']);
		$this->assertNotSame('expression(alert(1))', $result['height'] ?? null);
		$this->assertArrayNotHasKey('margin', $result);
	}

	public function testDividerAcceptsAWellFormedHeight(): void {
		$result = $this->sanitizer->sanitizeWidget(['type' => 'divider', 'height' => '12px']);
		$this->assertSame('12px', $result['height']);
	}

	public function testVideoCastsFlagsAndDropsUnknownKeys(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'video',
			'title' => 'Uitleg',
			'autoplay' => 'yes',
			'loop' => 0,
			'muted' => 1,
			'onerror' => 'alert(1)',
		]);

		$this->assertTrue($result['autoplay']);
		$this->assertFalse($result['loop']);
		$this->assertTrue($result['muted']);
		$this->assertArrayNotHasKey('onerror', $result);
	}

	public function testNewsClampsItsNumericRangesAndRefusesUnknownSort(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'news',
			'limit' => 9999,
			'excerptLength' => 1,
			'columns' => 99,
			'sortBy' => 'evil',
			'sortOrder' => 'evil',
			'autoplayInterval' => 9999,
			'sourceQuery' => 'DROP TABLE',
		]);

		$this->assertSame(20, $result['limit']);
		$this->assertSame(50, $result['excerptLength'], 'excerptLength floors at 50');
		$this->assertSame(4, $result['columns']);
		$this->assertSame('modified', $result['sortBy']);
		$this->assertSame('desc', $result['sortOrder']);
		$this->assertSame(30, $result['autoplayInterval']);
		$this->assertArrayNotHasKey('sourceQuery', $result);
	}

	public function testPeopleClampsLimitAndRefusesUnknownSelectionMode(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'people',
			'limit' => 9999,
			'columns' => 99,
			'selectionMode' => 'evil',
			'sortBy' => 'evil',
			'includePasswordHash' => true,
		]);

		$this->assertSame(50, $result['limit'], 'a People widget may not fetch the whole directory');
		$this->assertSame(4, $result['columns']);
		$this->assertSame('manual', $result['selectionMode']);
		$this->assertSame('displayName', $result['sortBy']);
		$this->assertArrayNotHasKey('includePasswordHash', $result);
	}

	public function testCalendarRefusesNonHttpsIcsUrls(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'calendar',
			'externalIcsUrls' => [
				'https://example.com/agenda.ics',
				'http://example.com/insecure.ics',
				'javascript:alert(1)',
				'file:///etc/passwd',
			],
			'limit' => 9999,
			'ownerId' => 'someone-else',
		]);

		$this->assertSame(['https://example.com/agenda.ics'], $result['externalIcsUrls']);
		$this->assertSame(20, $result['limit']);
		$this->assertArrayNotHasKey('ownerId', $result);
	}

	public function testCalendarIdsAreCoercedToStrings(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'calendar',
			'calendarIds' => ['personal', 42],
		]);

		foreach ($result['calendarIds'] as $id) {
			$this->assertIsString($id);
		}
	}

	public function testFeedConstrainsSourceTypeAndTrimsKeyword(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'feed',
			'sourceType' => 'evil',
			'filterKeyword' => str_repeat('a', 500),
			'limit' => 9999,
			'apiToken' => 'secret',
		]);

		$this->assertSame('rss', $result['sourceType']);
		$this->assertSame(100, mb_strlen($result['filterKeyword']));
		$this->assertSame(20, $result['limit']);
		$this->assertArrayNotHasKey('apiToken', $result, 'a stored widget must never carry credentials');
	}

	/**
	 * showTitle was added after feed widgets were already in the wild. A stored
	 * widget has no such key, and defaulting it to false would silently hide a
	 * title the editor had been saving all along.
	 */
	public function testFeedShowTitleDefaultsToTrueForWidgetsSavedBeforeTheOption(): void {
		$zonderSleutel = $this->sanitizer->sanitizeWidget([
			'type' => 'feed',
			'title' => 'Laatste nieuws',
		]);
		$this->assertTrue($zonderSleutel['showTitle']);

		$uit = $this->sanitizer->sanitizeWidget([
			'type' => 'feed',
			'title' => 'Laatste nieuws',
			'showTitle' => false,
		]);
		$this->assertFalse($uit['showTitle'], 'an explicit false must survive the sanitizer');
	}

	public function testPhotoStoryKeepsOnlyItsConfig(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'photo-story',
			'config' => ['folder' => '/Photos'],
			'shellCommand' => 'rm -rf /',
		]);

		$this->assertArrayHasKey('config', $result);
		$this->assertArrayNotHasKey('shellCommand', $result);
	}

	public function testFileStoryKeepsOnlyItsConfig(): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => 'file-story',
			'config' => ['folder' => '/Docs'],
			'shellCommand' => 'rm -rf /',
		]);

		$this->assertArrayHasKey('config', $result);
		$this->assertArrayNotHasKey('shellCommand', $result);
	}

	// ---------- defaults must actually be the defaults ----------

	/**
	 * The bug QG-5 found: an omitted enum key stored null, not its default.
	 *
	 * Seventeen sites were written as
	 *
	 *     in_array($widget['sortBy'] ?? 'modified', $allowed) ? $widget['sortBy'] : 'modified'
	 *
	 * The `?? 'modified'` guards only the in_array() test. When the key is
	 * absent that test passes on the default, and the ternary then reads the
	 * missing key — emitting a notice and assigning null. So a widget saved
	 * without an explicit sortBy stored sortBy: null rather than "modified",
	 * and that is the ordinary case, not an edge case.
	 *
	 * @dataProvider defaultingKeys
	 */
	public function testOmittedEnumKeysFallBackToTheirDefault(string $type, string $key, string $expected): void {
		$result = $this->sanitizer->sanitizeWidget(['type' => $type]);

		$this->assertArrayHasKey($key, $result, "$type should still define '$key'");
		$this->assertNotNull($result[$key], "$type: omitted '$key' must not store null");
		$this->assertSame($expected, $result[$key]);
	}

	public static function defaultingKeys(): array {
		return [
			'news layout'       => ['news', 'layout', 'list'],
			'news sortBy'       => ['news', 'sortBy', 'modified'],
			'news sortOrder'    => ['news', 'sortOrder', 'desc'],
			'people layout'     => ['people', 'layout', 'card'],
			'people sortBy'     => ['people', 'sortBy', 'displayName'],
			'people sortOrder'  => ['people', 'sortOrder', 'asc'],
			'people selection'  => ['people', 'selectionMode', 'manual'],
			'calendar range'    => ['calendar', 'dateRange', 'upcoming'],
			'feed sourceType'   => ['feed', 'sourceType', 'rss'],
			'feed layout'       => ['feed', 'layout', 'list'],
		];
	}

	/** Same defect, one level down, in the story widgets' config array. */
	public function testOmittedStoryConfigKeysFallBackToTheirDefault(): void {
		$photo = $this->sanitizer->sanitizeWidget(['type' => 'photo-story', 'config' => []]);
		$this->assertNotNull($photo['config']['mode']);
		$this->assertSame('timeline', $photo['config']['mode']);
		$this->assertSame('apple', $photo['config']['style']);

		$file = $this->sanitizer->sanitizeWidget(['type' => 'file-story', 'config' => []]);
		$this->assertNotNull($file['config']['mode']);
		$this->assertSame('timeline', $file['config']['mode']);
	}

	// ---------- malformed input must not fatal ----------

	/**
	 * Import and the API both reach this method with attacker-shaped data, so
	 * a wrong scalar type has to be survivable rather than a 500.
	 *
	 * @dataProvider widgetTypes
	 */
	public function testScalarsWhereArraysAreExpectedDoNotFatal(string $type): void {
		$result = $this->sanitizer->sanitizeWidget([
			'type' => $type,
			'items' => 'not-an-array',
			'filters' => 'not-an-array',
			'config' => 'not-an-array',
			'selectedUsers' => 'not-an-array',
			'calendarIds' => 'not-an-array',
			'externalIcsUrls' => 'not-an-array',
		]);

		$this->assertIsArray($result, "$type must survive scalars in array slots");
		$this->assertSame($type, $result['type']);
	}
}
