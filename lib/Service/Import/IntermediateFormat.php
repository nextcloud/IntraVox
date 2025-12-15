<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import;

/**
 * Intermediate Format - Normalized representation of imported content
 *
 * Decouples format parsers (Confluence, WordPress, etc.) from IntraVox widget mappers
 */
class IntermediateFormat {
    /** @var array<IntermediatePage> */
    public array $pages = [];

    /** @var array */
    public array $navigation = [];

    /** @var array<MediaDownload> */
    public array $mediaDownloads = [];

    public string $language = 'nl';

    public function addPage(IntermediatePage $page): void {
        $this->pages[] = $page;
    }

    public function addMediaDownload(MediaDownload $media): void {
        $this->mediaDownloads[] = $media;
    }
}

/**
 * Represents a single page in intermediate format
 */
class IntermediatePage {
    public string $title;
    public string $slug;
    public string $language;
    public string $parentSlug = '';
    public ?string $uniqueId = null;
    public ?string $parentUniqueId = null;
    public ?string $sourceFile = null; // Used for hierarchy detection in HTML imports

    /** @var array<ContentBlock> */
    public array $contentBlocks = [];

    /** @var array<Attachment> */
    public array $attachments = [];

    /** @var array */
    public array $metadata = [];

    public function __construct(string $title, string $slug, string $language) {
        $this->title = $title;
        $this->slug = $slug;
        $this->language = $language;
        $this->uniqueId = 'page-' . $this->generateUUID();
    }

    /**
     * Generate RFC 4122 compliant UUID v4
     *
     * @return string UUID in format XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
     */
    private function generateUUID(): string {
        $data = random_bytes(16);

        // Set version to 4 (0100 in binary)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

        // Set variant to RFC 4122 (10 in binary)
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Format as XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function addContentBlock(ContentBlock $block): void {
        $this->contentBlocks[] = $block;
    }

    public function addAttachment(Attachment $attachment): void {
        $this->attachments[] = $attachment;
    }
}

/**
 * Base class for content blocks
 */
abstract class ContentBlock {
    abstract public function getType(): string;
    abstract public function toArray(): array;
}

/**
 * Heading content block
 */
class HeadingBlock extends ContentBlock {
    public int $level;
    public string $text;

    public function __construct(int $level, string $text) {
        $this->level = $level;
        $this->text = $text;
    }

    public function getType(): string {
        return 'heading';
    }

    public function toArray(): array {
        return [
            'type' => 'heading',
            'level' => $this->level,
            'text' => $this->text,
        ];
    }
}

/**
 * HTML content block
 */
class HtmlBlock extends ContentBlock {
    public string $content;
    public ?string $cssClass;

    public function __construct(string $content, ?string $cssClass = null) {
        $this->content = $content;
        $this->cssClass = $cssClass;
    }

    public function getType(): string {
        return 'html';
    }

    public function toArray(): array {
        return [
            'type' => 'html',
            'content' => $this->content,
            'cssClass' => $this->cssClass,
        ];
    }
}

/**
 * Image content block
 */
class ImageBlock extends ContentBlock {
    public string $url;
    public string $alt;
    public ?string $filename;
    public ?string $title;

    public function __construct(string $url, string $alt = '', ?string $filename = null, ?string $title = null) {
        $this->url = $url;
        $this->alt = $alt;
        $this->filename = $filename;
        $this->title = $title;
    }

    public function getType(): string {
        return 'image';
    }

    public function toArray(): array {
        return [
            'type' => 'image',
            'url' => $this->url,
            'alt' => $this->alt,
            'filename' => $this->filename,
            'title' => $this->title,
        ];
    }
}

/**
 * Code block
 */
class CodeBlock extends ContentBlock {
    public string $code;
    public ?string $language;
    public bool $lineNumbers;

    public function __construct(string $code, ?string $language = null, bool $lineNumbers = false) {
        $this->code = $code;
        $this->language = $language;
        $this->lineNumbers = $lineNumbers;
    }

    public function getType(): string {
        return 'code';
    }

    public function toArray(): array {
        return [
            'type' => 'code',
            'code' => $this->code,
            'language' => $this->language,
            'lineNumbers' => $this->lineNumbers,
        ];
    }
}

/**
 * Panel block (info, warning, note, tip)
 */
class PanelBlock extends ContentBlock {
    public string $panelType; // info, note, warning, tip, error
    public string $content;
    public ?string $title;

    public function __construct(string $panelType, string $content, ?string $title = null) {
        $this->panelType = $panelType;
        $this->content = $content;
        $this->title = $title;
    }

    public function getType(): string {
        return 'panel';
    }

    public function toArray(): array {
        return [
            'type' => 'panel',
            'panelType' => $this->panelType,
            'content' => $this->content,
            'title' => $this->title,
        ];
    }
}

/**
 * Divider block
 */
class DividerBlock extends ContentBlock {
    public function getType(): string {
        return 'divider';
    }

    public function toArray(): array {
        return ['type' => 'divider'];
    }
}

/**
 * Attachment reference
 */
class Attachment {
    public string $url;
    public string $filename;
    public ?string $type;
    public ?int $size;

    public function __construct(string $url, string $filename, ?string $type = null, ?int $size = null) {
        $this->url = $url;
        $this->filename = $filename;
        $this->type = $type;
        $this->size = $size;
    }
}

/**
 * Media download task
 */
class MediaDownload {
    public string $url;
    public string $targetFilename;
    public string $pageSlug;

    public function __construct(string $url, string $targetFilename, string $pageSlug) {
        $this->url = $url;
        $this->targetFilename = $targetFilename;
        $this->pageSlug = $pageSlug;
    }
}
