<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import\Confluence\Macros;

use DOMElement;
use OCA\IntraVox\Service\Import\ContentBlock;
use OCA\IntraVox\Service\Import\ImageBlock;
use OCA\IntraVox\Service\Import\HtmlBlock;

/**
 * Handler for Confluence attachment and image macros
 *
 * Handles:
 * - <ac:image> - Confluence image element
 * - <ri:attachment> - Attachment reference
 * - <ac:structured-macro ac:name="attachments"> - Attachments list
 */
class AttachmentMacroHandler implements MacroHandlerInterface {
    public function supports(string $macroName): bool {
        return in_array($macroName, ['attachments', 'viewfile', 'image']);
    }

    public function convert(DOMElement $macro, ConversionContext $context): array {
        $macroName = $macro->getAttribute('ac:name');

        return match($macroName) {
            'attachments' => $this->convertAttachmentsList($macro, $context),
            'viewfile' => $this->convertViewFile($macro, $context),
            'image' => $this->convertImage($macro, $context),
            default => [],
        };
    }

    /**
     * Convert attachments list macro
     *
     * @param DOMElement $macro Macro element
     * @param ConversionContext $context Context
     * @return array<ContentBlock>
     */
    private function convertAttachmentsList(DOMElement $macro, ConversionContext $context): array {
        // The attachments macro displays a list of all page attachments
        // For now, we'll create a simple HTML block noting this
        // In a full implementation, we'd query the page's attachments and create a links widget

        $html = '<div class="confluence-attachments"><p><em>Page attachments will be imported separately</em></p></div>';

        return [new HtmlBlock($html)];
    }

    /**
     * Convert viewfile macro (displays a specific file)
     *
     * @param DOMElement $macro Macro element
     * @param ConversionContext $context Context
     * @return array<ContentBlock>
     */
    private function convertViewFile(DOMElement $macro, ConversionContext $context): array {
        $filename = $context->getMacroParameter($macro, 'name');

        if (empty($filename)) {
            return [];
        }

        // For now, create a simple download link
        // In full implementation, this would be converted to a file widget
        $html = sprintf(
            '<p><a href="#" download>📄 %s</a></p>',
            htmlspecialchars($filename, ENT_QUOTES)
        );

        return [new HtmlBlock($html)];
    }

    /**
     * Convert image macro/element
     *
     * @param DOMElement $macro Macro element
     * @param ConversionContext $context Context
     * @return array<ContentBlock>
     */
    private function convertImage(DOMElement $macro, ConversionContext $context): array {
        // This is typically not called directly as <ac:image> elements
        // are processed in the main parser
        return [];
    }

    /**
     * Process Confluence image element
     *
     * Called from main parser when encountering <ac:image>
     *
     * @param DOMElement $imageElement The <ac:image> element
     * @param ConversionContext $context Context
     * @return ImageBlock|null Image block or null
     */
    public static function processImageElement(DOMElement $imageElement, ConversionContext $context): ?ImageBlock {
        // Find attachment reference
        $attachment = $imageElement->getElementsByTagNameNS('*', 'attachment')->item(0);

        if (!$attachment) {
            // Try URL reference
            $urlRef = $imageElement->getElementsByTagNameNS('*', 'url')->item(0);
            if ($urlRef) {
                /** @var DOMElement $urlRef */
                $url = $urlRef->getAttribute('ri:value');
                $filename = basename(parse_url($url, PHP_URL_PATH));

                $context->registerImageDownload($url, $filename);

                return new ImageBlock($url, '', $filename);
            }

            return null;
        }

        /** @var DOMElement $attachment */
        $filename = $attachment->getAttribute('ri:filename');

        if (empty($filename)) {
            return null;
        }

        // Build attachment URL (will be downloaded later)
        $baseUrl = $context->getBaseUrl();
        $spaceKey = $context->getSpaceKey();

        // Construct Confluence attachment URL
        // Format: /download/attachments/{pageId}/{filename}
        // For now, use filename directly as we'll download it separately
        $url = $filename; // Placeholder - actual download URL will be resolved during import

        // Get alt text from image element
        $alt = $imageElement->getAttribute('ac:alt') ?? '';

        $context->registerImageDownload($url, $filename);

        return new ImageBlock($url, $alt, $filename);
    }
}
