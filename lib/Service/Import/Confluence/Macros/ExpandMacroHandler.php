<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import\Confluence\Macros;

use DOMElement;
use OCA\IntraVox\Service\Import\ContentBlock;
use OCA\IntraVox\Service\Import\HtmlBlock;

/**
 * Handler for Confluence expand macro
 *
 * Converts: <ac:structured-macro ac:name="expand">
 * To: HTML <details>/<summary> element (native HTML5 expand/collapse)
 */
class ExpandMacroHandler implements MacroHandlerInterface {
    public function supports(string $macroName): bool {
        return $macroName === 'expand';
    }

    public function convert(DOMElement $macro, ConversionContext $context): array {
        // Get title parameter (becomes the summary text)
        $title = $context->getMacroParameter($macro, 'title');

        // Default title if not provided
        if (empty($title)) {
            $title = 'Click to expand...';
        }

        // Get body content
        $body = $context->getMacroBody($macro);

        if (empty($body)) {
            return [];
        }

        // Build HTML5 details/summary structure
        $html = sprintf(
            '<details class="confluence-expand"><summary>%s</summary><div class="confluence-expand-content">%s</div></details>',
            htmlspecialchars($title, ENT_QUOTES),
            $body
        );

        return [new HtmlBlock($html, 'confluence-expand')];
    }
}
