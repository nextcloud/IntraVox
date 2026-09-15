<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Metadata;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Metadata\PageMetadataService;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Service\Version\PageVersionService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsCacheFixtures;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsCollaboratorFixtures;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsFolderFixtures;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsNodeFixtures;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsServiceDoubles;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes getPageMetadata() on the METADATA domain service (fase-10),
 * built directly (no facade). Pins the load-bearing quirks the plan flagged: the
 * creation-time fallback (ctime 0 -> mtime, for storages that do not report
 * creation time), the exact "Page not found: <id>" error text, and the core
 * response shape used by the metadata/rename UI.
 *
 * Page resolution is rigged through the injected PageLocator (issue #90's
 * self-sourced cross-language locate) via a per-test resolve closure, exactly as
 * PageContentApiControllerTest builds this service. The #70 canWrite gate rides
 * in through the real PageDataEnricher over inert collaborators.
 */
class PageMetadataServiceTest extends TestCase {

    use BuildsNodeFixtures;
    use BuildsFolderFixtures;
    use BuildsCacheFixtures;
    use BuildsServiceDoubles;
    use BuildsCollaboratorFixtures;

    /**
     * The locator's per-id resolution a test installs. Returns
     * ['file'=>File,'folder'=>Folder] for a hit, or null for "page not found".
     * Read at call time so a test can flip it.
     */
    private \Closure $resolveFn;

    protected function setUp(): void {
        $this->resolveFn = fn(string $id) => null;
    }

    /**
     * Build the METADATA domain service directly, with the PageLocator rigged to
     * resolve page ids through $this->resolveFn (default: not found). The
     * FolderContext resolves languageFolder()/intraVox() so getPageMetadata reaches
     * the mocked locator; the resolved page comes from that locator, not from
     * walking these folders. The real PageDataEnricher (over inert collaborators)
     * derives path/parent/language from the resolved folder's path (#70 gate).
     */
    private function buildService(): PageMetadataService {
        $locator = $this->createMock(PageLocator::class);
        $locator->method('locatePageAnyLanguage')
            ->willReturnCallback(fn($root, $folder, string $id) => ($this->resolveFn)($id));
        $locator->method('findPageById')
            ->willReturnCallback(fn($folder, string $id) => ($this->resolveFn)($id));

        // languageFolder()/intraVox() must resolve (not throw) so getPageMetadata
        // reaches the mocked locator; the resolved page comes from the locator, not
        // from walking these folders. The SAME FolderContext feeds the enricher so
        // relativePathFromRoot() measures the resolved page folder against the same
        // /IntraVox root — that is what makes enrichWithPathData derive en/about.
        $folders = $this->fakeFolderContext(
            languageFolder: $this->makeFolder('/IntraVox/en'),
            intraVox: $this->makeFolder('/IntraVox')
        );

        // The real PageDataEnricher over the shared FolderContext and inert
        // collaborators: permissionsForPage() (mock) returns null so getPageMetadata
        // falls back to its default permissions block (#70 canWrite gate default
        // false); isMetaVoxAvailable() (mock) is false so the translations/groupfolder
        // fan-out never fires. relativePathFromRoot() derives the path/parent/language.
        $enricher = new \OCA\IntraVox\Service\Path\PageDataEnricher(
            new \OCA\IntraVox\Service\Path\PagePathHelper(),
            $this->createMock(\OCA\IntraVox\Service\PermissionService::class),
            $this->createMock(\OCA\IntraVox\Service\Publication\MetaVoxGateway::class),
            $folders,
            $this->createMock(\OCA\IntraVox\Service\Translation\TranslationGroupService::class),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Util\GroupfolderResolver::class),
        );

        return new PageMetadataService(
            new PageIdUtils(),
            $this->createMock(PageVersionService::class),
            $this->createMock(PageIndexService::class),
            $this->doubleOrBuild(\OCA\IntraVox\Service\NavigationService::class),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class),
            $folders,
            $enricher,
            $this->createMock(LoggerInterface::class),
            $this->fakeHomepageResolver(null),
            $this->fakeCacheInvalidator(),
            $locator,
        );
    }

    /**
     * A page file 'about' the locator resolves to (index misses, so a real facade
     * would have walked the primary language folder — here the locator resolves it
     * directly, byte-identical result).
     *
     * @param int $ctime creation time the file reports (0 = unsupported)
     */
    private function makeService(int $ctime, int $mtime = 1_700_000_000): PageMetadataService {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('about.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/about/about.json');
        $file->method('getId')->willReturn(4242);
        $file->method('getMTime')->willReturn($mtime);
        $file->method('getCreationTime')->willReturn($ctime);
        $file->method('getSize')->willReturn(512);
        $file->method('getInternalPath')->willReturn('__groupfolders/1/files/IntraVox/en/about/about.json');
        $file->method('getContent')->willReturn(json_encode([
            'uniqueId' => 'page-about',
            'title' => 'About',
            'language' => 'en',
        ]));

        // beside layout: about.json sits beside the about/ folder in en/. The page's
        // folder is /IntraVox/en/about, so enrichWithPathData derives path 'en/about'
        // (root-relative), parentPath/parentId 'en', language 'en'.
        $pageFolder = $this->makeFolder('/IntraVox/en/about', ['about.json' => $file]);

        // Rig the locator to resolve page-about to this file+folder (issue #90
        // self-sourced locate). enrichWithPathData resolves the relative path via the
        // root, which the FolderContext supplies.
        $this->resolveFn = fn(string $id) => $id === 'page-about'
            ? ['file' => $file, 'folder' => $pageFolder]
            : null;

        return $this->buildService();
    }

    public function testUnknownPageThrowsPageNotFoundWithTheIdInTheMessage(): void {
        // Default resolveFn returns null for every id, so the locate + legacy
        // fallback both miss and getPageMetadata throws with the id in the message.
        $svc = $this->buildService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Page not found: page-nope');
        $svc->getPageMetadata('page-nope');
    }

    public function testCreationTimeZeroFallsBackToModifiedTime(): void {
        $mtime = 1_700_000_500;
        $svc = $this->makeService(ctime: 0, mtime: $mtime);

        $meta = $svc->getPageMetadata('page-about');

        $this->assertSame($mtime, $meta['created'], 'ctime 0 falls back to mtime');
        $this->assertSame($mtime, $meta['modified']);
        $this->assertSame(date('Y-m-d H:i:s', $mtime), $meta['createdFormatted']);
    }

    public function testRealCreationTimeIsKeptWhenReported(): void {
        $svc = $this->makeService(ctime: 1_600_000_000, mtime: 1_700_000_000);

        $meta = $svc->getPageMetadata('page-about');

        $this->assertSame(1_600_000_000, $meta['created'], 'a real ctime is preserved');
        $this->assertSame(1_700_000_000, $meta['modified']);
    }

    public function testCoreResponseShape(): void {
        $svc = $this->makeService(ctime: 1_600_000_000);

        $meta = $svc->getPageMetadata('page-about');

        $this->assertSame('About', $meta['title']);
        $this->assertSame('page-about', $meta['uniqueId']);
        $this->assertSame(4242, $meta['fileId']);
        $this->assertSame(512, $meta['size']);
        $this->assertSame('IntraVox', $meta['mountPoint']);
        $this->assertSame('/IntraVox/en/about/about.json', $meta['path']);
        foreach (['created', 'modified', 'createdFormatted', 'modifiedFormatted',
                  'createdRelative', 'modifiedRelative', 'permissions', 'canEdit'] as $key) {
            $this->assertArrayHasKey($key, $meta, "metadata must expose '$key'");
        }
    }

    /**
     * Pins the enrichWithPathData step specifically: the metadata below is derived
     * from the FOLDER STRUCTURE, not copied from the page JSON (which carries no
     * depth/parentId/parentPath). If the enrichment were skipped these would be the
     * defaults (depth 0, null parents), so this catches an extraction that drops
     * enrichWithPathData — which the plain shape test above cannot.
     */
    public function testDerivedPathMetadataProvesEnrichmentRan(): void {
        // The page lives at en/about. enrichWithPathData resolves that relative
        // path and derives the parent from its segments: parentPath 'en',
        // parentId 'en'. The page JSON carries none of these, so a non-null parent
        // is only produced when the enrichment actually ran — this catches an
        // extraction that drops enrichWithPathData, which the shape test cannot.
        $svc = $this->makeService(ctime: 1_600_000_000);

        $meta = $svc->getPageMetadata('page-about');

        $this->assertSame('en', $meta['parentPath'], 'parentPath is derived from the folder path by enrichment');
        $this->assertSame('en', $meta['parentId'], 'parentId is the parent folder segment');
        $this->assertSame('en', $meta['language'], 'language is the first path segment');
    }
}
