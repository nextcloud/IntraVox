<?php
declare(strict_types=1);

namespace OCA\IntraVox\Search;

use OCA\IntraVox\Service\PageService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class PageSearchProvider implements IProvider {
    private PageService $pageService;
    private IL10N $l10n;
    private IURLGenerator $urlGenerator;

    public function __construct(
        PageService $pageService,
        IL10N $l10n,
        IURLGenerator $urlGenerator
    ) {
        $this->pageService = $pageService;
        $this->l10n = $l10n;
        $this->urlGenerator = $urlGenerator;
    }

    public function getId(): string {
        return 'intravox_pages';
    }

    public function getName(): string {
        return $this->l10n->t('IntraVox Pages');
    }

    public function getOrder(string $route, array $routeParameters): int {
        // Show IntraVox results at the top, before Files
        // Files typically has order 0, so we use -10 to appear before it
        if (str_starts_with($route, 'intravox.')) {
            return -10;
        }
        return -5;
    }

    public function search(IUser $user, ISearchQuery $query): SearchResult {
        $term = $query->getTerm();

        // Don't search for very short queries
        if (mb_strlen($term) < 2) {
            return SearchResult::complete(
                $this->l10n->t('IntraVox Pages'),
                []
            );
        }

        try {
            $results = $this->pageService->searchPages($term);
            $entries = [];

            foreach ($results as $result) {
                // Create IntraVox app URL (not Files URL)
                // Build URL manually to ensure it goes to the app, not Files
                $pageIdentifier = $result['uniqueId'] ?? $result['id'];

                // Generate base URL to the IntraVox app with query parameter
                // This ensures Nextcloud closes the search popup on navigation
                $url = $this->urlGenerator->linkToRouteAbsolute(
                    'intravox.page.index',
                    ['page' => $pageIdentifier]
                ) . '#' . $pageIdentifier;

                // Get first match for subline
                $subline = '';
                // Use IntraVox app icon for search results
                // The app-search.svg has neutral gray color (#555) for good contrast on both themes
                $thumbnailUrl = $this->urlGenerator->imagePath('intravox', 'app-search.svg');
                $icon = '';

                if (!empty($result['matches'])) {
                    $firstMatch = $result['matches'][0];

                    // Use match text as subline
                    if ($firstMatch['type'] === 'content' || $firstMatch['type'] === 'heading') {
                        $subline = $this->truncate($firstMatch['text'], 100);
                    } elseif ($firstMatch['type'] === 'title') {
                        $subline = $this->l10n->t('IntraVox Page');
                    }
                }

                $entry = new SearchResultEntry(
                    $thumbnailUrl,
                    $result['title'],
                    $subline,
                    $url,
                    $icon,
                    true // rounded icon
                );

                $entries[] = $entry;
            }

            return SearchResult::complete(
                $this->l10n->t('IntraVox Pages'),
                $entries
            );
        } catch (\Exception $e) {
            return SearchResult::complete(
                $this->l10n->t('IntraVox Pages'),
                []
            );
        }
    }

    private function truncate(string $text, int $length): string {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '...';
    }
}
