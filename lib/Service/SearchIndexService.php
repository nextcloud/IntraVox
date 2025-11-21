<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

class SearchIndexService {
    private IDBConnection $db;
    private PageService $pageService;

    public function __construct(
        IDBConnection $db,
        PageService $pageService
    ) {
        $this->db = $db;
        $this->pageService = $pageService;
    }

    /**
     * Index a single page
     */
    public function indexPage(string $uniqueId, string $language = 'en'): bool {
        try {
            // Get page data
            $page = $this->pageService->getPage($uniqueId, $language);
            if (!$page) {
                return false;
            }

            // Extract text content from widgets
            $content = $this->extractContent($page);

            // Check if page already exists in index
            $qb = $this->db->getQueryBuilder();
            $qb->select('id')
                ->from('intravox_search_index')
                ->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($uniqueId)));

            $result = $qb->executeQuery();
            $exists = $result->fetch();
            $result->closeCursor();

            $now = time();

            if ($exists) {
                // Update existing entry
                $qb = $this->db->getQueryBuilder();
                $qb->update('intravox_search_index')
                    ->set('title', $qb->createNamedParameter($page['title'] ?? 'Untitled'))
                    ->set('content', $qb->createNamedParameter($content))
                    ->set('path', $qb->createNamedParameter($page['path'] ?? ''))
                    ->set('modified', $qb->createNamedParameter($page['modified'] ?? $now, IQueryBuilder::PARAM_INT))
                    ->set('indexed_at', $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT))
                    ->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($uniqueId)));
                $qb->executeStatement();
            } else {
                // Insert new entry
                $qb = $this->db->getQueryBuilder();
                $qb->insert('intravox_search_index')
                    ->values([
                        'page_unique_id' => $qb->createNamedParameter($uniqueId),
                        'language' => $qb->createNamedParameter($language),
                        'title' => $qb->createNamedParameter($page['title'] ?? 'Untitled'),
                        'content' => $qb->createNamedParameter($content),
                        'path' => $qb->createNamedParameter($page['path'] ?? ''),
                        'modified' => $qb->createNamedParameter($page['modified'] ?? $now, IQueryBuilder::PARAM_INT),
                        'indexed_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT),
                    ]);
                $qb->executeStatement();
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Index all pages
     */
    public function indexAllPages(string $language = 'en'): array {
        $indexed = 0;
        $errors = 0;

        try {
            $pages = $this->pageService->listPages($language);

            foreach ($pages as $page) {
                if (isset($page['uniqueId'])) {
                    if ($this->indexPage($page['uniqueId'], $language)) {
                        $indexed++;
                    } else {
                        $errors++;
                    }
                }
            }
        } catch (\Exception $e) {
            $errors++;
        }

        return ['indexed' => $indexed, 'errors' => $errors];
    }

    /**
     * Search in index
     */
    public function search(string $query, string $language = 'en', int $limit = 20): array {
        $qb = $this->db->getQueryBuilder();

        // Build search query with LIKE for simplicity
        // For better performance, consider using full-text search features of your DB
        $searchTerm = '%' . $this->db->escapeLikeParameter($query) . '%';

        $qb->select('page_unique_id', 'title', 'path', 'content', 'modified')
            ->from('intravox_search_index')
            ->where($qb->expr()->eq('language', $qb->createNamedParameter($language)))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->iLike('title', $qb->createNamedParameter($searchTerm)),
                    $qb->expr()->iLike('content', $qb->createNamedParameter($searchTerm))
                )
            )
            ->orderBy('modified', 'DESC')
            ->setMaxResults($limit);

        $result = $qb->executeQuery();
        $results = [];

        while ($row = $result->fetch()) {
            // Calculate score based on where match was found
            $score = 0;
            $matches = [];

            if (stripos($row['title'], $query) !== false) {
                $score += 10;
                $matches[] = [
                    'type' => 'title',
                    'text' => $row['title']
                ];
            }

            if (stripos($row['content'], $query) !== false) {
                $score += 3;
                // Extract snippet around match
                $snippet = $this->extractSnippet($row['content'], $query);
                $matches[] = [
                    'type' => 'content',
                    'text' => $snippet
                ];
            }

            $results[] = [
                'uniqueId' => $row['page_unique_id'],
                'title' => $row['title'],
                'path' => $row['path'],
                'score' => $score,
                'matches' => $matches,
                'matchCount' => count($matches)
            ];
        }

        $result->closeCursor();

        // Sort by score
        usort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        return $results;
    }

    /**
     * Remove page from index
     */
    public function removeFromIndex(string $uniqueId): bool {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->delete('intravox_search_index')
                ->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($uniqueId)));
            $qb->executeStatement();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear entire index
     */
    public function clearIndex(): bool {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->delete('intravox_search_index');
            $qb->executeStatement();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract text content from page widgets
     */
    private function extractContent(array $page): string {
        $content = [];

        if (isset($page['layout']['rows'])) {
            foreach ($page['layout']['rows'] as $row) {
                if (isset($row['widgets'])) {
                    foreach ($row['widgets'] as $widget) {
                        // Extract text from different widget types
                        if ($widget['type'] === 'text' && isset($widget['content'])) {
                            $content[] = strip_tags($widget['content']);
                        }
                        if ($widget['type'] === 'heading' && isset($widget['content'])) {
                            $content[] = $widget['content'];
                        }
                        if ($widget['type'] === 'links' && isset($widget['items'])) {
                            foreach ($widget['items'] as $link) {
                                if (isset($link['title'])) {
                                    $content[] = $link['title'];
                                }
                                if (isset($link['text'])) {
                                    $content[] = $link['text'];
                                }
                            }
                        }
                    }
                }
            }
        }

        return implode(' ', $content);
    }

    /**
     * Extract snippet around search term
     */
    private function extractSnippet(string $text, string $query, int $contextLength = 150): string {
        $pos = mb_stripos($text, $query);
        if ($pos === false) {
            return mb_substr($text, 0, $contextLength) . '...';
        }

        // Get context around match
        $start = max(0, $pos - 75);
        $snippet = mb_substr($text, $start, $contextLength);

        // Add ellipsis if needed
        $prefix = $start > 0 ? '...' : '';
        $suffix = (mb_strlen($text) > $start + $contextLength) ? '...' : '';

        return $prefix . trim($snippet) . $suffix;
    }
}
