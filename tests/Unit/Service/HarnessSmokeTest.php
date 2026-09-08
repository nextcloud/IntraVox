<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Proves the shared BuildsPageService harness behaves the way the ten existing
 * PageService test files expect, so they can migrate onto it without changing
 * what they assert. This is a test of the test infrastructure, not of PageService
 * behaviour.
 */
class HarnessSmokeTest extends TestCase {

    use BuildsPageService;

    /** Written by makeFolder()'s move() callback. */
    private array $moves = [];

    protected function setUp(): void {
        $this->moves = [];
    }

    public function testMakeFileExposesJsonContentAndStableId(): void {
        $file = $this->makeFile('/IntraVox/en/about.json', ['uniqueId' => 'p1', 'title' => 'About']);

        $this->assertSame('about.json', $file->getName());
        $this->assertSame(FileInfo::TYPE_FILE, $file->getType());
        $this->assertSame('/IntraVox/en/about.json', $file->getPath());
        $this->assertSame(['uniqueId' => 'p1', 'title' => 'About'], json_decode($file->getContent(), true));
        $this->assertSame(abs(crc32('/IntraVox/en/about.json')), $file->getId());
    }

    public function testMakeFolderResolvesChildrenAndThrowsForMissing(): void {
        $child = $this->makeFile('/IntraVox/en/about.json', ['uniqueId' => 'p1']);
        $folder = $this->makeFolder('/IntraVox/en', ['about.json' => $child]);

        $this->assertSame(FileInfo::TYPE_FOLDER, $folder->getType());
        $this->assertTrue($folder->nodeExists('about.json'));
        $this->assertFalse($folder->nodeExists('missing.json'));
        $this->assertSame($child, $folder->get('about.json'));
        $this->assertSame([$child], $folder->getDirectoryListing());

        $this->expectException(NotFoundException::class);
        $folder->get('missing.json');
    }

    public function testMakeFolderPermissionFlagsAreControllable(): void {
        $ro = $this->makeFolder('/IntraVox/en', [], deletable: false, creatable: false);
        $this->assertFalse($ro->isDeletable());
        $this->assertFalse($ro->isCreatable());
        $this->assertTrue($ro->isUpdateable());
    }

    public function testMakeFolderRecordsMovesIntoHostMovesProperty(): void {
        $folder = $this->makeFolder('/IntraVox/en/about', []);
        $folder->move('/IntraVox/en/news/about');
        $this->assertSame(['/IntraVox/en/about' => '/IntraVox/en/news/about'], $this->moves);
    }

    public function testDoubleOrBuildBuildsFinalLeafSanitizers(): void {
        // PageShapeSanitizer is final and takes three final leaf sanitizers;
        // doubleOrBuild must construct it rather than fail to mock it.
        $shape = $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class);
        $this->assertInstanceOf(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class, $shape);
    }

    public function testInjectFillsCollaboratorsButLeavesLazySeamServicesUnset(): void {
        $svc = $this->makeSeamOverridingService();

        $explicit = [
            'logger' => $this->createMock(LoggerInterface::class),
            'userId' => 'tester',
        ];
        $this->injectPageServiceDependencies($svc, $explicit);

        $ref = new \ReflectionClass(PageService::class);

        // Explicitly-set property survives.
        $this->assertSame('tester', $ref->getProperty('userId')->getValue($svc));

        // A lazy-seam service property is left UNSET (uninitialized), so the lazy
        // accessor will build the real one on demand.
        $locatorProp = $this->findPropertyOfType($ref, PageLocator::class);
        $this->assertNotNull($locatorProp, 'PageService should have a PageLocator-typed property');
        $this->assertFalse(
            $locatorProp->isInitialized($svc),
            'lazy-seam service must stay unset so the accessor builds the real collaborator'
        );

        // A non-lazy collaborator property is filled with a double.
        $permProp = $this->findPropertyOfType($ref, \OCA\IntraVox\Service\PermissionService::class);
        $this->assertNotNull($permProp);
        $this->assertTrue(
            $permProp->isInitialized($svc),
            'non-lazy collaborators must be auto-filled'
        );
    }

    public function testLazySeamListMatchesFourKnownServices(): void {
        // Pins the current membership so a change to the extraction plan is a
        // visible, deliberate edit to this list.
        $this->assertSame(
            [
                PageLocator::class,
                \OCA\IntraVox\Service\Translation\TranslationGroupService::class,
                \OCA\IntraVox\Service\Media\PageMediaService::class,
                \OCA\IntraVox\Service\News\NewsPageService::class,
            ],
            self::LAZY_SEAM_SERVICES
        );
    }

    private function makeSeamOverridingService(): PageService {
        // A bare constructorless PageService — this smoke test only exercises the
        // harness auto-fill mechanics, never a folder path, so no seam/context is
        // wired.
        return new class extends PageService {
            public function __construct() {
            }
        };
    }

    private function findPropertyOfType(\ReflectionClass $ref, string $class): ?\ReflectionProperty {
        foreach ($ref->getProperties() as $prop) {
            $type = $prop->getType();
            if ($type instanceof \ReflectionNamedType && $type->getName() === $class) {
                return $prop;
            }
        }
        return null;
    }
}
