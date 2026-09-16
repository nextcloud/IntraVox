<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Harness;

use OCA\IntraVox\Service\Folder\FolderContext;

/**
 * Facade-free service-double helper (COMMIT 0, fase-10 harness split).
 * doubleOrBuild() mocks a class, or builds a real one over recursively-doubled
 * deps when it is final. Lifted verbatim out of BuildsPageService.
 */
trait BuildsServiceDoubles {
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
                $name = $pType instanceof \ReflectionNamedType ? $pType->getName() : null;
                if ($name === \Closure::class) {
                    // A final service that keeps a \Closure ctor param (e.g. FolderContext's
                    // optional seam closures): take the default, else a no-op closure. These
                    // are never invoked on the paths a doubled leaf is reached through.
                    $args[] = $param->isDefaultValueAvailable()
                        ? $param->getDefaultValue()
                        : ($param->allowsNull() ? null : static function (): void {});
                } elseif ($name !== null && !$pType->isBuiltin()
                    && (class_exists($name) || interface_exists($name))) {
                    $args[] = $this->doubleOrBuild($name);
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    $args[] = null;
                }
            }
            return new $class(...$args);
        }
    }
}
