<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import\Confluence\Macros;

use DOMElement;
use OCA\IntraVox\Service\Import\ContentBlock;
use OCA\IntraVox\Service\Import\PanelBlock;

/**
 * Handler for Confluence panel macros (info, note, warning, tip, error)
 *
 * Converts: <ac:structured-macro ac:name="info|note|warning|tip">
 * To: PanelBlock with appropriate styling
 */
class PanelMacroHandler implements MacroHandlerInterface {
    private const SUPPORTED_MACROS = [
        'info',
        'note',
        'warning',
        'tip',
        'error',
        'panel', // Generic panel
    ];

    public function supports(string $macroName): bool {
        return in_array($macroName, self::SUPPORTED_MACROS);
    }

    public function convert(DOMElement $macro, ConversionContext $context): array {
        $macroName = $macro->getAttribute('ac:name');

        // Get panel title (optional parameter)
        $title = $context->getMacroParameter($macro, 'title');

        // Get panel body content
        $body = $context->getMacroBody($macro);

        if (empty($body)) {
            // No content, skip
            return [];
        }

        // Map macro name to panel type
        $panelType = $this->mapPanelType($macroName);

        return [
            new PanelBlock($panelType, $body, $title)
        ];
    }

    /**
     * Map Confluence macro name to panel type
     *
     * @param string $macroName Macro name
     * @return string Panel type for CSS class
     */
    private function mapPanelType(string $macroName): string {
        return match($macroName) {
            'info' => 'info',
            'note' => 'note',
            'warning' => 'warning',
            'tip' => 'tip',
            'error' => 'error',
            'panel' => 'note', // Default generic panel to note style
            default => 'info',
        };
    }
}
