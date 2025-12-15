<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import\Confluence\Macros;

use DOMElement;
use OCA\IntraVox\Service\Import\ContentBlock;
use OCA\IntraVox\Service\Import\HtmlBlock;
use Psr\Log\LoggerInterface;

/**
 * Default handler for unsupported Confluence macros
 *
 * Creates a placeholder text block indicating the macro is not supported
 */
class DefaultMacroHandler implements MacroHandlerInterface {
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function supports(string $macroName): bool {
        // This handler supports all macros as a fallback
        return true;
    }

    public function convert(DOMElement $macro, ConversionContext $context): array {
        $macroName = $macro->getAttribute('ac:name');

        $this->logger->warning('Unsupported Confluence macro: ' . $macroName);

        // Try to get body content if available
        $body = $context->getMacroBody($macro);

        // Create placeholder message
        $html = sprintf(
            '<div class="confluence-unsupported-macro"><p><em>⚠️ Unsupported Confluence macro: <code>%s</code></em></p>',
            htmlspecialchars($macroName, ENT_QUOTES)
        );

        // If there's body content, include it as text
        if (!empty($body)) {
            $html .= '<div class="macro-body">' . htmlspecialchars($body, ENT_QUOTES) . '</div>';
        }

        $html .= '</div>';

        return [new HtmlBlock($html)];
    }
}
