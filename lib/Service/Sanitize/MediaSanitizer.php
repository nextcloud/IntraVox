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
     * Produce a filesystem-safe filename. Strips path separators and control
     * characters, keeps letters and digits in any script, then re-applies the
     * original extension if it was on the allow list.
     *
     * Unicode letters used to be stripped along with everything else: the
     * rule was `[^a-zA-Z0-9_\-]`, so a German intranet uploading
     * "Übersicht.png" got "bersicht.png" back, "Öl.png" became "l.png", and a
     * name written in a non-latin script collapsed to nothing and was handed a
     * "file_<uniqid>" fallback. The file worked, but under a name its owner
     * did not choose and could not search for. Nextcloud itself stores those
     * names without complaint, and after issue #101 the serving path does too,
     * so the ASCII floor here was the last thing enforcing it.
     *
     * What still goes: anything that is not a letter, digit, underscore or
     * hyphen — which is what keeps "/" and "\" (path separators), control
     * characters and shell metacharacters out. \p{L} and \p{N} are matched
     * with the /u flag; invalid UTF-8 makes preg_replace return null, and that
     * is treated as a name we refuse rather than one we repair.
     *
     * @throws \InvalidArgumentException when validateExtension is true and
     *         the extension is not in self::ALLOWED_EXTENSIONS, or when the
     *         filename is not valid UTF-8
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

        $filename = preg_replace('/[^\p{L}\p{N}_\-]/u', '_', $filename);
        if ($filename === null) {
            // Only reachable on invalid UTF-8, which no browser sends and no
            // filesystem should be asked to store.
            throw new \InvalidArgumentException('Filename is not valid UTF-8');
        }
        $filename = preg_replace('/_+/', '_', $filename);
        $filename = trim($filename, '_');

        if (in_array(mb_strtolower($filename), self::WINDOWS_RESERVED_BASENAMES, true)) {
            $filename = 'file_' . uniqid();
        }

        // Bytes, not characters: the 255 limit filesystems impose is a byte
        // limit, and a multi-byte name reaches it in fewer characters. Cutting
        // with mb_strcut rather than substr keeps the truncation from landing
        // mid-character and producing invalid UTF-8.
        $maxLength = 255 - strlen($extension);
        if (strlen($filename) > $maxLength) {
            $filename = mb_strcut($filename, 0, $maxLength);
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
     * Fails closed: any failure inside the sanitizer — including a missing
     * svg-sanitize dependency, which raises an \Error rather than an
     * \Exception — is reported as a rejected upload.
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
        } catch (\Throwable $e) {
            // \Throwable, not \Exception: when vendor/ is missing from the
            // package the Sanitizer class does not exist, and `new Sanitizer()`
            // raises an \Error. Catching only \Exception let that escape as a
            // fatal on every App Store install (see REL-1). Whatever the cause,
            // an SVG we could not sanitize must be refused, never passed through.
            $this->logger->error('SVG sanitization error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw new \Exception('Invalid SVG file');
        }
    }

    /**
     * Above this pixel count we skip the dominant-colour decode: imagecreate-
     * fromstring() loads the whole raster into RAM (~w*h*4 bytes), so a 24MP
     * image is already ~96MB. The metadata is a nicety, never worth OOMing a
     * worker — above the cap the caller still gets width/height (from the cheap
     * getimagesize header read) and simply no colour.
     */
    private const MAX_COLOUR_DECODE_PIXELS = 24_000_000;

    /**
     * Verify a file actually decodes as the image type its MIME claims, and
     * return the display metadata a caller can use to reserve layout space and
     * show a placeholder (issue: late image pop-in / layout shift).
     *
     * Defends against polyglot uploads (e.g. an HTML file masquerading with an
     * image/jpeg extension+MIME) exactly as before — the validation throw is
     * unchanged. The return is additive: the same getimagesize() call that does
     * the polyglot check already knows the pixel dimensions, so harvesting them
     * (plus a one-pixel dominant colour) is nearly free.
     *
     * @return array{width:int,height:int,dominantColor:?string} LOGICAL width/
     *   height (already corrected for EXIF orientation, so a rotated phone photo
     *   reports the dimensions it will actually display at, not the raw sensor
     *   dimensions — a raw-dimension aspect-ratio would reserve a 90°-wrong box
     *   and make the shift WORSE). dominantColor is an '#rrggbb' average, or null
     *   when it cannot be computed cheaply (too large, or decode unsupported).
     *
     * @throws \InvalidArgumentException when the file fails to decode or the
     *         decoded format does not match the declared MIME
     */
    public function validateImageFile(string $tmpFile, string $detectedMime): array {
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

        [$rawWidth, $rawHeight] = [(int)$imageInfo[0], (int)$imageInfo[1]];

        // EXIF orientation: 5–8 swap the axes (the sensor stored it landscape,
        // the camera flags "display rotated 90°"). Only JPEG carries EXIF here.
        $swap = false;
        if ($imageInfo[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmpFile);
            $orientation = is_array($exif) ? (int)($exif['Orientation'] ?? 0) : 0;
            $swap = in_array($orientation, [5, 6, 7, 8], true);
        }
        $width = $swap ? $rawHeight : $rawWidth;
        $height = $swap ? $rawWidth : $rawHeight;

        return [
            'width' => $width,
            'height' => $height,
            'dominantColor' => $this->dominantColor($tmpFile, $rawWidth, $rawHeight),
        ];
    }

    /**
     * The average colour of an image as '#rrggbb', for a placeholder box that
     * shows instantly while the full image loads. Computed by asking GD to
     * downscale the whole image to a single 1×1 pixel — the cheapest possible
     * average. Returns null (no placeholder, the box falls back to a neutral
     * skeleton) rather than risking anything: above the megapixel cap, when GD
     * is absent, or on any decode failure.
     */
    private function dominantColor(string $tmpFile, int $rawWidth, int $rawHeight): ?string {
        if ($rawWidth <= 0 || $rawHeight <= 0) {
            return null;
        }
        if ($rawWidth * $rawHeight > self::MAX_COLOUR_DECODE_PIXELS) {
            return null; // too large to decode safely just for a nicety
        }
        if (!function_exists('imagecreatefromstring') || !function_exists('imagescale')) {
            return null; // GD not available
        }

        $src = null;
        $one = null;
        try {
            $data = @file_get_contents($tmpFile);
            if ($data === false) {
                return null;
            }
            $src = @imagecreatefromstring($data);
            if ($src === false) {
                return null;
            }
            // Downscale to 1×1: GD averages the pixels for us.
            $one = @imagescale($src, 1, 1);
            if ($one === false) {
                return null;
            }
            $rgb = imagecolorat($one, 0, 0);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            return sprintf('#%02x%02x%02x', $r, $g, $b);
        } catch (\Throwable $e) {
            return null;
        } finally {
            if ($src instanceof \GdImage) {
                imagedestroy($src);
            }
            if ($one instanceof \GdImage) {
                imagedestroy($one);
            }
        }
    }
}
