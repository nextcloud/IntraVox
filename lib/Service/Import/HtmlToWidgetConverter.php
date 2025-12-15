<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import;

use DOMDocument;
use DOMElement;
use DOMNode;
use Psr\Log\LoggerInterface;

/**
 * HTML to Widget Converter
 *
 * Converts HTML content to IntraVox content blocks (intermediate format)
 * Used by all importers to parse HTML and convert to structured widgets
 */
class HtmlToWidgetConverter {
    private LoggerInterface $logger;

    /** Allowed HTML tags for text widgets */
    private const ALLOWED_TAGS = [
        'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'em', 'u', 's', 'code', 'pre',
        'ul', 'ol', 'li', 'a', 'blockquote',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'br', 'hr', 'span', 'div',
        'img', 'video', 'iframe',
        'details', 'summary',
    ];

    /** Allowed HTML attributes */
    private const ALLOWED_ATTRIBUTES = [
        'href', 'src', 'alt', 'title', 'class', 'id',
        'width', 'height', 'style',
        'colspan', 'rowspan',
        'target', 'rel',
    ];

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Convert HTML to content blocks
     *
     * @param string $html HTML content
     * @return array<ContentBlock> Array of content blocks
     */
    public function convertHtmlToBlocks(string $html): array {
        if (empty(trim($html))) {
            return [];
        }

        // Parse HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        // Wrap in div to handle fragments
        $html = '<?xml encoding="UTF-8"><div>' . $html . '</div>';
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $blocks = [];
        $bodyNode = $dom->getElementsByTagName('div')->item(0);

        if ($bodyNode) {
            $blocks = $this->processNodeChildren($bodyNode);
        }

        return $blocks;
    }

    /**
     * Process child nodes and convert to content blocks
     *
     * @param DOMNode $node Parent node
     * @return array<ContentBlock> Content blocks
     */
    private function processNodeChildren(DOMNode $node): array {
        $blocks = [];
        $htmlBuffer = '';

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $tagName = strtolower($child->tagName);

                // Block-level elements that become separate widgets
                if ($this->isBlockLevelElement($tagName)) {
                    // Flush HTML buffer first
                    if (!empty(trim($htmlBuffer))) {
                        $blocks[] = new HtmlBlock($this->sanitizeHtml($htmlBuffer));
                        $htmlBuffer = '';
                    }

                    // Convert block element
                    $block = $this->convertElementToBlock($child);
                    if ($block) {
                        if (is_array($block)) {
                            $blocks = array_merge($blocks, $block);
                        } else {
                            $blocks[] = $block;
                        }
                    }
                } else {
                    // Inline element - add to buffer
                    $htmlBuffer .= $this->getOuterHtml($child);
                }
            } else {
                // Text node
                $text = $child->textContent;
                if (!empty(trim($text))) {
                    $htmlBuffer .= htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        }

        // Flush remaining buffer
        if (!empty(trim($htmlBuffer))) {
            $blocks[] = new HtmlBlock($this->sanitizeHtml($htmlBuffer));
        }

        return $blocks;
    }

    /**
     * Check if element is block-level
     *
     * @param string $tagName Tag name
     * @return bool True if block-level
     */
    private function isBlockLevelElement(string $tagName): bool {
        return in_array($tagName, [
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'p', 'div', 'blockquote',
            'ul', 'ol', 'table',
            'hr', 'img', 'video', 'iframe',
            'pre', 'details',
        ]);
    }

    /**
     * Convert DOM element to content block
     *
     * @param DOMElement $element DOM element
     * @return ContentBlock|array|null Content block(s) or null
     */
    private function convertElementToBlock(DOMElement $element): ContentBlock|array|null {
        $tagName = strtolower($element->tagName);

        switch ($tagName) {
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $level = (int)substr($tagName, 1);
                return new HeadingBlock($level, $element->textContent);

            case 'hr':
                return new DividerBlock();

            case 'img':
                $src = $element->getAttribute('src');
                $alt = $element->getAttribute('alt');
                $title = $element->getAttribute('title');
                return new ImageBlock($src, $alt, null, $title);

            case 'pre':
                // Check for code block
                $codeElement = $element->getElementsByTagName('code')->item(0);
                if ($codeElement) {
                    $code = $codeElement->textContent;
                    $language = null;

                    // Try to extract language from class (e.g., language-php)
                    $class = $codeElement->getAttribute('class');
                    if (preg_match('/language-(\w+)/', $class, $matches)) {
                        $language = $matches[1];
                    }

                    return new CodeBlock($code, $language);
                }

                // Regular pre tag
                return new HtmlBlock($this->getOuterHtml($element));

            case 'details':
                // Expand/collapse - keep as HTML with details/summary
                return new HtmlBlock($this->getOuterHtml($element), 'confluence-expand');

            case 'div':
                // Check for special classes (panels, etc.)
                $class = $element->getAttribute('class');

                // Confluence panels
                if (preg_match('/confluence-panel-(\w+)/', $class, $matches)) {
                    $panelType = $matches[1];
                    $title = null;
                    $content = '';

                    // Extract title and body
                    foreach ($element->childNodes as $child) {
                        if ($child instanceof DOMElement) {
                            if (str_contains($child->getAttribute('class'), 'confluence-panel-title')) {
                                $title = $child->textContent;
                            } elseif (str_contains($child->getAttribute('class'), 'confluence-panel-body')) {
                                $content = $this->getInnerHtml($child);
                            }
                        }
                    }

                    if (empty($content)) {
                        $content = $this->getInnerHtml($element);
                    }

                    return new PanelBlock($panelType, $content, $title);
                }

                // Regular div - keep as HTML
                return new HtmlBlock($this->getOuterHtml($element));

            case 'p':
            case 'blockquote':
            case 'ul':
            case 'ol':
            case 'table':
                return new HtmlBlock($this->getOuterHtml($element));

            default:
                $this->logger->debug('Unhandled block element: ' . $tagName);
                return new HtmlBlock($this->getOuterHtml($element));
        }
    }

    /**
     * Get outer HTML of element
     *
     * @param DOMElement $element Element
     * @return string HTML
     */
    private function getOuterHtml(DOMElement $element): string {
        $doc = $element->ownerDocument;
        return $doc->saveHTML($element);
    }

    /**
     * Get inner HTML of element
     *
     * @param DOMElement $element Element
     * @return string HTML
     */
    private function getInnerHtml(DOMElement $element): string {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }
        return $html;
    }

    /**
     * Sanitize HTML - remove unsafe tags and attributes
     *
     * @param string $html HTML content
     * @return string Sanitized HTML
     */
    private function sanitizeHtml(string $html): string {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        $html = '<?xml encoding="UTF-8"><div>' . $html . '</div>';
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $bodyNode = $dom->getElementsByTagName('div')->item(0);
        if ($bodyNode) {
            $this->sanitizeNode($bodyNode);
            return $this->getInnerHtml($bodyNode);
        }

        return $html;
    }

    /**
     * Recursively sanitize DOM node
     *
     * @param DOMNode $node Node to sanitize
     */
    private function sanitizeNode(DOMNode $node): void {
        $toRemove = [];

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $tagName = strtolower($child->tagName);

                // Remove disallowed tags
                if (!in_array($tagName, self::ALLOWED_TAGS)) {
                    $toRemove[] = $child;
                    continue;
                }

                // Remove disallowed attributes
                $attributes = [];
                foreach ($child->attributes as $attr) {
                    $attributes[] = $attr->name;
                }

                foreach ($attributes as $attrName) {
                    if (!in_array($attrName, self::ALLOWED_ATTRIBUTES)) {
                        $child->removeAttribute($attrName);
                    }
                }

                // Recursively sanitize children
                $this->sanitizeNode($child);
            }
        }

        // Remove disallowed nodes
        foreach ($toRemove as $element) {
            $node->removeChild($element);
        }
    }

    /**
     * Extract images from HTML
     *
     * @param string $html HTML content
     * @return array<ImageBlock> Image blocks
     */
    public function extractImages(string $html): array {
        $images = [];

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $imgElements = $dom->getElementsByTagName('img');
        foreach ($imgElements as $img) {
            /** @var DOMElement $img */
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');
            $title = $img->getAttribute('title');

            if (!empty($src)) {
                $images[] = new ImageBlock($src, $alt, null, $title);
            }
        }

        return $images;
    }
}
