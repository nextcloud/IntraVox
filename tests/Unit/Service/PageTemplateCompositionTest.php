<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Compose\PageCompositionService;
use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCA\IntraVox\Service\Template\PageTemplateService;
use OCA\IntraVox\Service\Translation\TranslationGroupService;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * The TEMPLATE half of COMPOSE composition — saveAsTemplate (page -> template) and
 * createPageFromTemplate (template -> new draft page). Both were carved into
 * PageCompositionService with ZERO behavioural coverage (only a delegator-arity
 * pin existed); the compose adversarial review flagged that gap. This closes it:
 * pin the observable contract of both directions.
 *
 * Drives PageCompositionService directly with stub getPage/createPage/getTemplate
 * closures (the same style as PageCopyCompositionTest), so the composition is
 * exercised without a PageService subclass. The PageTemplateService engine is
 * mocked — these tests pin the ORCHESTRATION (what data is stamped/stripped, the
 * error-array fallbacks, the media copy), not the engine's folder mechanics.
 */
class PageTemplateCompositionTest extends TestCase {

    use BuildsPageService;

    /** A folder that records putContent() on files created via newFile(). */
    private array $written = [];

    private function makeFolder(string $path, array $children = []): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        $folder->method('newFile')->willReturnCallback(function (string $name) use ($path) {
            $file = $this->createMock(File::class);
            $file->method('getName')->willReturn($name);
            $file->method('putContent')->willReturnCallback(function ($content) use ($path, $name) {
                $this->written[$path . '/' . $name] = $content;
                return true;
            });
            return $file;
        });
        return $folder;
    }

    private function makeService(
        PageTemplateService $templates,
        ?PageMediaService $media = null
    ): PageCompositionService {
        $this->written = [];
        $base = $this->makeFolder('/IntraVox', ['en' => $this->makeFolder('/IntraVox/en')]);
        return new PageCompositionService(
            $templates,
            $this->createMock(TranslationGroupService::class),
            $media ?? $this->createMock(PageMediaService::class),
            $this->doubleOrBuild(HtmlSanitizer::class),
            new PageIdUtils(),
            $this->fakeFolderContext(intraVox: $base, languageFolder: $base->get('en')),
            'tester',
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
    }

    // ------------------------------------------------------------ saveAsTemplate

    public function testSaveAsTemplateStampsTemplateIdentityAndSourcePointer(): void {
        // getPage returns the source page; the engine reserves a template folder;
        // the composition writes the template JSON with template identity.
        $templateFolder = $this->makeFolder('/IntraVox/en/_templates/handbook');
        $templates = $this->createMock(PageTemplateService::class);
        $templates->method('newTemplateFolder')->willReturn([
            'handbook', $templateFolder, $this->makeFolder('/IntraVox/en/_templates/handbook/_media'),
        ]);

        $svc = $this->makeService($templates);
        $getPage = fn(string $id): array => [
            'uniqueId' => 'page-src', 'title' => 'Handbook', 'status' => 'published',
            'path' => 'en/handbook', 'parentPath' => 'en', 'layout' => ['rows' => []],
        ];
        $findPageFolder = fn(string $id): ?Folder => null; // no source _media to copy

        $result = $svc->saveAsTemplate('page-src', 'Handbook Template', 'A description', $getPage, $findPageFolder);

        $this->assertTrue($result['success']);
        $this->assertSame('handbook', $result['templateId']);

        // The written template JSON carries template identity, not the page's.
        $written = json_decode($this->written['/IntraVox/en/_templates/handbook/handbook.json'], true);
        $this->assertTrue($written['isTemplate'], 'a saved template is flagged isTemplate');
        $this->assertStringStartsWith('template-', $written['uniqueId'], 'a template gets a template- uniqueId');
        $this->assertSame('Handbook Template', $written['title']);
        $this->assertSame('A description', $written['description']);
        $this->assertSame('tester', $written['createdBy'], 'createdBy is the acting user');
        $this->assertSame('page-src', $written['sourcePageId'], 'the source page is remembered');
        $this->assertArrayNotHasKey('path', $written, 'page-specific path is stripped');
        $this->assertArrayNotHasKey('parentPath', $written, 'page-specific parentPath is stripped');
    }

    public function testSaveAsTemplateReturnsErrorArrayWhenSourceIsMissing(): void {
        $templates = $this->createMock(PageTemplateService::class);
        $svc = $this->makeService($templates);
        $getPage = fn(string $id): array => [];   // page not found -> falsy

        $result = $svc->saveAsTemplate('page-nope', 'X', null, $getPage, fn(string $id): ?Folder => null);

        $this->assertFalse($result['success']);
        $this->assertSame('Page not found', $result['error']);
    }

    public function testSaveAsTemplateCatchesEngineFailureIntoAnErrorArray(): void {
        // newTemplateFolder throwing must surface as a clean error array, never a
        // fatal — the save is best-effort from the caller's view.
        $templates = $this->createMock(PageTemplateService::class);
        $templates->method('newTemplateFolder')->willThrowException(new \RuntimeException('disk full'));

        $svc = $this->makeService($templates);
        $getPage = fn(string $id): array => ['uniqueId' => 'page-src', 'title' => 'X'];

        $result = $svc->saveAsTemplate('page-src', 'X', null, $getPage, fn(string $id): ?Folder => null);

        $this->assertFalse($result['success']);
        $this->assertSame('disk full', $result['error']);
    }

    // -------------------------------------------------- createPageFromTemplate

    public function testCreateFromTemplateStartsADraftWithFreshIdentityAndStrippedFields(): void {
        $templates = $this->createMock(PageTemplateService::class);
        $templates->method('templatesFolder')->willReturn(null); // no media step

        $svc = $this->makeService($templates);

        $seen = null;
        $getTemplate = fn(string $id): ?array => [
            'uniqueId' => 'template-abc', 'title' => 'Blank', 'isTemplate' => true,
            'description' => 'desc', 'createdBy' => 'someone', 'sourcePageId' => 'page-old',
            'status' => 'published', 'layout' => ['rows' => []],
        ];
        $createPage = function (array $data, ?string $parentPath = null) use (&$seen): array {
            $seen = $data;
            return $data;
        };
        $getPage = fn(string $id): array => $seen ?? [];
        $findPageFolder = fn(string $id): ?Folder => null;

        $result = $svc->createPageFromTemplate('tpl-1', 'My New Page', 'en', $createPage, $getPage, $getTemplate, $findPageFolder);

        $this->assertTrue($result['success']);
        $this->assertNotNull($seen, 'createPage was reached');
        $this->assertSame('draft', $seen['status'], 'a page from a template starts as a draft');
        $this->assertSame('My New Page', $seen['title']);
        $this->assertStringStartsWith('page-', $seen['uniqueId'], 'a fresh page uniqueId');
        // Template-specific fields must not leak into the new page.
        $this->assertArrayNotHasKey('isTemplate', $seen);
        $this->assertArrayNotHasKey('description', $seen);
        $this->assertArrayNotHasKey('createdBy', $seen);
        $this->assertArrayNotHasKey('sourcePageId', $seen);
    }

    public function testCreateFromTemplateReturnsErrorArrayForAnUnknownTemplate(): void {
        $svc = $this->makeService($this->createMock(PageTemplateService::class));

        $result = $svc->createPageFromTemplate(
            'missing',
            'X',
            null,
            fn(array $d, ?string $p = null): array => $d,
            fn(string $id): array => [],
            fn(string $id): ?array => null,   // template not found
            fn(string $id): ?Folder => null
        );

        $this->assertFalse($result['success']);
        $this->assertSame('Template not found', $result['error']);
    }

    public function testCreateFromTemplateFallsBackToCreatedDataWhenGetPageFails(): void {
        // The re-fetch through getPage may fail on a brand-new folder (ACL race);
        // the response must fall back to the created page rather than blow up.
        $templates = $this->createMock(PageTemplateService::class);
        $templates->method('templatesFolder')->willReturn(null);

        $svc = $this->makeService($templates);
        $created = null;
        $createPage = function (array $data, ?string $parentPath = null) use (&$created): array {
            $created = $data;
            return $data;
        };
        $getPage = function (string $id): array {
            throw new \RuntimeException('ACL race on fresh folder');
        };

        $result = $svc->createPageFromTemplate(
            'tpl-1',
            'Page',
            null,
            $createPage,
            $getPage,
            fn(string $id): ?array => ['uniqueId' => 'template-abc', 'title' => 'T', 'layout' => ['rows' => []]],
            fn(string $id): ?Folder => null
        );

        $this->assertTrue($result['success'], 'a failed re-fetch still yields a success with the created data');
        $this->assertSame($created['uniqueId'], $result['page']['uniqueId'], 'falls back to the created page');
    }
}
