<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * Service for generating RSS feeds from IntraVox pages.
 *
 * Uses the token owner's filesystem view (via getUserFolder) to
 * automatically respect GroupFolder ACL permissions.
 */
class FeedService {
    private const GROUPFOLDER_NAME = 'IntraVox';
    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];
    private const DEFAULT_LANGUAGE = 'en';
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 50;
    private const EXCERPT_LENGTH = 300;
    private const SKIP_FOLDERS = ['_media', '_resources', '_templates', 'images', 'files', '.nomedia'];

    public function __construct(
        private IRootFolder $rootFolder,
        private IUserManager $userManager,
        private IURLGenerator $urlGenerator,
        private IConfig $config,
        private LoggerInterface $logger
    ) {}

    /**
     * Generate an RSS feed for a user based on their feed configuration.
     *
     * When scope is 'language', the user's Nextcloud language setting is
     * resolved dynamically (not stored in config).
     *
     * @param string $userId The feed token owner
     * @param array $config Feed configuration {scope, limit}
     * @param string $feedUrl The self-referencing feed URL
     * @return array{xml: string, lastModified: int}
     */
    public function generateFeed(string $userId, array $config, string $feedUrl, string $feedMediaBaseUrl = ''): array {
        $user = $this->userManager->get($userId);
        if ($user === null) {
            $this->logger->warning('[FeedService] User not found', ['userId' => $userId]);
            return ['xml' => $this->buildEmptyFeed($feedUrl), 'lastModified' => 0];
        }

        $scope = $config['scope'] ?? 'all';
        $limit = min(max((int)($config['limit'] ?? self::DEFAULT_LIMIT), 1), self::MAX_LIMIT);

        try {
            $userFolder = $this->rootFolder->getUserFolder($userId);
        } catch (\Exception $e) {
            $this->logger->error('[FeedService] Cannot access user folder', [
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
            return ['xml' => $this->buildEmptyFeed($feedUrl), 'lastModified' => 0];
        }

        // Navigate to IntraVox GroupFolder in user's view
        if (!$userFolder->nodeExists(self::GROUPFOLDER_NAME)) {
            $this->logger->debug('[FeedService] IntraVox folder not found for user', ['userId' => $userId]);
            return ['xml' => $this->buildEmptyFeed($feedUrl), 'lastModified' => 0];
        }

        try {
            $intraVoxFolder = $userFolder->get(self::GROUPFOLDER_NAME);
        } catch (\Exception $e) {
            return ['xml' => $this->buildEmptyFeed($feedUrl), 'lastModified' => 0];
        }

        if (!($intraVoxFolder instanceof Folder)) {
            return ['xml' => $this->buildEmptyFeed($feedUrl), 'lastModified' => 0];
        }

        // Collect pages based on scope
        $pages = [];

        if ($scope === 'language') {
            // Use the user's Nextcloud language setting
            $userLang = $this->getUserLanguage($userId);
            $this->collectPagesFromLanguage($intraVoxFolder, $userLang, $pages);
        } else {
            // All languages
            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                $this->collectPagesFromLanguage($intraVoxFolder, $lang, $pages);
            }
        }

        // Sort by modification date, newest first
        usort($pages, fn($a, $b) => ($b['modified'] ?? 0) <=> ($a['modified'] ?? 0));

        // Apply limit
        $pages = array_slice($pages, 0, $limit);

        // Determine last modified timestamp
        $lastModified = !empty($pages) ? ($pages[0]['modified'] ?? 0) : 0;

        // Build feed title
        $feedTitle = 'IntraVox';
        if ($scope === 'language') {
            $feedTitle .= ' — ' . strtoupper($this->getUserLanguage($userId));
        }

        $baseUrl = $this->urlGenerator->getAbsoluteURL('/apps/intravox');
        $xml = $this->buildRssXml($pages, $feedTitle, $feedUrl, $baseUrl, $feedMediaBaseUrl);

        return ['xml' => $xml, 'lastModified' => $lastModified];
    }

    /**
     * Collect pages from a specific language folder.
     */
    private function collectPagesFromLanguage(Folder $intraVoxFolder, string $language, array &$pages): void {
        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            return;
        }

        try {
            if (!$intraVoxFolder->nodeExists($language)) {
                return;
            }
            $langFolder = $intraVoxFolder->get($language);
            if (!($langFolder instanceof Folder)) {
                return;
            }
            $this->findPagesRecursive($langFolder, $pages, $language);
        } catch (\Exception $e) {
            $this->logger->debug('[FeedService] Error accessing language folder', [
                'language' => $language,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Collect pages from a specific folder (identified by its page uniqueId).
     */
    private function collectPagesFromFolder(Folder $intraVoxFolder, string $language, string $folderId, array &$pages): void {
        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            return;
        }

        try {
            if (!$intraVoxFolder->nodeExists($language)) {
                return;
            }
            $langFolder = $intraVoxFolder->get($language);
            if (!($langFolder instanceof Folder)) {
                return;
            }

            // Find the folder that contains the page with this uniqueId
            $targetFolder = $this->findFolderByPageId($langFolder, $folderId);
            if ($targetFolder !== null) {
                $this->findPagesRecursive($targetFolder, $pages, $language);
            }
        } catch (\Exception $e) {
            $this->logger->debug('[FeedService] Error accessing folder', [
                'folderId' => $folderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Recursively find pages in a folder.
     *
     * Follows the same pattern as PageService::findNewsPagesInFolder().
     */
    private function findPagesRecursive(Folder $folder, array &$pages, string $language): void {
        try {
            $items = $folder->getDirectoryListing();
        } catch (\Exception $e) {
            return;
        }

        foreach ($items as $item) {
            if (!($item instanceof Folder)) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (in_array($folderName, self::SKIP_FOLDERS, true)) {
                continue;
            }

            // Look for {foldername}.json inside the folder
            try {
                if (!$item->nodeExists($folderName . '.json')) {
                    // Still recurse into subfolders
                    $this->findPagesRecursive($item, $pages, $language);
                    continue;
                }

                $jsonFile = $item->get($folderName . '.json');
                if (!($jsonFile instanceof File) || !$jsonFile->isReadable()) {
                    $this->findPagesRecursive($item, $pages, $language);
                    continue;
                }

                // Check read permission
                if (($item->getPermissions() & 1) === 0) {
                    continue;
                }

                $content = $jsonFile->getContent();
                if ($content === false || $content === null || $content === '') {
                    $this->findPagesRecursive($item, $pages, $language);
                    continue;
                }

                $data = json_decode($content, true);
                if (!$data || !isset($data['uniqueId'], $data['title'])) {
                    $this->findPagesRecursive($item, $pages, $language);
                    continue;
                }

                $imageData = $this->extractFirstImage($data);

                $pages[] = [
                    'uniqueId' => $data['uniqueId'],
                    'title' => $data['title'],
                    'excerpt' => $this->extractExcerpt($data, self::EXCERPT_LENGTH),
                    'htmlContent' => $this->extractHtmlContent($data),
                    'modified' => $jsonFile->getMTime(),
                    'language' => $language,
                    'image' => $imageData,
                ];
            } catch (\Exception $e) {
                // Skip this folder
            }

            // Recurse into subfolders
            $this->findPagesRecursive($item, $pages, $language);
        }
    }

    /**
     * Find a folder that contains a page with the given uniqueId.
     */
    private function findFolderByPageId(Folder $folder, string $uniqueId): ?Folder {
        try {
            $items = $folder->getDirectoryListing();
        } catch (\Exception $e) {
            return null;
        }

        foreach ($items as $item) {
            if (!($item instanceof Folder)) {
                continue;
            }

            $folderName = $item->getName();
            if (in_array($folderName, self::SKIP_FOLDERS, true)) {
                continue;
            }

            // Check if this folder's page matches the uniqueId
            try {
                if ($item->nodeExists($folderName . '.json')) {
                    $jsonFile = $item->get($folderName . '.json');
                    if ($jsonFile instanceof File) {
                        $content = $jsonFile->getContent();
                        $data = json_decode($content ?: '', true);
                        if ($data && ($data['uniqueId'] ?? '') === $uniqueId) {
                            return $item;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Continue searching
            }

            // Recurse
            $result = $this->findFolderByPageId($item, $uniqueId);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Extract a plain text excerpt from page data.
     *
     * Same logic as PageService::getPageExcerpt().
     */
    private function extractExcerpt(array $pageData, int $length): string {
        if (!isset($pageData['layout']['rows']) || !is_array($pageData['layout']['rows'])) {
            return '';
        }

        foreach ($pageData['layout']['rows'] as $row) {
            if (!isset($row['widgets']) || !is_array($row['widgets'])) {
                continue;
            }

            foreach ($row['widgets'] as $widget) {
                if (($widget['type'] ?? '') === 'text' && !empty($widget['content'])) {
                    $text = strip_tags($widget['content']);
                    $text = preg_replace('/\s+/', ' ', $text);
                    $text = trim($text);

                    if (!empty($text)) {
                        if (mb_strlen($text) > $length) {
                            $text = mb_substr($text, 0, $length);
                            $lastSpace = mb_strrpos($text, ' ');
                            if ($lastSpace !== false && $lastSpace > $length * 0.7) {
                                $text = mb_substr($text, 0, $lastSpace);
                            }
                            $text .= '...';
                        }
                        return $text;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Extract HTML content from page data for content:encoded.
     *
     * Collects text widget HTML content to provide rich feed items.
     */
    private function extractHtmlContent(array $pageData): string {
        if (!isset($pageData['layout']['rows']) || !is_array($pageData['layout']['rows'])) {
            return '';
        }

        $html = '';
        foreach ($pageData['layout']['rows'] as $row) {
            if (!isset($row['widgets']) || !is_array($row['widgets'])) {
                continue;
            }
            foreach ($row['widgets'] as $widget) {
                $type = $widget['type'] ?? '';
                if ($type === 'text' && !empty($widget['content'])) {
                    $html .= $this->markdownToHtml($widget['content']);
                } elseif ($type === 'heading' && !empty($widget['content'])) {
                    $level = $widget['level'] ?? 2;
                    $html .= '<h' . $level . '>' . htmlspecialchars($widget['content']) . '</h' . $level . '>';
                }
            }
        }

        return $html;
    }

    /**
     * Convert basic markdown formatting to HTML for RSS content.
     *
     * Uses <p> with bullet/number prefixes instead of <ul>/<ol>/<li> because
     * Nextcloud News CSS does not render list elements properly.
     */
    private function markdownToHtml(string $text): string {
        $lines = explode("\n", $text);
        $html = '';
        $olCounter = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $olCounter = 0;
                continue;
            }

            // Checkbox list item: - [ ] item or - [x] item
            if (preg_match('/^[-*]\s+\[([xX ])\]\s*(.+)$/', $trimmed, $m)) {
                $check = strtolower($m[1]) === 'x' ? "\u{2611}" : "\u{2610}";
                $html .= '<p>' . $check . ' ' . $this->inlineMarkdown(htmlspecialchars($m[2])) . '</p>';
                continue;
            }

            // Unordered list item: - item or * item
            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $m)) {
                $olCounter = 0;
                $html .= '<p>' . "\u{2022}" . ' ' . $this->inlineMarkdown(htmlspecialchars($m[1])) . '</p>';
                continue;
            }

            // Ordered list item: 1. item
            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $m)) {
                $olCounter++;
                $html .= '<p>' . $olCounter . '. ' . $this->inlineMarkdown(htmlspecialchars($m[1])) . '</p>';
                continue;
            }

            $olCounter = 0;
            $html .= '<p>' . $this->inlineMarkdown(htmlspecialchars($trimmed)) . '</p>';
        }

        return $html;
    }

    /**
     * Apply inline markdown formatting (bold, italic) to already-escaped HTML text.
     */
    private function inlineMarkdown(string $text): string {
        // Bold: **text** or __text__
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
        // Italic: *text* or _text_ (but not inside bold markers)
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/(?<![a-zA-Z0-9])_(.+?)_(?![a-zA-Z0-9])/', '<em>$1</em>', $text);
        return $text;
    }

    /**
     * Extract the first image from page data.
     *
     * Same logic as PageService::getPageFirstImage().
     */
    private function extractFirstImage(array $pageData): ?array {
        // Check header row first
        if (isset($pageData['layout']['headerRow']['widgets']) && is_array($pageData['layout']['headerRow']['widgets'])) {
            foreach ($pageData['layout']['headerRow']['widgets'] as $widget) {
                if (($widget['type'] ?? '') === 'image' && !empty($widget['src'])) {
                    return [
                        'src' => $widget['src'],
                        'mediaFolder' => $widget['mediaFolder'] ?? 'page',
                    ];
                }
            }
        }

        // Then check main rows
        if (isset($pageData['layout']['rows']) && is_array($pageData['layout']['rows'])) {
            foreach ($pageData['layout']['rows'] as $row) {
                if (!isset($row['widgets']) || !is_array($row['widgets'])) {
                    continue;
                }
                foreach ($row['widgets'] as $widget) {
                    if (($widget['type'] ?? '') === 'image' && !empty($widget['src'])) {
                        return [
                            'src' => $widget['src'],
                            'mediaFolder' => $widget['mediaFolder'] ?? 'page',
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Serve a media file for a specific user (used by public feed media endpoint).
     *
     * @return array{content: string, mimeType: string, filename: string}|null
     */
    public function getMediaForUser(string $userId, string $pageId, string $filename): ?array {
        $filename = basename($filename); // Prevent directory traversal

        try {
            $userFolder = $this->rootFolder->getUserFolder($userId);
        } catch (\Exception $e) {
            return null;
        }

        if (!$userFolder->nodeExists(self::GROUPFOLDER_NAME)) {
            return null;
        }

        try {
            $intraVoxFolder = $userFolder->get(self::GROUPFOLDER_NAME);
        } catch (\Exception $e) {
            return null;
        }

        if (!($intraVoxFolder instanceof Folder)) {
            return null;
        }

        // Search all language folders for the page
        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            try {
                if (!$intraVoxFolder->nodeExists($lang)) {
                    continue;
                }
                $langFolder = $intraVoxFolder->get($lang);
                if (!($langFolder instanceof Folder)) {
                    continue;
                }

                $mediaFile = $this->findMediaInFolder($langFolder, $pageId, $filename);
                if ($mediaFile !== null) {
                    $mimeType = $mediaFile->getMimeType();
                    if ($mimeType === 'application/octet-stream') {
                        $ext = strtolower(pathinfo($mediaFile->getName(), PATHINFO_EXTENSION));
                        $mimeType = match ($ext) {
                            'jpg', 'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'webp' => 'image/webp',
                            'svg' => 'image/svg+xml',
                            default => $mimeType,
                        };
                    }

                    return [
                        'content' => $mediaFile->getContent(),
                        'mimeType' => $mimeType,
                        'filename' => $mediaFile->getName(),
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Find a media file inside a page's _media folder by uniqueId.
     */
    private function findMediaInFolder(Folder $folder, string $pageId, string $filename): ?File {
        try {
            $items = $folder->getDirectoryListing();
        } catch (\Exception $e) {
            return null;
        }

        foreach ($items as $item) {
            if (!($item instanceof Folder)) {
                continue;
            }

            $folderName = $item->getName();
            if (in_array($folderName, self::SKIP_FOLDERS, true)) {
                continue;
            }

            // Check if this folder's page matches the pageId
            try {
                if ($item->nodeExists($folderName . '.json')) {
                    $jsonFile = $item->get($folderName . '.json');
                    if ($jsonFile instanceof File) {
                        $content = $jsonFile->getContent();
                        $data = json_decode($content ?: '', true);
                        if ($data && ($data['uniqueId'] ?? '') === $pageId) {
                            // Found the page, look for media
                            if ($item->nodeExists('_media') && $item->get('_media') instanceof Folder) {
                                $mediaFolder = $item->get('_media');
                                if ($mediaFolder->nodeExists($filename)) {
                                    $file = $mediaFolder->get($filename);
                                    if ($file instanceof File) {
                                        return $file;
                                    }
                                }
                            }
                            return null; // Page found but media not found
                        }
                    }
                }
            } catch (\Exception $e) {
                // Continue searching
            }

            // Recurse
            $result = $this->findMediaInFolder($item, $pageId, $filename);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Get the user's language preference from Nextcloud settings.
     */
    private function getUserLanguage(string $userId): string {
        $lang = $this->config->getUserValue($userId, 'core', 'lang', self::DEFAULT_LANGUAGE);
        $langCode = strtolower(substr($lang, 0, 2));
        return in_array($langCode, self::SUPPORTED_LANGUAGES, true)
            ? $langCode
            : self::DEFAULT_LANGUAGE;
    }

    /**
     * Build RSS 2.0 XML from collected pages.
     */
    private function buildRssXml(array $pages, string $feedTitle, string $feedUrl, string $baseUrl, string $feedMediaBaseUrl = ''): string {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->writeAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $xml->writeAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');

        $xml->startElement('channel');
        $xml->writeElement('title', $feedTitle);
        $xml->writeElement('link', $baseUrl);
        $xml->writeElement('description', 'IntraVox intranet pages feed');
        $xml->writeElement('lastBuildDate', (new \DateTime())->format(\DateTime::RSS));

        // Self-referencing atom:link (best practice)
        $xml->startElement('atom:link');
        $xml->writeAttribute('href', $feedUrl);
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/rss+xml');
        $xml->endElement();

        foreach ($pages as $page) {
            $xml->startElement('item');
            $xml->writeElement('title', $page['title'] ?? '');
            $xml->writeElement('link', $baseUrl . '/#' . ($page['uniqueId'] ?? ''));

            if (!empty($page['excerpt'])) {
                $xml->writeElement('description', $page['excerpt']);
            }

            // Rich HTML content (content:encoded)
            $imageUrl = null;
            if (!empty($page['image']) && !empty($feedMediaBaseUrl)) {
                $imageSrc = $page['image']['src'];
                $imageUrl = $feedMediaBaseUrl . '/' . ($page['uniqueId'] ?? '') . '/' . rawurlencode($imageSrc);
            }

            $htmlContent = $page['htmlContent'] ?? '';
            if (!empty($imageUrl) || !empty($htmlContent)) {
                $encoded = '';
                if (!empty($imageUrl)) {
                    $encoded .= '<p><img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($page['title'] ?? '') . '" style="max-width:100%;max-height:150px;width:auto;height:auto;border-radius:4px;" /></p>';
                }
                if (!empty($htmlContent)) {
                    $encoded .= $htmlContent;
                }
                $xml->startElement('content:encoded');
                $xml->writeCdata($encoded);
                $xml->endElement();
            }

            if (!empty($page['modified'])) {
                $xml->writeElement('pubDate', date(\DateTime::RSS, $page['modified']));
            }

            // Image: enclosure + media:thumbnail + media:content
            if (!empty($imageUrl)) {
                $mimeType = $this->guessImageMimeType($imageSrc);

                $xml->startElement('enclosure');
                $xml->writeAttribute('url', $imageUrl);
                $xml->writeAttribute('type', $mimeType);
                $xml->writeAttribute('length', '0');
                $xml->endElement();

                $xml->startElement('media:thumbnail');
                $xml->writeAttribute('url', $imageUrl);
                $xml->endElement();

                $xml->startElement('media:content');
                $xml->writeAttribute('url', $imageUrl);
                $xml->writeAttribute('medium', 'image');
                $xml->writeAttribute('type', $mimeType);
                $xml->endElement();
            }

            $xml->startElement('guid');
            $xml->writeAttribute('isPermaLink', 'false');
            $xml->text($page['uniqueId'] ?? '');
            $xml->endElement();

            $xml->endElement(); // item
        }

        $xml->endElement(); // channel
        $xml->endElement(); // rss
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function guessImageMimeType(string $filename): string {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/jpeg',
        };
    }

    /**
     * Build an empty RSS feed (used for error cases).
     */
    private function buildEmptyFeed(string $feedUrl): string {
        return $this->buildRssXml([], 'IntraVox', $feedUrl, $this->urlGenerator->getAbsoluteURL('/apps/intravox'));
    }
}
