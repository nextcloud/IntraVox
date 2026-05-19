<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Sanitize;

use enshrined\svgSanitize\Sanitizer;
use Psr\Log\LoggerInterface;

/**
 * Sanitize media uploads (filenames + SVG + image-header validation).
 *
 * Mirrors the security-critical helpers that used to live in PageService.
 * Keeping them in a dedicated service makes the rules auditable in
 * isolation — important for enterprise security reviews that scrutinize
 * upload paths separately from page-rendering logic.
 */
final class MediaSanitizer {
    public const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'mp4', 'webm', 'ogg',
    ];

    /**
     * Windows-reserved device names — refused on every platform to avoid
     * surprise when files are downloaded onto Windows hosts.
     *
     * @var array<int, string>
     */
    private const WINDOWS_RESERVED_BASENAMES = [
        'con', 'prn', 'aux', 'nul',
        'com1', 'com2', 'com3', 'com4', 'com5', 'com6', 'com7', 'com8', 'com9',
        'lpt1', 'lpt2', 'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9',
    ];

    /**
     * Patterns that should never survive svg-sanitize. We re-scan after the
     * library does its work because some bypasses live in obscure XML
     * constructs the upstream allowlist still permits.
     *
     * @var array<int, string>
     */
    private const DANGEROUS_SVG_PATTERNS = [
        '<!ENTITY',
        '<iframe',
        '<embed',
        '<object',
        '<script',
        'javascript:',
        'data:text/html',
        'SYSTEM',
        'PUBLIC',
    ];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Produce a filesystem-safe filename. Strips path separators, control
     * characters and unicode, then re-applies the original extension if it
     * was on the allow list.
     *
     * @throws \InvalidArgumentException when validateExtension is true and
     *         the extension is not in self::ALLOWED_EXTENSIONS
     */
    public function sanitizeFilename(string $filename, bool $validateExtension = true): string {
        $extension = '';
        if (($dotPos = strrpos($filename, '.')) !== false) {
            $ext = strtolower(substr($filename, $dotPos + 1));
            if ($validateExtension && !in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                throw new \InvalidArgumentException(
                    'File extension not allowed: ' . $ext .
                    '. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS)
                );
            }
            $extension = '.' . $ext;
            $filename = substr($filename, 0, $dotPos);
        }

        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        $filename = trim($filename, '_');

        if (in_array(strtolower($filename), self::WINDOWS_RESERVED_BASENAMES, true)) {
            $filename = 'file_' . uniqid();
        }

        $maxLength = 255 - strlen($extension);
        if (strlen($filename) > $maxLength) {
            $filename = substr($filename, 0, $maxLength);
        }

        if ($filename === '') {
            $filename = 'file_' . uniqid();
        }

        return $filename . $extension;
    }

    /**
     * Sanitize raw SVG content. Returns clean SVG markup or throws when the
     * file is malformed or contains content the dangerous-patterns scan
     * catches even after the svg-sanitize allowlist.
     *
     * @throws \Exception
     */
    public function sanitizeSVG(string $svgContent): string {
        try {
            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);

            $cleanSvg = $sanitizer->sanitize($svgContent);
            if ($cleanSvg === false || $cleanSvg === '') {
                throw new \Exception('SVG sanitization failed - file may contain malicious content');
            }

            if (stripos($cleanSvg, '<!DOCTYPE') !== false) {
                throw new \Exception('SVG contains DOCTYPE declaration (not allowed)');
            }

            foreach (self::DANGEROUS_SVG_PATTERNS as $pattern) {
                if (stripos($cleanSvg, $pattern) !== false) {
                    throw new \Exception('SVG contains prohibited content: ' . $pattern);
                }
            }

            return $cleanSvg;
        } catch (\Exception $e) {
            $this->logger->error('SVG sanitization error: ' . $e->getMessage());
            throw new \Exception('Invalid SVG file');
        }
    }

    /**
     * Verify a file actually decodes as the image type its MIME claims.
     * Defends against polyglot uploads (e.g. an HTML file masquerading
     * with an image/jpeg extension+MIME).
     *
     * @throws \InvalidArgumentException when the file fails to decode or
     *         the decoded format does not match the declared MIME
     */
    public function validateImageFile(string $tmpFile, string $detectedMime): void {
        $imageInfo = @getimagesize($tmpFile);
        if ($imageInfo === false) {
            throw new \InvalidArgumentException('File appears to be an invalid or corrupted image');
        }

        $expectedMime = match ($imageInfo[2]) {
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_GIF => 'image/gif',
            IMAGETYPE_WEBP => 'image/webp',
            default => null,
        };

        if ($expectedMime !== null && $expectedMime !== $detectedMime) {
            $this->logger->warning('Image MIME type mismatch', [
                'detected' => $detectedMime,
                'actual' => $expectedMime,
            ]);
            throw new \InvalidArgumentException(
                'Image file appears to be corrupted or has incorrect extension'
            );
        }
    }
}
