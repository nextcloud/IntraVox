<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Path;

use OCA\IntraVox\Service\LanguageService;
use OCP\Files\Folder;

/**
 * Build the breadcrumb trail for a page (cluster J, Phase 5). Extracted verbatim
 * from PageService::getBreadcrumb; the data it used to fetch itself (the page, the
 * language, the homepage flag, the navigation folder, the parent-page lookup) is
 * now passed in, so this class is pure presentation logic. PageService keeps a
 * thin getBreadcrumb() facade that gathers those inputs via its own seams and
 * delegates here.
 */
class BreadcrumbBuilder {

    public function __construct(
        private LanguageService $languageService,
    ) {
    }

    /**
     * @param string $pageId            the requested id (used for the 'home' shortcut)
     * @param array<string,mixed> $page the page as returned by getPage()
     * @param string $language          the viewer's language code
     * @param bool $isHomepagePointer   whether a configured homepage pointer names this page
     * @param Folder|null $readFolder   folder to read navigation.json from (null = none)
     * @param callable(string):?array $findParent  resolve a parent page by its folder path
     * @return list<array<string,mixed>>
     */
    public function build(
        string $pageId,
        array $page,
        string $language,
        bool $isHomepagePointer,
        ?Folder $readFolder,
        callable $findParent
    ): array {
        $breadcrumb = [];

        // Check if current page is the home page (legacy id/path detection, plus
        // a configured homepage pointer via uniqueId).
        $isHomePage = ($pageId === 'home' ||
                       preg_match('/^[a-z]{2,3}\/home$/', $page['path']) ||
                       preg_match('/^[a-z]{2,3}$/', $page['path']) ||
                       (!empty($page['uniqueId']) && $isHomepagePointer));

        // Read home breadcrumb label from navigation.json (first item title)
        // This allows users to customize the label via the navigation editor
        $homeTitle = 'Home';
        $homeUniqueId = $isHomePage ? $page['uniqueId'] : null;
        try {
            $folder = $readFolder;
            if ($folder !== null && $folder->nodeExists('navigation.json')) {
                $navFile = $folder->get('navigation.json');
                $navData = $navFile instanceof \OCP\Files\File
                    ? json_decode($navFile->getContent(), true, 64)
                    : null;
                if ($navData && !empty($navData['items'][0]['title'])) {
                    $homeTitle = $navData['items'][0]['title'];
                }
                if (!$isHomePage && $navData && !empty($navData['items'][0]['uniqueId'])) {
                    $homeUniqueId = $navData['items'][0]['uniqueId'];
                }
            }
        } catch (\Exception $e) {
            // fallback to 'Home'
        }

        // Always start with Home
        $breadcrumb[] = [
            'id' => 'home',
            'uniqueId' => $homeUniqueId,
            'title' => $homeTitle,
            'path' => $language . '/home',
            'url' => $isHomePage ? null : '#home',
            'current' => $isHomePage
        ];

        // If this is the home page, we're done - don't add duplicate
        if ($isHomePage) {
            return $breadcrumb;
        }

        // Build breadcrumb from the full path
        // Example path: en/departments/marketing/campaigns
        $pathParts = explode('/', $page['path']);
        $accumulatedPath = '';

        foreach ($pathParts as $index => $part) {
            // Build accumulated path for looking up parent pages
            if (!empty($accumulatedPath)) {
                $accumulatedPath .= '/';
            }
            $accumulatedPath .= $part;

            // Skip language folder in breadcrumb display (but include in accumulated path)
            if ($index === 0 && $this->languageService->isLanguageAvailable($part)) {
                continue;
            }

            // Skip 'home' as it's already added
            if ($part === 'home') {
                continue;
            }

            // Check if this is the last item (current page)
            if ($index === count($pathParts) - 1) {
                // Add current page (not clickable)
                $breadcrumb[] = [
                    'uniqueId' => $page['uniqueId'],
                    'title' => $page['title'],
                    'path' => $page['path'],
                    'url' => null,
                    'current' => true
                ];
                break;
            }

            // Try to find parent page by its folder path
            try {
                $parentPage = $findParent($accumulatedPath);
                if ($parentPage) {
                    $breadcrumb[] = [
                        'id' => $part,
                        'uniqueId' => $parentPage['uniqueId'],
                        'title' => $parentPage['title'],
                        'path' => $parentPage['path'],
                        'url' => '#' . $parentPage['uniqueId'],
                        'current' => false
                    ];
                } else {
                    // No page found for this folder - use folder name as label but don't make clickable
                    $breadcrumb[] = [
                        'id' => $part,
                        'uniqueId' => null,
                        'title' => ucfirst(str_replace('-', ' ', $part)),
                        'path' => $accumulatedPath,
                        'url' => null,
                        'current' => false
                    ];
                }
            } catch (\Exception $e) {
                // Parent page not found or error loading it
                // Use folder name as fallback
                $breadcrumb[] = [
                    'id' => $part,
                    'uniqueId' => null,
                    'title' => ucfirst(str_replace('-', ' ', $part)),
                    'path' => $accumulatedPath,
                    'url' => null,
                    'current' => false
                ];
            }
        }

        return $breadcrumb;
    }
}
