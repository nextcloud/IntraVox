<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import;

use DOMDocument;
use DOMElement;
use DOMNode;
use OCA\IntraVox\Service\Import\Confluence\Macros\MacroHandlerInterface;
use OCA\IntraVox\Service\Import\Confluence\Macros\ConversionContext;
use OCA\IntraVox\Service\Import\Confluence\Macros\PanelMacroHandler;
use OCA\IntraVox\Service\Import\Confluence\Macros\CodeMacroHandler;
use OCA\IntraVox\Service\Import\Confluence\Macros\AttachmentMacroHandler;
use OCA\IntraVox\Service\Import\Confluence\Macros\ExpandMacroHandler;
use OCA\IntraVox\Service\Import\Confluence\Macros\DefaultMacroHandler;
use Psr\Log\LoggerInterface;

/**
 * Confluence Storage Format Importer
 *
 * Parses Confluence pages (XHTML Storage Format) and converts to IntraVox format
 * Supports both Confluence Cloud and Server/Data Center
 */
class ConfluenceImporter extends AbstractImporter {
    /** @var array<MacroHandlerInterface> */
    private array $macroHandlers = [];

    private HtmlToWidgetConverter $htmlConverter;

    public function __construct(LoggerInterface $logger) {
        parent::__construct($logger);

        $this->htmlConverter = new HtmlToWidgetConverter($logger);

        // Register macro handlers (priority order)
        $this->registerMacroHandler(new PanelMacroHandler());
        $this->registerMacroHandler(new CodeMacroHandler());
        $this->registerMacroHandler(new AttachmentMacroHandler());
        $this->registerMacroHandler(new ExpandMacroHandler());
        $this->registerMacroHandler(new DefaultMacroHandler($logger)); // Fallback
    }

    public function getSupportedFormat(): string {
        return 'confluence';
    }

    /**
     * Register a macro handler
     *
     * @param MacroHandlerInterface $handler Macro handler
     */
    public function registerMacroHandler(MacroHandlerInterface $handler): void {
        $this->macroHandlers[] = $handler;
    }

    /**
     * Parse Confluence page(s) from XML/JSON content
     *
     * @param mixed $content Content (array with 'pages' or single page data)
     * @return IntermediateFormat Intermediate representation
     */
    public function parse($content): IntermediateFormat {
        $format = new IntermediateFormat();

        // Handle different input formats
        if (is_string($content)) {
            // Single page HTML/XML
            $pageData = [
                'title' => 'Imported Page',
                'body' => $content,
            ];
            $this->parsePage($pageData, $format);
        } elseif (is_array($content)) {
            if (isset($content['pages'])) {
                // Multiple pages
                foreach ($content['pages'] as $pageData) {
                    $this->parsePage($pageData, $format);
                }
            } else {
                // Single page object
                $this->parsePage($content, $format);
            }
        }

        return $format;
    }

    /**
     * Parse a single Confluence page
     *
     * @param array $pageData Page data with 'title' and 'body' (Storage Format HTML)
     * @param IntermediateFormat $format Intermediate format to add page to
     */
    private function parsePage(array $pageData, IntermediateFormat $format): void {
        $title = $pageData['title'] ?? 'Untitled';
        $body = $pageData['body'] ?? $pageData['content'] ?? '';
        $slug = $this->generateSlug($title);

        $this->logger->info('Parsing Confluence page: ' . $title);

        $page = new IntermediatePage($title, $slug, $format->language);

        // Set source file for hierarchy detection
        if (!empty($pageData['sourceFile'])) {
            $page->sourceFile = $pageData['sourceFile'];
        }

        // Set parent if specified
        if (!empty($pageData['parentSlug'])) {
            $page->parentSlug = $pageData['parentSlug'];
        }

        // Store metadata
        $page->metadata = [
            'created' => $pageData['created'] ?? null,
            'modified' => $pageData['modified'] ?? null,
            'author' => $pageData['author'] ?? null,
        ];

        // Parse Storage Format content
        $context = new ConversionContext(
            $pageData['baseUrl'] ?? '',
            $pageData['spaceKey'] ?? null
        );

        $blocks = $this->parseStorageFormat($body, $context);

        foreach ($blocks as $block) {
            $page->addContentBlock($block);
        }

        // Register media downloads
        foreach ($context->getMediaDownloads() as $media) {
            $format->addMediaDownload(new MediaDownload(
                $media['url'],
                $media['filename'],
                $slug
            ));
        }

        $format->addPage($page);
    }

    /**
     * Parse Confluence Storage Format HTML
     *
     * @param string $html Storage Format HTML
     * @param ConversionContext $context Conversion context
     * @return array<ContentBlock> Content blocks
     */
    private function parseStorageFormat(string $html, ConversionContext $context): array {
        if (empty(trim($html))) {
            return [];
        }

        $this->logger->debug('Parsing Storage Format HTML');

        // Parse XML/HTML with namespace support
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        // Wrap in div and add namespace declarations
        $wrappedXml = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<div xmlns:ac="http://www.atlassian.com/schema/confluence/4/ac/" ' .
            'xmlns:ri="http://www.atlassian.com/schema/confluence/4/ri/">' .
            $html .
            '</div>';

        // Use loadXML instead of loadHTML for proper namespace support
        $loaded = @$dom->loadXML($wrappedXml);

        if (!$loaded) {
            // Fallback to HTML parser if XML parsing fails
            $this->logger->debug('XML parsing failed, falling back to HTML parser');
            $dom->loadHTML($wrappedXml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }

        libxml_clear_errors();

        $rootNode = $dom->documentElement;

        if (!$rootNode) {
            $this->logger->warning('Failed to parse Storage Format HTML');
            return [];
        }

        return $this->processStorageFormatNodes($rootNode, $context);
    }

    /**
     * Process Storage Format DOM nodes
     *
     * @param DOMNode $node Node to process
     * @param ConversionContext $context Conversion context
     * @return array<ContentBlock> Content blocks
     */
    private function processStorageFormatNodes(DOMNode $node, ConversionContext $context): array {
        $blocks = [];

        foreach ($node->childNodes as $child) {
            if (!($child instanceof DOMElement)) {
                continue;
            }

            $tagName = $child->localName ?? $child->tagName;
            $namespace = $child->namespaceURI;

            // Handle Confluence-specific elements
            if ($namespace === 'http://www.atlassian.com/schema/confluence/4/ac/') {
                // Confluence namespaced elements
                if ($tagName === 'structured-macro') {
                    // Handle macro
                    $macroBlocks = $this->processMacro($child, $context);
                    $blocks = array_merge($blocks, $macroBlocks);
                } elseif ($tagName === 'image') {
                    // Handle image
                    $imageBlock = AttachmentMacroHandler::processImageElement($child, $context);
                    if ($imageBlock) {
                        $blocks[] = $imageBlock;
                    }
                } else {
                    // Other AC elements - recursively process
                    $childBlocks = $this->processStorageFormatNodes($child, $context);
                    $blocks = array_merge($blocks, $childBlocks);
                }
            } else {
                // Standard HTML elements - convert using HTML converter
                $html = $this->getOuterHtml($child);
                $htmlBlocks = $this->htmlConverter->convertHtmlToBlocks($html);
                $blocks = array_merge($blocks, $htmlBlocks);
            }
        }

        return $blocks;
    }

    /**
     * Process Confluence macro
     *
     * @param DOMElement $macro Macro element
     * @param ConversionContext $context Conversion context
     * @return array<ContentBlock> Content blocks
     */
    private function processMacro(DOMElement $macro, ConversionContext $context): array {
        $macroName = $macro->getAttribute('ac:name');

        if (empty($macroName)) {
            $this->logger->warning('Macro without name attribute');
            return [];
        }

        // Find handler for this macro
        foreach ($this->macroHandlers as $handler) {
            if ($handler->supports($macroName)) {
                return $handler->convert($macro, $context);
            }
        }

        // No handler found (shouldn't happen with DefaultMacroHandler)
        $this->logger->warning('No handler found for macro: ' . $macroName);
        return [];
    }

    /**
     * Get outer HTML of element
     *
     * @param DOMElement $element Element
     * @return string HTML
     */
    private function getOuterHtml(DOMElement $element): string {
        return $element->ownerDocument->saveHTML($element);
    }

    /**
     * Generate slug from title
     *
     * @param string $title Page title
     * @return string Slug
     */
    private function generateSlug(string $title): string {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if (empty($slug)) {
            $slug = 'page-' . substr(md5($title), 0, 8);
        }

        return $slug;
    }
}
