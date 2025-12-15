<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import\Confluence\Macros;

use DOMElement;
use OCA\IntraVox\Service\Import\ContentBlock;

/**
 * Interface for Confluence macro handlers
 *
 * Each macro handler converts a specific Confluence macro (<ac:structured-macro>)
 * to IntraVox content blocks
 */
interface MacroHandlerInterface {
    /**
     * Check if this handler supports the given macro name
     *
     * @param string $macroName Macro name (e.g., 'info', 'code', 'toc')
     * @return bool True if supported
     */
    public function supports(string $macroName): bool;

    /**
     * Convert macro element to content blocks
     *
     * @param DOMElement $macro The <ac:structured-macro> element
     * @param ConversionContext $context Conversion context with helpers
     * @return array<ContentBlock> Array of content blocks
     */
    public function convert(DOMElement $macro, ConversionContext $context): array;
}

/**
 * Conversion context - provides helpers and state during conversion
 */
class ConversionContext {
    private array $imageMapping = [];
    private array $pageMapping = [];
    private array $mediaDownloads = [];

    public function __construct(
        private string $baseUrl = '',
        private ?string $spaceKey = null
    ) {}

    /**
     * Get macro parameter value
     *
     * @param DOMElement $macro Macro element
     * @param string $name Parameter name
     * @return string|null Parameter value or null
     */
    public function getMacroParameter(DOMElement $macro, string $name): ?string {
        $params = $macro->getElementsByTagNameNS('*', 'parameter');
        foreach ($params as $param) {
            /** @var DOMElement $param */
            $paramName = $param->getAttribute('ac:name');
            if ($paramName === $name) {
                return $param->textContent;
            }
        }
        return null;
    }

    /**
     * Get macro body content
     *
     * @param DOMElement $macro Macro element
     * @return string|null Body content or null
     */
    public function getMacroBody(DOMElement $macro): ?string {
        $bodies = $macro->getElementsByTagNameNS('*', 'rich-text-body');
        if ($bodies->length > 0) {
            /** @var DOMElement $body */
            $body = $bodies->item(0);
            return $this->getInnerHtml($body);
        }

        // Try plain-text-body
        $bodies = $macro->getElementsByTagNameNS('*', 'plain-text-body');
        if ($bodies->length > 0) {
            /** @var DOMElement $body */
            $body = $bodies->item(0);
            return $body->textContent;
        }

        return null;
    }

    /**
     * Get inner HTML of element
     *
     * @param DOMElement $element Element
     * @return string HTML
     */
    public function getInnerHtml(DOMElement $element): string {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }
        return $html;
    }

    /**
     * Register image for download
     *
     * @param string $url Image URL
     * @param string $filename Target filename
     */
    public function registerImageDownload(string $url, string $filename): void {
        $this->imageMapping[$url] = $filename;
        $this->mediaDownloads[] = ['url' => $url, 'filename' => $filename];
    }

    /**
     * Get image filename for URL
     *
     * @param string $url Image URL
     * @return string|null Filename or null
     */
    public function getImageFilename(string $url): ?string {
        return $this->imageMapping[$url] ?? null;
    }

    /**
     * Get base URL for Confluence instance
     *
     * @return string Base URL
     */
    public function getBaseUrl(): string {
        return $this->baseUrl;
    }

    /**
     * Get space key
     *
     * @return string|null Space key
     */
    public function getSpaceKey(): ?string {
        return $this->spaceKey;
    }

    /**
     * Get all registered media downloads
     *
     * @return array Media downloads
     */
    public function getMediaDownloads(): array {
        return $this->mediaDownloads;
    }
}
