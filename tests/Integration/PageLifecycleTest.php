<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Integration;

use OCP\IUserManager;
use OCP\IUserSession;

/**
 * The page write path, end to end, against the real IntraVox groupfolder.
 *
 * Unlike the other classes here this one cannot use a throwaway groupfolder:
 * PageService::getIntraVoxFolder() hardcodes the name 'IntraVox' — that
 * hardcoding IS the single-site assumption the multi-site seam (P2) replaces.
 * So the test does the next safest thing: it creates its own page with a
 * unique id, exercises create → read → update → delete on it, and removes it
 * again. It never modifies a page it did not create.
 *
 * This is the suite's most important class for P2: the seam has to leave every
 * assertion here byte-identical.
 */
class PageLifecycleTest extends IntegrationTestCase {

    /** Pages created by this test, removed in tearDown even on failure. */
    private array $createdPageIds = [];

    private string $actingUser = '';

    protected function setUp(): void {
        parent::setUp();

        $uid = $this->resolveWritingUser();
        if ($uid === null) {
            $this->markTestSkipped(
                'No user with write access to the IntraVox groupfolder on this instance.'
            );
        }
        $this->actingUser = $uid;
    }

    protected function tearDown(): void {
        foreach ($this->createdPageIds as $id) {
            try {
                $this->actingAs($this->actingUser, function () use ($id) {
                    $this->pageService()->deletePage($id);
                });
            } catch (\Throwable $e) {
                // Already gone, or never created.
            }
        }
        $this->createdPageIds = [];
        parent::tearDown();
    }

    /**
     * Find a user who can actually write to IntraVox. Deliberately does not
     * assume a fixed username, so the suite is portable between instances.
     */
    private function resolveWritingUser(): ?string {
        $userManager = self::server()->get(IUserManager::class);
        $candidates = [];
        $userManager->callForSeenUsers(function ($user) use (&$candidates) {
            $candidates[] = $user->getUID();
        });

        foreach ($candidates as $uid) {
            try {
                $writable = $this->actingAs($uid, function () use ($uid) {
                    $folder = $this->rootFolder()->getUserFolder($uid)->get('IntraVox');
                    return $folder->isCreatable();
                });
                if ($writable) {
                    return $uid;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        return null;
    }

    private function uniqueSlug(): string {
        return 'itest-' . bin2hex(random_bytes(5));
    }

    public function testCreateReadUpdateDelete(): void {
        $slug = $this->uniqueSlug();

        $created = $this->actingAs($this->actingUser, function () use ($slug) {
            return $this->pageService()->createPage([
                'id' => $slug,
                'title' => 'Integration test page',
                'layout' => ['rows' => [['widgets' => [
                    ['type' => 'text', 'content' => '<p>first</p>', 'column' => 1, 'order' => 1],
                ]]]],
            ]);
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
            return $this->pageService()->updatePage($uniqueId, $data);
        });

        $reread = $this->actingAs($this->actingUser, fn() => $this->pageReadService()->getPage($uniqueId));
        $this->assertSame('Integration test page (edited)', $reread['title']);
        $this->assertSame('<p>second</p>', $reread['layout']['rows'][0]['widgets'][0]['content']);

        // DELETE
        $this->actingAs($this->actingUser, fn() => $this->pageService()->deletePage($uniqueId));
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
            return $this->pageService()->createPage([
                'id' => $slug,
                'title' => 'Sanitizer probe',
                'layout' => ['rows' => [['widgets' => [
                    ['type' => 'text', 'content' => '<p>ok</p><script>alert(1)</script>', 'column' => 1, 'order' => 1],
                ]]]],
            ]);
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
        $pages = $this->actingAs($this->actingUser, fn() => $this->pageService()->listPages());

        $this->assertNotEmpty($pages, 'the instance must have pages for this to mean anything');

        foreach ($pages as $page) {
            $path = $page['path'] ?? '';
            $this->assertStringNotContainsString('_templates', $path, 'templates are not pages');
            $this->assertStringNotContainsString('_resources', $path, 'resource library files are not pages');
        }
    }
}
