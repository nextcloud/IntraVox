<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import;

use Psr\Log\LoggerInterface;
use ZipArchive;

/**
 * Confluence HTML Export Importer
 *
 * Imports content from Confluence HTML exports (exported via Space Tools → Export Space)
 */
class ConfluenceHtmlImporter {
    private ConfluenceImporter $confluenceImporter;

    public function __construct(
        private LoggerInterface $logger
    ) {
        $this->confluenceImporter = new ConfluenceImporter($logger);
    }

    /**
     * Import from Confluence HTML export ZIP file
     *
     * @param string $zipPath Path to ZIP file
     * @param string $language Target language
     * @return IntermediateFormat Intermediate representation
     */
    public function importFromZip(string $zipPath, string $language = 'nl'): IntermediateFormat {
        // Extract ZIP to temporary directory
        $extractPath = $this->extractZip($zipPath);

        try {
            // Find all HTML files
            $htmlFiles = $this->findHtmlFiles($extractPath);

            // Build page hierarchy from directory structure and breadcrumbs
            $pageHierarchy = $this->buildPageHierarchy($htmlFiles, $extractPath);

            // Parse each HTML file
            $format = new IntermediateFormat();
            $format->language = $language;

            foreach ($htmlFiles as $htmlFile) {
                $pageData = $this->parseHtmlFile($htmlFile, $extractPath);

                if ($pageData) {
                    // Add hierarchy information
                    $relativePath = substr($htmlFile, strlen($extractPath) + 1);
                    if (isset($pageHierarchy[$relativePath])) {
                        $pageData['parentPath'] = $pageHierarchy[$relativePath]['parent'];
                        $pageData['filePath'] = $relativePath;
                    }

                    // Parse using ConfluenceImporter
                    $pageFormat = $this->confluenceImporter->parse($pageData);

                    // Merge pages
                    foreach ($pageFormat->pages as $page) {
                        $page->language = $language;
                        $format->addPage($page);
                    }

                    // Merge media downloads
                    foreach ($pageFormat->mediaDownloads as $media) {
                        $format->addMediaDownload($media);
                    }
                }
            }

            // Set parent relationships based on hierarchy
            $this->setParentRelationships($format, $pageHierarchy);

            return $format;

        } finally {
            // Cleanup temporary directory
            $this->cleanupDirectory($extractPath);
        }
    }

    /**
     * Extract ZIP file to temporary directory
     * Uses cryptographically secure random directory names and validates paths
     * to prevent ZIP Slip attacks (CWE-22)
     *
     * @param string $zipPath Path to ZIP file
     * @return string Path to extracted directory
     */
    private function extractZip(string $zipPath): string {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open ZIP file: ' . $zipPath);
        }

        // Create temporary directory with cryptographically secure random name
        // Use random_bytes() instead of uniqid() for unpredictable names
        $extractPath = sys_get_temp_dir() . '/confluence_import_' . bin2hex(random_bytes(16));
        // Use restrictive permissions (0700 = owner only)
        if (!mkdir($extractPath, 0700, true)) {
            throw new \RuntimeException('Failed to create temp directory: ' . $extractPath);
        }

        // Get real path for ZIP Slip prevention
        $realExtractPath = realpath($extractPath);
        if ($realExtractPath === false) {
            throw new \RuntimeException('Failed to resolve extract path');
        }
        $realExtractPath = rtrim($realExtractPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Extract files individually with path traversal validation
        $extractedCount = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = $stat['name'];

            // Skip root directory entries and __MACOSX
            if ($filename === '/' || $filename === '' || strpos($filename, '__MACOSX') !== false) {
                continue;
            }

            // Skip hidden files that start with ._
            if (strpos(basename($filename), '._') === 0) {
                continue;
            }

            // Build target path
            $targetPath = $realExtractPath . $filename;

            // Check if this is a directory entry
            $isDirectory = substr($filename, -1) === '/';

            if ($isDirectory) {
                // Create directory with restrictive permissions
                if (!is_dir($targetPath)) {
                    if (!mkdir($targetPath, 0700, true)) {
                        throw new \RuntimeException('Failed to create directory: ' . $filename);
                    }
                }
            } else {
                // For files, verify the path is within destination (ZIP Slip prevention)
                $dirname = dirname($targetPath);
                if (!is_dir($dirname)) {
                    if (!mkdir($dirname, 0700, true)) {
                        throw new \RuntimeException('Failed to create parent directory for: ' . $filename);
                    }
                }

                // Validate path is within extract directory
                $realDirname = realpath($dirname);
                if ($realDirname === false || strpos($realDirname . DIRECTORY_SEPARATOR, $realExtractPath) !== 0) {
                    $this->logger->error('ZIP Slip attack detected in Confluence import', [
                        'filename' => $filename,
                        'targetPath' => $targetPath,
                        'realDirname' => $realDirname,
                        'extractPath' => $realExtractPath
                    ]);
                    // Clean up and throw
                    $zip->close();
                    $this->cleanupDirectory($extractPath);
                    throw new \RuntimeException('Zip Slip detected: Invalid path in ZIP file');
                }

                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    file_put_contents($targetPath, $content);
                    $extractedCount++;
                }
            }
        }

        $zip->close();

        // Check if directory exists
        if (!is_dir($extractPath)) {
            throw new \RuntimeException('Extract path does not exist: ' . $extractPath);
        }

        return $extractPath;
    }

    /**
     * Find all HTML files in directory (recursively)
     *
     * @param string $directory Directory to search
     * @return array Array of file paths
     */
    private function findHtmlFiles(string $directory): array {
        $htmlFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'html') {
                // Skip index files and navigation files
                $filename = $file->getFilename();
                if (!in_array($filename, ['index.html', 'overview.html', 'toc.html'])) {
                    $htmlFiles[] = $file->getPathname();
                }
            }
        }

        return $htmlFiles;
    }

    /**
     * Parse HTML file to page data
     *
     * @param string $htmlFile Path to HTML file
     * @param string $baseDir Base directory for resolving attachments
     * @return array|null Page data or null if parsing fails
     */
    private function parseHtmlFile(string $htmlFile, string $baseDir): ?array {
        $html = file_get_contents($htmlFile);

        if (!$html) {
            $this->logger->warning('Failed to read HTML file: ' . $htmlFile);
            return null;
        }

        // Extract title from HTML
        $title = $this->extractTitle($html, $htmlFile);

        // Extract body content (between <body> tags or specific content div)
        $body = $this->extractBody($html);

        if (empty($body)) {
            $this->logger->warning('No body content found in: ' . $htmlFile);
            return null;
        }

        // Get relative path for hierarchy matching
        $relativePath = substr($htmlFile, strlen($baseDir) + 1);

        return [
            'title' => $title,
            'body' => $body,
            'sourceFile' => $relativePath,
            'baseDir' => $baseDir,
        ];
    }

    /**
     * Extract title from HTML
     *
     * @param string $html HTML content
     * @param string $fallbackPath Fallback path for title
     * @return string Page title
     */
    private function extractTitle(string $html, string $fallbackPath): string {
        // Try to find <title> tag
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $title = html_entity_decode(strip_tags($matches[1]));
            $title = trim($title);

            // Remove " - Confluence" suffix if present
            $title = preg_replace('/ - Confluence$/', '', $title);

            if (!empty($title)) {
                return $title;
            }
        }

        // Try to find <h1> tag
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches)) {
            $title = html_entity_decode(strip_tags($matches[1]));
            $title = trim($title);

            if (!empty($title)) {
                return $title;
            }
        }

        // Fallback: use filename
        $filename = basename($fallbackPath, '.html');
        return ucfirst(str_replace(['-', '_'], ' ', $filename));
    }

    /**
     * Extract body content from HTML
     *
     * @param string $html Full HTML content
     * @return string Body content
     */
    private function extractBody(string $html): string {
        // Confluence exports typically have content in a div with id="main-content" or class="wiki-content"
        // Try to extract just the content area

        // Create DOMDocument for better HTML parsing
        $dom = new \DOMDocument();
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Try to find content by common Confluence selectors (in order of specificity)
        $selectors = [
            "//div[@id='main-content']",
            "//div[contains(@class, 'wiki-content')]",
            "//div[contains(@class, 'page-content')]",
            "//div[@id='content']",
            "//main",
            "//article"
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $contentNode = $nodes->item(0);

                // Remove unwanted elements from content
                $this->removeUnwantedElements($contentNode, $xpath);

                // Get the innerHTML
                $content = $dom->saveHTML($contentNode);

                // Clean up the extracted content
                $content = $this->cleanupHtml($content);

                if (!empty(trim(strip_tags($content)))) {
                    return $content;
                }
            }
        }

        // Fallback: Try regex-based extraction for body
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $bodyContent = $matches[1];

            // Remove common Confluence navigation/header elements via regex
            $bodyContent = preg_replace('/<div[^>]*id=["\']pagetreesearch["\'][^>]*>.*?<\/div>/is', '', $bodyContent);
            $bodyContent = preg_replace('/<div[^>]*class=["\'][^"\']*breadcrumbs[^"\']*["\'][^>]*>.*?<\/div>/is', '', $bodyContent);
            $bodyContent = preg_replace('/<form[^>]*pagetreesearchform[^>]*>.*?<\/form>/is', '', $bodyContent);

            // Clean up the extracted content
            $bodyContent = $this->cleanupHtml($bodyContent);

            if (!empty(trim(strip_tags($bodyContent)))) {
                return $bodyContent;
            }
        }

        // Last resort: return full HTML
        return $html;
    }

    /**
     * Clean up extracted HTML content
     *
     * @param string $html HTML content to clean
     * @return string Cleaned HTML
     */
    private function cleanupHtml(string $html): string {
        // Remove orphaned closing tags (tags without opening tags)
        // Common ones from Confluence exports
        $html = preg_replace('/<\/(p|div|span|h[1-6]|ul|ol|li|table|tr|td|th)>\s*(?!<)/i', '', $html);

        // Remove empty paragraph tags and divs
        $html = preg_replace('/<(p|div)[^>]*>\s*<\/\1>/i', '', $html);

        // Remove standalone closing tags at the beginning or end
        $html = preg_replace('/^\s*<\/[^>]+>/i', '', $html);
        $html = preg_replace('/<\/[^>]+>\s*$/i', '', $html);

        // Trim whitespace
        $html = trim($html);

        return $html;
    }

    /**
     * Remove unwanted elements from a DOM node
     *
     * @param \DOMNode $node Node to clean
     * @param \DOMXPath $xpath XPath object
     */
    private function removeUnwantedElements(\DOMNode $node, \DOMXPath $xpath): void {
        // Remove navigation, breadcrumbs, search forms, etc.
        $unwantedSelectors = [
            ".//div[@id='pagetreesearch']",
            ".//div[contains(@class, 'breadcrumbs')]",
            ".//div[contains(@class, 'pageSection')]",
            ".//form[contains(@name, 'pagetreesearchform')]",
            ".//form[contains(@class, 'aui')]",
            ".//nav",
            ".//div[contains(@class, 'page-metadata')]"
        ];

        foreach ($unwantedSelectors as $selector) {
            $unwantedNodes = $xpath->query($selector, $node);
            if ($unwantedNodes) {
                foreach ($unwantedNodes as $unwantedNode) {
                    if ($unwantedNode->parentNode) {
                        $unwantedNode->parentNode->removeChild($unwantedNode);
                    }
                }
            }
        }
    }

    /**
     * Build page hierarchy from directory structure and breadcrumbs
     *
     * Confluence HTML exports can have hierarchy in multiple ways:
     * 1. Directory structure (subdirectories represent child pages)
     * 2. Breadcrumb navigation in HTML
     * 3. Index.html with page tree (also used for ordering)
     *
     * @param array $htmlFiles List of HTML file paths
     * @param string $baseDir Base directory
     * @return array Hierarchy map [filePath => ['parent' => parentPath, 'title' => title, 'order' => int]]
     */
    private function buildPageHierarchy(array $htmlFiles, string $baseDir): array {
        $hierarchy = [];

        // Extract order from index.html if it exists
        $pageOrder = $this->extractPageOrderFromIndex($baseDir);

        foreach ($htmlFiles as $htmlFile) {
            $relativePath = substr($htmlFile, strlen($baseDir) + 1);
            $pathParts = explode('/', $relativePath);
            $fileName = array_pop($pathParts);

            // Initialize hierarchy entry with order
            $hierarchy[$relativePath] = [
                'parent' => null,
                'title' => null,
                'level' => count($pathParts), // Depth in directory structure
                'order' => $pageOrder[$relativePath] ?? 9999, // Use extracted order or default to end
            ];

            // Method 1: Use directory structure to determine hierarchy
            // If file is in a subdirectory, look for parent HTML in parent directory
            if (count($pathParts) > 0) {
                // Look for index.html in parent directory
                $parentDir = implode('/', $pathParts);
                $parentIndexPath = $parentDir . '/index.html';

                // Check if parent index exists
                if (isset($hierarchy[$parentIndexPath])) {
                    $hierarchy[$relativePath]['parent'] = $parentIndexPath;
                } else {
                    // Look for any HTML file in parent directory with similar name
                    $parentDirName = end($pathParts);
                    $possibleParentFile = count($pathParts) > 1
                        ? implode('/', array_slice($pathParts, 0, -1)) . '/' . $parentDirName . '.html'
                        : $parentDirName . '.html';

                    if (isset($hierarchy[$possibleParentFile])) {
                        $hierarchy[$relativePath]['parent'] = $possibleParentFile;
                    }
                }
            }

            // Method 2: Parse breadcrumbs from HTML file
            $html = file_get_contents($htmlFile);

            $breadcrumbParent = $this->extractParentFromBreadcrumb($html, $relativePath);
            if ($breadcrumbParent) {
                $hierarchy[$relativePath]['parent'] = $breadcrumbParent;
            }
        }

        return $hierarchy;
    }

    /**
     * Extract page order from index.html file
     *
     * Confluence HTML exports typically include an index.html with links to all pages
     * in the correct order. We parse this to preserve the original page order.
     *
     * @param string $baseDir Base directory
     * @return array Map of [filePath => order] where order is 0-based index
     */
    private function extractPageOrderFromIndex(string $baseDir): array {
        $indexPath = $baseDir . '/index.html';
        $order = [];

        if (!file_exists($indexPath)) {
            return $order;
        }

        $html = file_get_contents($indexPath);
        if (!$html) {
            return $order;
        }

        // Extract all links from index.html
        preg_match_all('/<a[^>]*href=["\']([^"\']+)["\'][^>]*>/is', $html, $matches);

        if (empty($matches[1])) {
            return $order;
        }

        $currentOrder = 0;
        foreach ($matches[1] as $href) {
            // Normalize href to relative path
            $normalizedHref = $this->normalizeHref($href);

            // Skip empty hrefs
            if (empty($normalizedHref)) {
                continue;
            }

            // Store order (0-based index)
            $order[$normalizedHref] = $currentOrder;
            $currentOrder++;
        }

        return $order;
    }

    /**
     * Extract parent page from breadcrumb navigation
     *
     * @param string $html HTML content
     * @param string $currentFilePath Current file's relative path (e.g., "NICE/234424394.html")
     * @return string|null Parent page path
     */
    private function extractParentFromBreadcrumb(string $html, string $currentFilePath): ?string {
        // Look for Confluence breadcrumb patterns
        // Common patterns:
        // <ol class="breadcrumbs"> or <ol id="breadcrumbs">
        // <div id="breadcrumb-section">
        // <nav aria-label="Breadcrumb">

        // Try breadcrumb list (match both class and id)
        if (preg_match('/<ol[^>]*(?:class|id)=["\'][^"\']*breadcrumb[^"\']*["\'][^>]*>(.*?)<\/ol>/is', $html, $matches)) {
            $breadcrumbHtml = $matches[1];

            // Extract all links from breadcrumb
            preg_match_all('/<a[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $breadcrumbHtml, $links, PREG_SET_ORDER);

            // The parent is typically the second-to-last link (last is current page)
            if (count($links) >= 2) {
                $parentLink = $links[count($links) - 2];
                $href = $parentLink[1];

                // Convert href to relative path
                $normalizedHref = $this->normalizeHref($href);

                // Get the directory from current file path (e.g., "NICE" from "NICE/234424394.html")
                $pathParts = explode('/', $currentFilePath);
                $directory = count($pathParts) > 1 ? $pathParts[0] : '';

                // If the href doesn't include a directory, prepend the current file's directory
                if ($directory && strpos($normalizedHref, '/') === false) {
                    return $directory . '/' . $normalizedHref;
                }
                return $normalizedHref;
            }
        }

        return null;
    }

    /**
     * Normalize href to relative file path
     *
     * @param string $href Link href attribute
     * @return string Normalized relative path
     */
    private function normalizeHref(string $href): string {
        // Remove leading ./
        $href = preg_replace('/^\.\//', '', $href);

        // Remove leading /
        $href = ltrim($href, '/');

        // Remove anchor
        $href = preg_replace('/#.*$/', '', $href);

        // Remove query string
        $href = preg_replace('/\?.*$/', '', $href);

        return $href;
    }

    /**
     * Set parent relationships on pages based on hierarchy
     *
     * @param IntermediateFormat $format Intermediate format with pages
     * @param array $hierarchy Hierarchy map
     */
    private function setParentRelationships(IntermediateFormat $format, array $hierarchy): void {
        // Build a map of filePath -> Page for quick lookup
        $pagesByPath = [];

        foreach ($format->pages as $page) {
            if (isset($page->sourceFile)) {
                $pagesByPath[$page->sourceFile] = $page;
            }
        }

        // Set parent relationships
        foreach ($hierarchy as $filePath => $info) {
            if (!isset($pagesByPath[$filePath])) {
                continue;
            }

            $page = $pagesByPath[$filePath];
            $parentPath = $info['parent'];

            // Store order in page metadata for sorting later
            if (isset($info['order'])) {
                $page->metadata['confluenceOrder'] = $info['order'];
            }

            if ($parentPath && isset($pagesByPath[$parentPath])) {
                $parentPage = $pagesByPath[$parentPath];
                $page->parentUniqueId = $parentPage->uniqueId;
            }
        }
    }

    /**
     * Cleanup temporary directory
     *
     * @param string $directory Directory to remove
     */
    private function cleanupDirectory(string $directory): void {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($directory);
    }
}
