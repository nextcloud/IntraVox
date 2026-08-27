<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\PublicShare;

use OCA\IntraVox\Service\SetupService;
use OCP\Files\File;

/**
 * Builds the breadcrumb an anonymous share visitor sees, extracted verbatim
 * from PublicShareController (controller split, PR-B).
 *
 * It was 232 lines of filesystem walking, JSON parsing and three-layer
 * fallback sitting in an HTTP controller — the single largest block in either
 * controller, and the clearest case of logic that is not about HTTP at all.
 *
 * Why it cannot reuse PageService::getBreadcrumb: that one answers "where is
 * this page in the site", starting at the language root. A share answers
 * "where is this page inside what was shared", and the visitor must never be
 * told about ancestors above the share scope — not even their names. So the
 * walk starts at the scope root and the language folder is skipped.
 *
 * Contract carried over unchanged:
 *   - Paths are Unicode-normalised (NFC) before comparison, when intl is
 *     available. Without it the comparison still runs, just unnormalised.
 *   - Every filesystem read is individually wrapped: a missing or corrupt
 *     navigation.json, home.json or page JSON degrades to a folder-name
 *     breadcrumb instead of failing the request.
 *   - The home entry is resolved in three descending steps — navigation.json's
 *     first item, then home.json, then a scan of the language folder for any
 *     page-*.json. The scan exists because older sites have no home.json.
 *   - The language segment is identified by length (2-3 chars) at index 0,
 *     which is how the rest of IntraVox recognises it too.
 *   - A page that IS the scope root returns a single-entry breadcrumb.
 */
final class ShareBreadcrumbBuilder {
    public function __construct(
        private SetupService $setupService,
    ) {
    }

    /**
     * @param array<string, mixed> $pageData
     * @return array<int, array<string, mixed>>
     */
    public function build(array $pageData, string $shareScopePath, string $language): array {
        $pagePath = $pageData['path'] ?? '';
        if (empty($pagePath)) {
            return [];
        }

        // Normalize paths for Unicode-safe comparison
        if (function_exists('normalizer_normalize')) {
            $pagePath = \Normalizer::normalize($pagePath, \Normalizer::FORM_C) ?: $pagePath;
            $shareScopePath = \Normalizer::normalize($shareScopePath, \Normalizer::FORM_C) ?: $shareScopePath;
        }

        // shareScopePath is relative path like "nl" or "nl/afdeling" or "nl/afdeling/hr"
        $pathParts = explode('/', $pagePath);

        $breadcrumb = [];

        // Get GroupFolder for system-level page lookup
        $groupFolder = null;
        try {
            $groupFolder = $this->setupService->getSharedFolder();
        } catch (\Exception $e) {
            // Fall back to folder names only
        }

        // Determine if this is a language-root share (scope is just the language, e.g. "en")
        $isLanguageRootShare = !str_contains($shareScopePath, '/');

        if ($isLanguageRootShare) {
            // Language-root share: add actual Home page as first breadcrumb item
            $homeUniqueId = null;
            $homeTitle = 'Home';
            if ($groupFolder !== null) {
                // Read home breadcrumb label from navigation.json (first item title)
                try {
                    $navPath = $language . '/navigation.json';
                    if ($groupFolder->nodeExists($navPath)) {
                        $navFile = $groupFolder->get($navPath);
                        $navData = json_decode($navFile->getContent(), true, 64);
                        if ($navData && !empty($navData['items'][0]['title'])) {
                            $homeTitle = $navData['items'][0]['title'];
                        }
                        if ($navData && !empty($navData['items'][0]['uniqueId'])) {
                            $homeUniqueId = $navData['items'][0]['uniqueId'];
                        }
                    }
                } catch (\Exception $e) {
                    // Fall through, Home without uniqueId
                }
                // Get home uniqueId from home.json if not found in navigation
                if ($homeUniqueId === null) {
                    try {
                        $homeJsonPath = $language . '/home.json';
                        if ($groupFolder->nodeExists($homeJsonPath)) {
                            $homeFile = $groupFolder->get($homeJsonPath);
                            $homeData = json_decode($homeFile->getContent(), true, 64);
                            if ($homeData && isset($homeData['uniqueId'])) {
                                $homeUniqueId = $homeData['uniqueId'];
                            }
                        }
                    } catch (\Exception $e) {
                        // Fall through
                    }
                }
                // Fallback: scan language folder for any page-*.json file at root level
                if ($homeUniqueId === null) {
                    try {
                        $langFolder = $groupFolder->get($language);
                        if ($langFolder instanceof \OCP\Files\Folder) {
                            foreach ($langFolder->getDirectoryListing() as $node) {
                                $name = $node->getName();
                                // A File check, not just the name: getDirectoryListing()
                                // hands back Nodes, and a *directory* called
                                // "page-x.json" would make getContent() raise an
                                // \Error — which the catch below, being \Exception,
                                // would not stop. Found by PHPStan once this moved
                                // out of the controller's baseline.
                                if ($node instanceof File
                                    && str_starts_with($name, 'page-') && str_ends_with($name, '.json')) {
                                    $content = $node->getContent();
                                    $data = json_decode($content, true, 64);
                                    if ($data && isset($data['uniqueId'])) {
                                        $homeUniqueId = $data['uniqueId'];
                                        break;
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Fall through
                    }
                }
            }

            // Check if the current page IS the home page
            $isHomePage = ($pagePath === $language || $pagePath === $language . '/home');

            $breadcrumb[] = [
                'uniqueId' => $homeUniqueId,
                'title' => $homeTitle,
                'path' => $language,
                'url' => $isHomePage ? null : ($homeUniqueId ? '#' . $homeUniqueId : null),
                'current' => $isHomePage,
            ];

            if ($isHomePage) {
                return $breadcrumb;
            }
        } else {
            // Subfolder share: add the share scope root page as "Home" breadcrumb
            $scopeRootUniqueId = null;
            $scopeFolderName = basename($shareScopePath);
            $scopeRootTitle = ucfirst(str_replace('-', ' ', $scopeFolderName));
            if ($groupFolder !== null) {
                try {
                    $scopeJsonPath = $shareScopePath . '/' . $scopeFolderName . '.json';
                    if ($groupFolder->nodeExists($scopeJsonPath)) {
                        $scopeFile = $groupFolder->get($scopeJsonPath);
                        $scopeContent = $scopeFile->getContent();
                        $scopeData = json_decode($scopeContent, true, 64);
                        if ($scopeData && isset($scopeData['uniqueId'])) {
                            $scopeRootUniqueId = $scopeData['uniqueId'];
                            $scopeRootTitle = $scopeData['title'] ?? $scopeRootTitle;
                        }
                    }
                } catch (\Exception $e) {
                    // Fall through
                }
            }

            // Check if the current page IS the scope root page
            $isScopeRoot = ($pagePath === $shareScopePath);

            $breadcrumb[] = [
                'uniqueId' => $scopeRootUniqueId,
                'title' => $scopeRootTitle,
                'path' => $shareScopePath,
                'url' => $isScopeRoot ? null : ($scopeRootUniqueId ? '#' . $scopeRootUniqueId : null),
                'current' => $isScopeRoot,
            ];

            if ($isScopeRoot) {
                return $breadcrumb;
            }
        }

        // Walk through page path parts, only include items within the share scope
        $accumulatedPath = '';
        $scopeReached = false;

        foreach ($pathParts as $index => $part) {
            if (!empty($accumulatedPath)) {
                $accumulatedPath .= '/';
            }
            $accumulatedPath .= $part;

            // Skip language folder (already covered by root breadcrumb)
            if ($index === 0 && strlen($part) >= 2 && strlen($part) <= 3) {
                continue;
            }

            // For language-root shares, all items after the language are in scope
            // For subfolder shares, wait until we PASS the share scope (root is already in breadcrumb)
            if (!$isLanguageRootShare) {
                if ($accumulatedPath === $shareScopePath) {
                    // This is the scope root, already added as first breadcrumb — skip
                    continue;
                }
                if (!str_starts_with($accumulatedPath, $shareScopePath . '/')) {
                    // Not yet within share scope
                    continue;
                }
            }

            $isLastItem = ($index === count($pathParts) - 1);

            if ($isLastItem) {
                // Current page
                $breadcrumb[] = [
                    'uniqueId' => $pageData['uniqueId'] ?? null,
                    'title' => $pageData['title'] ?? ucfirst(str_replace('-', ' ', $part)),
                    'path' => $pagePath,
                    'url' => null,
                    'current' => true,
                ];
            } else {
                // Parent — try to find the page JSON in the GroupFolder
                $parentPage = null;
                if ($groupFolder !== null) {
                    try {
                        $jsonPath = $accumulatedPath . '/' . $part . '.json';
                        if ($groupFolder->nodeExists($jsonPath)) {
                            $jsonFile = $groupFolder->get($jsonPath);
                            $content = $jsonFile->getContent();
                            $data = json_decode($content, true, 64);
                            if ($data && isset($data['uniqueId'], $data['title'])) {
                                $parentPage = $data;
                            }
                        }
                    } catch (\Exception $e) {
                        // Fall through to folder name fallback
                    }
                }

                if ($parentPage) {
                    $breadcrumb[] = [
                        'uniqueId' => $parentPage['uniqueId'],
                        'title' => $parentPage['title'],
                        'path' => $accumulatedPath,
                        'url' => '#' . $parentPage['uniqueId'],
                        'current' => false,
                    ];
                } else {
                    $breadcrumb[] = [
                        'uniqueId' => null,
                        'title' => ucfirst(str_replace('-', ' ', $part)),
                        'path' => $accumulatedPath,
                        'url' => null,
                        'current' => false,
                    ];
                }
            }
        }

        return $breadcrumb;
    }
}
