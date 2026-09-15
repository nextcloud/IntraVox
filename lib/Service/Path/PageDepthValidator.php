<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Path;

use OCA\IntraVox\Service\LanguageService;

/**
 * The maximum-nesting-depth rule, extracted verbatim from PageService so both
 * writers of it — Write/PageWriteService (createPage) and
 * Structure/PageStructureService (movePage) — can enforce it without the rule
 * arriving as a $this-bound closure threaded through the facade.
 *
 * Pure derivation over a path string: no filesystem, no DB. The depth is measured
 * relative to the language root (PagePathHelper::calculateDepth), the ceiling is
 * derived from the path shape (getMaxDepthForPath — currently a flat 5 for every
 * shape, kept as-is so the extraction is behaviour-identical), and exceeding it
 * throws the same InvalidArgumentException the facade did, byte-for-byte.
 */
final class PageDepthValidator {
    public function __construct(
        private PagePathHelper $pathHelper,
        private LanguageService $languageService,
    ) {
    }

    /**
     * Validate that creating a child page at the given path wouldn't exceed max
     * depth. Verbatim from PageService::validateDepth().
     */
    public function validate(string $parentPath): void {
        $currentDepth = $this->pathHelper->calculateDepth($parentPath);
        $maxDepth = $this->getMaxDepthForPath($parentPath);

        if ($currentDepth >= $maxDepth) {
            throw new \InvalidArgumentException(
                "Cannot create child page: maximum nesting depth of {$maxDepth} would be exceeded"
            );
        }
    }

    /**
     * Get maximum allowed depth for a given path. Verbatim from
     * PageService::getMaxDepthForPath().
     */
    private function getMaxDepthForPath(string $path): int {
        // explode() always yields >=1 element, so the old `count($pathParts) > 0`
        // guards were always true (dead) and are dropped — $pathParts[0] always
        // exists. Behaviour is unchanged (every shape caps at 5); the explicit
        // public/departments branches are kept for when the caps diverge again.
        $pathParts = explode('/', trim($path, '/'));

        // Remove language if present. Uses the available (= every NC-known)
        // language set so paths in any language an admin added (e.g. 'da') get
        // correct depth math, not only the ones IntraVox ships a translation for.
        if ($this->languageService->isLanguageAvailable($pathParts[0])) {
            array_shift($pathParts);
        }

        // A language-only path leaves the parts empty after the shift; guard the
        // [0] reads below so they stay in bounds.
        if ($pathParts === []) {
            return 5;
        }

        // Public pages: max depth 5
        if ($pathParts[0] === 'public') {
            return 5;
        }

        // Department pages: max depth 5
        if ($pathParts[0] === 'departments') {
            return 5;
        }

        // Default: max depth 5
        return 5;
    }
}
