<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Template;

use OCA\IntraVox\Service\Template\PageTemplateService;
use OCA\IntraVox\Service\Template\TemplateMetadataExtractor;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * PageTemplateService (service split, PR-14): template storage/lookup on a
 * caller-supplied language folder. The API result shapes (success/error
 * arrays, [] and null degradations) are frontend contract — pinned here.
 */
class PageTemplateServiceTest extends TestCase {

    private function svc(): PageTemplateService {
        return new PageTemplateService(
            new TemplateMetadataExtractor(),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
    }

    private function makeFile(string $name, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn($name);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getMTime')->willReturn(1_700_000_000);
        return $file;
    }

    /** @param array<string, \OCP\Files\Node> $children */
    private function makeFolder(string $name, array $children): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn($name);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('get')->willReturnCallback(function ($p) use ($children) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new \OCP\Files\NotFoundException($p);
        });
        return $folder;
    }

    private function makeTemplate(string $id, string $title): Folder {
        return $this->makeFolder($id, [
            $id . '.json' => $this->makeFile($id . '.json', [
                'uniqueId' => 'template-' . $id,
                'title' => $title,
                'widgets' => [],
            ]),
        ]);
    }

    public function testNoTemplatesFolderMeansEmptyListAndNullGet(): void {
        $lang = $this->makeFolder('en', []);

        $this->assertNull($this->svc()->templatesFolder($lang));
        $this->assertSame([], $this->svc()->listTemplates($lang));
        $this->assertNull($this->svc()->getTemplate($lang, 'anything'));
    }

    public function testListSkipsSpecialFoldersAndSortsByTitle(): void {
        $templates = $this->makeFolder('_templates', [
            'zebra' => $this->makeTemplate('zebra', 'Zebra layout'),
            'onboarding' => $this->makeTemplate('onboarding', 'Aboard!'),
            '_media' => $this->makeFolder('_media', []),
            '.hidden' => $this->makeFolder('.hidden', []),
            'broken' => $this->makeFolder('broken', []),   // folder without JSON
        ]);
        $lang = $this->makeFolder('en', ['_templates' => $templates]);

        $out = $this->svc()->listTemplates($lang);

        $this->assertCount(2, $out, 'special folders and JSON-less folders are skipped');
        $this->assertSame('Aboard!', $out[0]['title'], 'sorted by title, case-insensitive');
        $this->assertSame('zebra', $out[1]['id']);
        $this->assertArrayHasKey('preview', $out[0]);
    }

    public function testGetTemplateReturnsFullContent(): void {
        $lang = $this->makeFolder('en', [
            '_templates' => $this->makeFolder('_templates', [
                'kb' => $this->makeTemplate('kb', 'Knowledge Base'),
            ]),
        ]);

        $content = $this->svc()->getTemplate($lang, 'kb');

        $this->assertSame('Knowledge Base', $content['title']);
        $this->assertNull($this->svc()->getTemplate($lang, 'nope'));
    }

    public function testDeleteTemplateResultShapes(): void {
        $doomed = $this->makeTemplate('doomed', 'Doomed');
        $doomed->expects($this->once())->method('delete');
        $lang = $this->makeFolder('en', [
            '_templates' => $this->makeFolder('_templates', ['doomed' => $doomed]),
        ]);

        $this->assertSame(['success' => true], $this->svc()->deleteTemplate($lang, 'doomed'));
        $this->assertSame(
            ['success' => false, 'error' => 'Template not found'],
            $this->svc()->deleteTemplate($lang, 'ghost')
        );
        $this->assertSame(
            ['success' => false, 'error' => 'Templates folder not accessible'],
            $this->svc()->deleteTemplate($this->makeFolder('en', []), 'doomed')
        );
    }

    /**
     * IV-09: a traversal id like '.' (or '..', '', or one containing '/') must be
     * refused before it reaches the filesystem — '.' resolved to the _templates
     * folder itself and DELETE wiped every template in the language.
     */
    public function testDeleteTemplateRefusesTraversalIds(): void {
        // The _templates folder must NEVER be deleted; fail the test if it is.
        $templates = $this->makeFolder('_templates', []);
        $templates->expects($this->never())->method('delete');
        $lang = $this->makeFolder('en', ['_templates' => $templates]);

        foreach (['.', '..', '', 'a/b', '../other'] as $bad) {
            $this->assertSame(
                ['success' => false, 'error' => 'Template not found'],
                $this->svc()->deleteTemplate($lang, $bad),
                "traversal id '$bad' must be refused"
            );
        }
    }

    public function testGetTemplateRefusesTraversalIds(): void {
        $lang = $this->makeFolder('en', [
            '_templates' => $this->makeFolder('_templates', []),
        ]);
        foreach (['.', '..', '', 'a/b'] as $bad) {
            $this->assertNull($this->svc()->getTemplate($lang, $bad), "traversal id '$bad' must be refused");
        }
    }

    public function testCanCreateFollowsTemplatesFolderWhenPresent(): void {
        $templates = $this->makeFolder('_templates', []);
        $templates->method('isCreatable')->willReturn(false);
        $lang = $this->makeFolder('en', ['_templates' => $templates]);
        $lang->method('isCreatable')->willReturn(true);

        $this->assertFalse($this->svc()->canCreateTemplates($lang), '_templates folder decides when it exists');

        $langWithout = $this->makeFolder('en', []);
        $langWithout->method('isCreatable')->willReturn(true);
        $this->assertTrue($this->svc()->canCreateTemplates($langWithout), 'language folder decides otherwise');
    }

    public function testCanDeleteTemplateFollowsTheTemplateFolderPermission(): void {
        // Deletable template folder -> allowed.
        $deletable = $this->makeFolder('onboarding', []);
        $deletable->method('isDeletable')->willReturn(true);
        $templates = $this->makeFolder('_templates', ['onboarding' => $deletable]);
        $lang = $this->makeFolder('en', ['_templates' => $templates]);
        $this->assertTrue(
            $this->svc()->canDeleteTemplate($lang, 'onboarding'),
            'delete is allowed when the template folder is deletable'
        );

        // Same template but ACL revoked delete -> refused (the whole point of IV-13).
        $locked = $this->makeFolder('onboarding', []);
        $locked->method('isDeletable')->willReturn(false);
        $templatesLocked = $this->makeFolder('_templates', ['onboarding' => $locked]);
        $langLocked = $this->makeFolder('en', ['_templates' => $templatesLocked]);
        $this->assertFalse(
            $this->svc()->canDeleteTemplate($langLocked, 'onboarding'),
            'delete is refused when the template folder is not deletable'
        );
    }

    public function testCanDeleteTemplateRefusesMissingAndTraversalIds(): void {
        $templates = $this->makeFolder('_templates', []);
        $lang = $this->makeFolder('en', ['_templates' => $templates]);

        $this->assertFalse(
            $this->svc()->canDeleteTemplate($lang, 'does-not-exist'),
            'a template that is not present cannot be deleted'
        );
        foreach (['.', '..', '', 'a/b', '../other'] as $bad) {
            $this->assertFalse(
                $this->svc()->canDeleteTemplate($lang, $bad),
                "traversal id '$bad' must be refused by the delete gate"
            );
        }

        // No _templates folder at all -> refused, never "unset, so allow".
        $langWithout = $this->makeFolder('en', []);
        $this->assertFalse($this->svc()->canDeleteTemplate($langWithout, 'onboarding'));
    }

    public function testNewTemplateFolderSuffixesOnCollision(): void {
        $mediaFolder = $this->makeFolder('_media', []);
        $reserved = $this->makeFolder('onboarding-2', []);
        $reserved->method('newFolder')->with('_media')->willReturn($mediaFolder);

        $templates = $this->makeFolder('_templates', [
            'onboarding' => $this->makeTemplate('onboarding', 'Existing'),
        ]);
        $templates->method('newFolder')->with('onboarding-2')->willReturn($reserved);

        $lang = $this->makeFolder('en', ['_templates' => $templates]);

        [$id, $folder, $media] = $this->svc()->newTemplateFolder($lang, 'onboarding');

        $this->assertSame('onboarding-2', $id);
        $this->assertSame($reserved, $folder);
        $this->assertSame($mediaFolder, $media);
    }

    public function testNewTemplateFolderCreatesTemplatesRootWhenMissing(): void {
        $mediaFolder = $this->makeFolder('_media', []);
        $created = $this->makeFolder('fresh', []);
        $created->method('newFolder')->with('_media')->willReturn($mediaFolder);

        $templates = $this->makeFolder('_templates', []);
        $templates->method('newFolder')->with('fresh')->willReturn($created);

        // Language folder starts WITHOUT _templates; get() serves it only
        // after newFolder('_templates') was called.
        $lang = $this->createMock(Folder::class);
        $childExists = false;
        $lang->method('nodeExists')->willReturnCallback(function ($n) use (&$childExists) {
            return $n === '_templates' && $childExists;
        });
        $lang->expects($this->once())->method('newFolder')->with('_templates')
            ->willReturnCallback(function () use (&$childExists, $templates) {
                $childExists = true;
                return $templates;
            });
        $lang->method('get')->willReturnCallback(function ($p) use (&$childExists, $templates) {
            if ($p === '_templates' && $childExists) {
                return $templates;
            }
            throw new \OCP\Files\NotFoundException($p);
        });

        [$id, , ] = $this->svc()->newTemplateFolder($lang, 'fresh');

        $this->assertSame('fresh', $id);
    }
}
