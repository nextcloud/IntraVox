<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\PublicShare;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;

/**
 * The decoded page JSON inside a share's scope, read at most once per request.
 *
 * PublicShareService::allowedWidgetValues() is the allowlist the public feed and
 * calendar endpoints check a request against, and it answers one key at a time:
 * connectionId, feedUrl, contentType, listId, jiraProject, courseId,
 * moodleForumId. The feed guard therefore asks it six times for every feed in a
 * batch — and each answer used to walk the share tree and JSON-decode every page
 * in it from scratch. Three feed widgets on one page meant eighteen walks.
 *
 * Measured on the page this was found on: 0.72s per feed against a WARM feed
 * cache, scaling linearly to 2.2s for three feeds whose upstream fetches
 * together cost under a second. The time was never the network; it was reading
 * the same pages over and over.
 *
 * The memo is keyed on the share node's file id rather than held in one field,
 * because a request may legitimately touch more than one share and a shared key
 * would let one share's pages answer for another — a widening of access, which
 * is the opposite of what that allowlist is for. It is request-scoped by
 * construction: the service is built per request and nothing here outlives it.
 *
 * @see \OCA\IntraVox\Tests\Unit\Service\PublicShare\ShareWidgetValuesMemoTest
 */
final class SharePageReader {
    /**
     * Decoded pages, keyed by the share node's file id.
     *
     * @var array<int,list<array<string,mixed>>>
     */
    private array $cache = [];

    /**
     * Every page inside the share's scope, decoded.
     *
     * A file that is unreadable or not JSON is skipped rather than failing the
     * lot: the caller's fail-closed behaviour is unchanged, because a page that
     * cannot be read simply publishes nothing.
     *
     * @return list<array<string,mixed>> the decoded page JSON documents
     */
    public function pagesInside(Node $node): array {
        $key = $node->getId();
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $files = $node instanceof Folder ? $this->collectPageFiles($node) : [$node];

        $pages = [];
        foreach ($files as $file) {
            if (!$file instanceof File) {
                continue;
            }
            $page = json_decode((string)$file->getContent(), true);
            if (is_array($page)) {
                $pages[] = $page;
            }
        }

        return $this->cache[$key] = $pages;
    }

    /**
     * The page JSON files inside a shared folder, bounded so a deep tree cannot
     * turn one anonymous request into a full-tree walk.
     *
     * @return list<Node>
     */
    private function collectPageFiles(Folder $folder, int $depth = 0): array {
        if ($depth > 4) {
            return [];
        }

        $files = [];
        foreach ($folder->getDirectoryListing() as $child) {
            if (count($files) >= 200) {
                break;
            }

            if ($child instanceof Folder) {
                if (str_starts_with($child->getName(), '_')) {
                    continue; // _media and friends hold no page JSON
                }
                $files = array_merge($files, $this->collectPageFiles($child, $depth + 1));
                continue;
            }

            if (str_ends_with($child->getName(), '.json')) {
                $files[] = $child;
            }
        }

        return $files;
    }
}
