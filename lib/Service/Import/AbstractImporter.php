<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import;

use Psr\Log\LoggerInterface;

/**
 * Abstract base class for content importers
 *
 * Provides common functionality for importing content from external systems
 * (Confluence, WordPress, SharePoint, etc.) into IntraVox format
 */
abstract class AbstractImporter {
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Parse source content into intermediate format
     *
     * @param mixed $content Source content (string, array, file path, etc.)
     * @return IntermediateFormat Normalized representation
     */
    abstract public function parse($content): IntermediateFormat;

    /**
     * Get the name of the supported format
     *
     * @return string Format name (e.g., 'confluence', 'wordpress', 'html')
     */
    abstract public function getSupportedFormat(): string;

    /**
     * Convert intermediate format to IntraVox export structure
     *
     * @param IntermediateFormat $data Intermediate representation
     * @return array IntraVox export.json structure
     */
    protected function convertToIntraVoxExport(IntermediateFormat $data): array {
        $export = [
            'exportVersion' => '1.0',
            'exportDate' => (new \DateTime())->format('c'),
            'language' => $data->language,
            'navigation' => $data->navigation,
            'footer' => [],
            'pages' => [],
        ];

        foreach ($data->pages as $page) {
            $export['pages'][] = $this->convertPageToIntraVoxFormat($page);
        }

        return $export;
    }

    /**
     * Convert intermediate page to IntraVox page format
     *
     * @param IntermediatePage $page Intermediate page
     * @return array IntraVox page structure
     */
    protected function convertPageToIntraVoxFormat(IntermediatePage $page): array {
        // Use existing uniqueId if available, otherwise generate new one
        $uniqueId = $page->uniqueId ?? ('page-' . $this->generateUuid());

        // Convert content blocks to widgets
        $widgets = [];
        foreach ($page->contentBlocks as $block) {
            $widget = $this->convertBlockToWidget($block);
            if ($widget) {
                $widgets[] = $widget;
            }
        }

        // Build layout structure
        $layout = [
            'columns' => 1,
            'rows' => [
                [
                    'columns' => 1,
                    'backgroundColor' => '',
                    'widgets' => $widgets,
                ]
            ],
            'sideColumns' => [
                'left' => ['enabled' => false, 'backgroundColor' => '', 'widgets' => []],
                'right' => ['enabled' => false, 'backgroundColor' => '', 'widgets' => []],
            ],
            'headerRow' => [
                'enabled' => false,
                'backgroundColor' => '',
                'widgets' => [],
            ],
        ];

        // Determine export path
        $exportPath = $page->slug;
        if ($page->slug === 'home' || $page->slug === '') {
            $exportPath = 'home';
        }

        $pageExport = [
            'uniqueId' => $uniqueId,
            'title' => $page->title,
            'content' => [
                'uniqueId' => $uniqueId,
                'title' => $page->title,
                'language' => $page->language,
                'layout' => $layout,
                '_exportPath' => $exportPath,
                'metadata' => $page->metadata,
            ],
            'comments' => [],
            'pageReactions' => [],
        ];

        // Add parent relationship if exists
        if (!empty($page->parentUniqueId)) {
            $pageExport['parentUniqueId'] = $page->parentUniqueId;
        }

        return $pageExport;
    }

    /**
     * Convert content block to IntraVox widget
     *
     * @param ContentBlock $block Content block
     * @return array|null Widget structure or null if unsupported
     */
    protected function convertBlockToWidget(ContentBlock $block): ?array {
        static $widgetOrder = 0;

        $widget = null;

        switch ($block->getType()) {
            case 'heading':
                /** @var HeadingBlock $block */
                $widget = [
                    'type' => 'heading',
                    'level' => $block->level,
                    'text' => $block->text,
                    'column' => 1,
                    'order' => ++$widgetOrder,
                ];
                break;

            case 'html':
                /** @var HtmlBlock $block */
                $content = $block->content;

                // Wrap with CSS class if specified
                if ($block->cssClass) {
                    $content = sprintf('<div class="%s">%s</div>',
                        htmlspecialchars($block->cssClass, ENT_QUOTES),
                        $content
                    );
                }

                $widget = [
                    'type' => 'text',
                    'content' => $content,
                    'column' => 1,
                    'order' => ++$widgetOrder,
                ];
                break;

            case 'code':
                /** @var CodeBlock $block */
                $codeClass = 'confluence-code-block';
                if ($block->lineNumbers) {
                    $codeClass .= ' line-numbers';
                }

                $langAttr = $block->language ? sprintf(' class="language-%s"',
                    htmlspecialchars($block->language, ENT_QUOTES)) : '';

                $content = sprintf(
                    '<pre class="%s"><code%s>%s</code></pre>',
                    $codeClass,
                    $langAttr,
                    htmlspecialchars($block->code, ENT_QUOTES)
                );

                $widget = [
                    'type' => 'text',
                    'content' => $content,
                    'column' => 1,
                    'order' => ++$widgetOrder,
                ];
                break;

            case 'panel':
                /** @var PanelBlock $block */
                $panelClass = sprintf('confluence-panel confluence-panel-%s', $block->panelType);

                $panelHtml = sprintf('<div class="%s">', $panelClass);

                if ($block->title) {
                    $panelHtml .= sprintf(
                        '<div class="confluence-panel-title">%s</div>',
                        htmlspecialchars($block->title, ENT_QUOTES)
                    );
                }

                $panelHtml .= sprintf(
                    '<div class="confluence-panel-body">%s</div></div>',
                    $block->content
                );

                $widget = [
                    'type' => 'text',
                    'content' => $panelHtml,
                    'column' => 1,
                    'order' => ++$widgetOrder,
                ];
                break;

            case 'image':
                /** @var ImageBlock $block */
                $widget = [
                    'type' => 'image',
                    'src' => $block->filename ?? basename($block->url),
                    'alt' => $block->alt,
                    'objectFit' => 'cover',
                    'objectPosition' => 'center',
                    'column' => 1,
                    'order' => ++$widgetOrder,
                ];
                break;

            case 'divider':
                $widget = [
                    'type' => 'divider',
                    'column' => 1,
                    'order' => ++$widgetOrder,
                ];
                break;

            default:
                $this->logger->warning('Unsupported content block type: ' . $block->getType());
                break;
        }

        return $widget;
    }

    /**
     * Download attachments from URLs
     *
     * @param array<Attachment> $attachments List of attachments
     * @return array Map of URL => local filename
     */
    protected function downloadAttachments(array $attachments): array {
        $mapping = [];

        foreach ($attachments as $attachment) {
            try {
                $localFile = $this->downloadFile($attachment->url, $attachment->filename);
                if ($localFile) {
                    $mapping[$attachment->url] = $localFile;
                }
            } catch (\Exception $e) {
                $this->logger->warning(sprintf(
                    'Failed to download attachment %s: %s',
                    $attachment->filename,
                    $e->getMessage()
                ));
            }
        }

        return $mapping;
    }

    /**
     * Download a single file from URL
     *
     * @param string $url Source URL
     * @param string $targetFilename Target filename
     * @return string|null Local filename or null on failure
     */
    protected function downloadFile(string $url, string $targetFilename): ?string {
        // To be implemented by subclasses or use a shared download service
        return $targetFilename;
    }

    /**
     * Generate a UUID v4
     *
     * @return string UUID
     */
    protected function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Sanitize filename for safe storage
     *
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    protected function sanitizeFilename(string $filename): string {
        // Remove path components
        $filename = basename($filename);

        // Replace unsafe characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250);
            $filename = $name . '.' . $ext;
        }

        return $filename;
    }
}
