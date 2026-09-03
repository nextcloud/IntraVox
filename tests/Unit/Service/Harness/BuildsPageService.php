<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Harness;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\News\NewsPageService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Translation\TranslationGroupService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;

/**
 * Shared harness for the PageService characterization tests.
 *
 * Ten unit test files (and one benchmark) grew byte-identical copies of the same
 * three primitives: makeFile()/makeFolder() fixture builders, doubleOrBuild() for
 * the final leaf sanitizers, and the reflection auto-fill that populates a
 * seam-overriding anonymous PageService subclass while leaving the lazy-seam
 * services unset. This trait is the single home for that pattern so the refactor
 * that decomposes PageService can add a new service by editing ONE list
 * (LAZY_SEAM_SERVICES) instead of nine files.
 *
 * The trait deliberately reproduces the existing behaviour verbatim — it is a
 * consolidation, not a redesign. Consumers still build their own anonymous
 * subclass (each overrides a different set of seams and needs different explicit
 * collaborators); the trait only owns the parts that were identical everywhere.
 *
 * Requires the host to be a PHPUnit\Framework\TestCase (uses createMock()).
 */
trait BuildsPageService {

    /**
     * The PageService properties whose types are lazy-seam services: they must be
     * left UNSET so PageService's own lazy accessors build the real collaborator
     * from pageIndexService + logger, reproducing the pre-split inline behaviour.
     * An auto-mock here would answer null to every lookup and silently break the
     * page-location / translation / media / news paths.
     *
     * As the refactor extracts new lazy-only services, add their class here.
     */
    protected const LAZY_SEAM_SERVICES = [
        PageLocator::class,
        TranslationGroupService::class,
        PageMediaService::class,
        NewsPageService::class,
    ];

    /**
     * A File whose getContent() returns the given page JSON. getId() is a stable
     * hash of the path so fixtures that look a page up by file id are consistent.
     */
    protected function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        return $file;
    }

    /**
     * A Folder that records move() into $this->moves and can be made read-only /
     * non-creatable / non-deletable so permission preflights are testable.
     *
     * The host class must declare `private array $moves = [];` if it asserts on
     * moves; makeFolder() only writes to it when move() is actually called.
     *
     * @param array<string,\OCP\Files\Node> $children name => node
     */
    protected function makeFolder(
        string $path,
        array $children = [],
        bool $deletable = true,
        bool $creatable = true
    ): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('isDeletable')->willReturn($deletable);
        $folder->method('isCreatable')->willReturn($creatable);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->method('nodeExists')->willReturnCallback(
            fn($n) => isset($children[$n])
        );
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new NotFoundException($path . '/' . $p);
        });
        $folder->method('move')->willReturnCallback(function ($dest) use ($path) {
            if (property_exists($this, 'moves')) {
                $this->moves[$path] = $dest;
            }
        });
        return $folder;
    }

    /**
     * Mock $class, or — when it is final and therefore not doubleable — build a
     * real one and recurse for its own final dependencies (PageShapeSanitizer
     * takes three final leaf sanitizers).
     */
    protected function doubleOrBuild(string $class): object {
        try {
            return $this->createMock($class);
        } catch (\PHPUnit\Framework\MockObject\Generator\ClassIsFinalException $e) {
            $ctor = (new \ReflectionClass($class))->getConstructor();
            $args = [];
            foreach ($ctor?->getParameters() ?? [] as $param) {
                $pType = $param->getType();
                $args[] = $pType instanceof \ReflectionNamedType && !$pType->isBuiltin()
                    ? $this->doubleOrBuild($pType->getName())
                    : null;
            }
            return new $class(...$args);
        }
    }

    /**
     * Populate every not-yet-set, non-lazy-seam object property of a
     * seam-overriding PageService subclass with a mock (or built leaf).
     *
     * $explicit are the collaborators the caller has already set by name; they are
     * skipped here. Properties typed as a LAZY_SEAM_SERVICES class are left unset
     * on purpose (see the const docblock).
     *
     * @param array<string,mixed> $explicit property name => already-set value
     */
    protected function fillPageServiceDependencies(PageService $svc, array $explicit): void {
        foreach ((new \ReflectionClass(PageService::class))->getProperties() as $prop) {
            if ($prop->isStatic() || isset($explicit[$prop->getName()])) {
                continue;
            }
            $type = $prop->getType();
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }
            if (in_array($type->getName(), self::LAZY_SEAM_SERVICES, true)) {
                continue;
            }
            if ($prop->isInitialized($svc)) {
                continue;
            }
            $class = $type->getName();
            if (!interface_exists($class) && !class_exists($class)) {
                continue;
            }
            $prop->setValue($svc, $this->doubleOrBuild($class));
        }
    }

    /**
     * Set the given collaborators on the subclass by property name (the "explicit"
     * set every test threads through), then auto-fill the rest.
     *
     * @param array<string,mixed> $explicit property name => value
     */
    protected function injectPageServiceDependencies(PageService $svc, array $explicit): void {
        foreach ($explicit as $name => $value) {
            (new \ReflectionProperty(PageService::class, $name))->setValue($svc, $value);
        }
        $this->fillPageServiceDependencies($svc, $explicit);
    }
}
