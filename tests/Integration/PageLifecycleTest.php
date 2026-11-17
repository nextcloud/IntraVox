<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Integration;

/**
 * The real page write path against the disposable IntraVox fixture.
 * The runner provisions a known writer; never discover or impersonate owners.
 */
class PageLifecycleTest extends IntegrationTestCase {

    /** Pages created by this test, removed in tearDown even on failure. */
    private array $createdPageIds = [];

    private string $actingUser = '';

    protected function setUp(): void {
        parent::setUp();

        $this->actingUser = 'integration-admin';
    }

    protected function tearDown(): void {
        foreach ($this->createdPageIds as $id) {
            try {
                $this->actingAs($this->actingUser, function () use ($id) {
                    $this->pageWriteService()->deletePage($id);
                });
            } catch (\Throwable $e) {
                // Already gone, or never created.
            }
        }
        $this->createdPageIds = [];
        parent::tearDown();
    }

    private function uniqueSlug(): string {
        return 'itest-' . bin2hex(random_bytes(5));
    }

    public function testCreateReadUpdateDelete(): void {
        $slug = $this->uniqueSlug();

        $created = $this->actingAs($this->actingUser, function () use ($slug) {
            return $this->pageWriteService()->createPage([
                'id' => $slug,
                'title' => 'Integration test page',
                'layout' => ['rows' => [['widgets' => [
                    ['type' => 'text', 'content' => '<p>first</p>', 'column' => 1, 'order' => 1],
                ]]]],
            ], null);
        });

        $uniqueId = $created['uniqueId'] ?? null;
        $this->assertNotNull($uniqueId, 'a created page must have a uniqueId');
        $this->createdPageIds[] = $uniqueId;

        // READ
        $read = $this->actingAs($this->actingUser, fn() => $this->pageReadService()->getPage($uniqueId));
        $this->assertSame('Integration test page', $read['title']);
        $this->assertSame(
            '<p>first</p>',
            $read['layout']['rows'][0]['widgets'][0]['content'],
            'widget content must survive the sanitizer and the round trip to disk'
        );

        // UPDATE
        $this->actingAs($this->actingUser, function () use ($uniqueId, $read) {
            $data = $read;
            $data['title'] = 'Integration test page (edited)';
            $data['layout']['rows'][0]['widgets'][0]['content'] = '<p>second</p>';
            return $this->pageWriteService()->updatePage($uniqueId, $data);
        });

        $reread = $this->actingAs($this->actingUser, fn() => $this->pageReadService()->getPage($uniqueId));
        $this->assertSame('Integration test page (edited)', $reread['title']);
        $this->assertSame('<p>second</p>', $reread['layout']['rows'][0]['widgets'][0]['content']);

        // DELETE
        $this->actingAs($this->actingUser, fn() => $this->pageWriteService()->deletePage($uniqueId));
        $this->createdPageIds = array_diff($this->createdPageIds, [$uniqueId]);

        $this->expectException(\Throwable::class);
        $this->actingAs($this->actingUser, fn() => $this->pageReadService()->getPage($uniqueId));
    }

    /**
     * The sanitizer runs on the real save path. A script tag must not survive
     * to disk — this is the assertion that makes PR-20's extraction honest,
     * because it checks the sanitizer through the same route a user takes.
     */
    public function testScriptTagsDoNotSurviveTheSavePath(): void {
        $slug = $this->uniqueSlug();

        $created = $this->actingAs($this->actingUser, function () use ($slug) {
            return $this->pageWriteService()->createPage([
                'id' => $slug,
                'title' => 'Sanitizer probe',
                'layout' => ['rows' => [['widgets' => [
                    ['type' => 'text', 'content' => '<p>ok</p><script>alert(1)</script>', 'column' => 1, 'order' => 1],
                ]]]],
            ], null);
        });
        $uniqueId = $created['uniqueId'];
        $this->createdPageIds[] = $uniqueId;

        $read = $this->actingAs($this->actingUser, fn() => $this->pageReadService()->getPage($uniqueId));
        $content = $read['layout']['rows'][0]['widgets'][0]['content'];

        $this->assertStringNotContainsString('<script', $content, 'script tags must never reach disk');
        $this->assertStringContainsString('ok', $content, 'legitimate content must survive');
    }

    /**
     * listPages() is the walker that must not serve templates or resource
     * library files as pages — the bug that once put a template above the real
     * page in search results.
     */
    public function testListPagesNeverServesTemplatesOrResources(): void {
        $pages = $this->actingAs($this->actingUser, fn() => $this->pageLister()->listAll());

        $this->assertNotEmpty($pages, 'the instance must have pages for this to mean anything');
        $this->assertContains('Disposable release fixture', array_column($pages, 'title'));
        $this->assertNotContains('excluded-_templates', array_column($pages, 'uniqueId'));
        $this->assertNotContains('excluded-_resources', array_column($pages, 'uniqueId'));

        foreach ($pages as $page) {
            $path = $page['path'] ?? '';
            $this->assertStringNotContainsString('_templates', $path, 'templates are not pages');
            $this->assertStringNotContainsString('_resources', $path, 'resource library files are not pages');
        }
    }
}
