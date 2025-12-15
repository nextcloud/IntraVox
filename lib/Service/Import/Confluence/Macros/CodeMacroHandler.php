<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import\Confluence\Macros;

use DOMElement;
use OCA\IntraVox\Service\Import\ContentBlock;
use OCA\IntraVox\Service\Import\CodeBlock;

/**
 * Handler for Confluence code macro
 *
 * Converts: <ac:structured-macro ac:name="code">
 * To: CodeBlock with syntax highlighting
 */
class CodeMacroHandler implements MacroHandlerInterface {
    public function supports(string $macroName): bool {
        return $macroName === 'code';
    }

    public function convert(DOMElement $macro, ConversionContext $context): array {
        // Get code content from plain-text-body
        $code = $context->getMacroBody($macro);

        if ($code === null || trim($code) === '') {
            return [];
        }

        // Get language parameter
        $language = $context->getMacroParameter($macro, 'language');

        // Normalize language name (Confluence uses different names than Prism/highlight.js)
        $language = $this->normalizeLanguage($language);

        // Get line numbers parameter
        $linenumbersParam = $context->getMacroParameter($macro, 'linenumbers');
        $lineNumbers = $linenumbersParam === 'true';

        return [
            new CodeBlock($code, $language, $lineNumbers)
        ];
    }

    /**
     * Normalize language name for syntax highlighting
     *
     * @param string|null $confluenceLanguage Confluence language name
     * @return string|null Normalized language name
     */
    private function normalizeLanguage(?string $confluenceLanguage): ?string {
        if (empty($confluenceLanguage)) {
            return null;
        }

        // Confluence language name mappings to common syntax highlighter names
        $mapping = [
            'actionscript3' => 'actionscript',
            'bash' => 'bash',
            'c#' => 'csharp',
            'c++' => 'cpp',
            'coldfusion' => 'cfm',
            'css' => 'css',
            'delphi' => 'pascal',
            'diff' => 'diff',
            'erlang' => 'erlang',
            'groovy' => 'groovy',
            'html' => 'html',
            'html/xml' => 'html',
            'java' => 'java',
            'javafx' => 'java',
            'javascript' => 'javascript',
            'js' => 'javascript',
            'json' => 'json',
            'perl' => 'perl',
            'php' => 'php',
            'powershell' => 'powershell',
            'python' => 'python',
            'ruby' => 'ruby',
            'sass' => 'sass',
            'scala' => 'scala',
            'sql' => 'sql',
            'vb' => 'vbnet',
            'xml' => 'xml',
            'yaml' => 'yaml',
        ];

        $lang = strtolower($confluenceLanguage);
        return $mapping[$lang] ?? $lang;
    }
}
