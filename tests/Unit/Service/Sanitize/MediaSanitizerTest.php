<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Sanitize;

use OCA\IntraVox\Service\Sanitize\MediaSanitizer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MediaSanitizerTest extends TestCase {
    private MediaSanitizer $sanitizer;

    protected function setUp(): void {
        parent::setUp();
        $this->sanitizer = new MediaSanitizer($this->createMock(LoggerInterface::class));
    }

    // ---------- sanitizeFilename ----------

    public function testFilenameKeepsAlphanumeric(): void {
        $this->assertSame('foo_bar.png', $this->sanitizer->sanitizeFilename('foo_bar.png'));
    }

    public function testFilenameReplacesSpecialChars(): void {
        $this->assertSame('hello_world.jpg', $this->sanitizer->sanitizeFilename('hello world!.jpg'));
    }

    public function testFilenameCollapsesMultipleUnderscores(): void {
        $this->assertSame('a_b.png', $this->sanitizer->sanitizeFilename('a   b.png'));
    }

    public function testFilenameTrimsLeadingUnderscores(): void {
        // Leading "..." → "___" → trimmed away.
        $output = $this->sanitizer->sanitizeFilename('...foo.png');
        $this->assertSame('foo.png', $output);
    }

    public function testFilenameRejectsDisallowedExtensionByDefault(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->sanitizer->sanitizeFilename('virus.exe');
    }

    public function testFilenameAllowsAnyExtensionWhenValidationOff(): void {
        $this->assertSame('virus.exe', $this->sanitizer->sanitizeFilename('virus.exe', false));
    }

    public function testFilenameReplacesWindowsReservedName(): void {
        $output = $this->sanitizer->sanitizeFilename('con.png');
        $this->assertNotSame('con.png', $output);
        $this->assertStringEndsWith('.png', $output);
        $this->assertStringStartsWith('file_', $output);
    }

    public function testFilenameWindowsReservedIsCaseInsensitive(): void {
        $output = $this->sanitizer->sanitizeFilename('PRN.jpg');
        $this->assertStringStartsWith('file_', $output);
    }

    public function testFilenameTruncatesLongInputToFilesystemLimit(): void {
        $long = str_repeat('a', 400) . '.png';
        $output = $this->sanitizer->sanitizeFilename($long);
        $this->assertLessThanOrEqual(255, strlen($output));
        $this->assertStringEndsWith('.png', $output);
    }

    public function testFilenameEmptyBaseGetsFallback(): void {
        $output = $this->sanitizer->sanitizeFilename('___.png');
        $this->assertStringStartsWith('file_', $output);
        $this->assertStringEndsWith('.png', $output);
    }

    public function testFilenameLowercasesExtensionInValidation(): void {
        $this->assertSame('image.png', $this->sanitizer->sanitizeFilename('image.PNG'));
    }

    // ---------- sanitizeFilename: unicode is a letter, not a special char (#101) ----------

    /**
     * An uploaded name keeps its letters, whatever alphabet they are in.
     *
     * The rule was `[^a-zA-Z0-9_\-]`, which treated every accented letter as
     * punctuation: "Übersicht.png" was stored as "bersicht.png" and "Öl.png"
     * as "l.png". The file worked, but under a name its owner never chose.
     *
     * @dataProvider unicodeFilenames
     */
    public function testFilenameKeepsUnicodeLetters(string $input, string $expected, string $why): void {
        $this->assertSame($expected, $this->sanitizer->sanitizeFilename($input), $why);
    }

    public static function unicodeFilenames(): array {
        return [
            'leading umlaut'  => ['Übersicht.png', 'Übersicht.png', 'lost its first letter entirely'],
            'inner umlaut'    => ['Größe.jpg', 'Größe.jpg', 'ß and ö are letters'],
            'two-letter name' => ['Öl.png', 'Öl.png', 'was reduced to "l"'],
            'umlauts + space' => ['Über uns.png', 'Über_uns.png', 'space still becomes an underscore'],
            'reported name'   => ['image with äüö.png', 'image_with_äüö.png', 'the filename from issue #101'],
            'french accent'   => ['café.jpg', 'café.jpg', 'matches what the serving path now accepts'],
            'cyrillic'        => ['изображение.png', 'изображение.png', 'collapsed to a uniqid fallback'],
            'cjk'             => ['写真.jpg', '写真.jpg', 'collapsed to a uniqid fallback'],
            'digits kept'     => ['Foto 2026.jpg', 'Foto_2026.jpg', 'digits were never the problem'],
        ];
    }

    /**
     * Widening the letter rule must not widen the separator rule with it.
     *
     * These are the characters the ASCII floor used to stop as a side effect,
     * and the reason the replacement is an allowlist of \p{L}\p{N}_- rather
     * than "anything printable".
     *
     * @dataProvider dangerousFilenames
     */
    public function testFilenameStillStripsPathAndShellCharacters(string $input, string $forbidden, string $why): void {
        $output = $this->sanitizer->sanitizeFilename($input);
        $this->assertStringNotContainsString($forbidden, $output, $why);
    }

    public static function dangerousFilenames(): array {
        return [
            'forward slash'  => ['../etc/passwd.png', '/', 'path separator'],
            'backslash'      => ['..\\windows\\x.png', '\\', 'path separator on the other platform'],
            'null byte'      => ["foto\0.png", "\0", 'truncates downstream checks'],
            'newline'        => ["foto\n.png", "\n", 'splits log lines'],
            'semicolon'      => ['foto;rm -rf.png', ';', 'shell metacharacter'],
            'dollar'         => ['foto$(id).png', '$', 'shell metacharacter'],
            'quote'          => ["foto'.png", "'", 'quoting'],
            'unicode + slash' => ['Über/uns.png', '/', 'unicode does not smuggle a separator'],
        ];
    }

    public function testFilenameRefusesInvalidUtf8(): void {
        // preg_replace with /u returns null here; failing closed beats writing
        // a name the filesystem and the database disagree about.
        $this->expectException(\InvalidArgumentException::class);
        $this->sanitizer->sanitizeFilename("foto\xC3\x28.png");
    }

    public function testFilenameTruncatesMultibyteNameOnACharacterBoundary(): void {
        // 400 two-byte characters is 800 bytes, well past the 255-byte limit,
        // so this only stays valid UTF-8 if the cut respects the boundary.
        $long = str_repeat('ü', 400) . '.png';
        $output = $this->sanitizer->sanitizeFilename($long);

        $this->assertLessThanOrEqual(255, strlen($output));
        $this->assertStringEndsWith('.png', $output);
        $this->assertSame($output, mb_convert_encoding($output, 'UTF-8', 'UTF-8'), 'truncation split a character');
    }

    public function testFilenameStillFallsBackWhenNothingSurvives(): void {
        // Punctuation-only names have no letters to keep, so the fallback that
        // guarded non-latin scripts before is still needed for these.
        $output = $this->sanitizer->sanitizeFilename('!!!.png');
        $this->assertStringStartsWith('file_', $output);
        $this->assertStringEndsWith('.png', $output);
    }

    // ---------- sanitizeSVG ----------

    public function testSvgKeepsCleanContent(): void {
        $clean = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>';
        $output = $this->sanitizer->sanitizeSVG($clean);
        $this->assertStringContainsString('<svg', $output);
        $this->assertStringContainsString('<rect', $output);
    }

    public function testSvgStripsScriptTag(): void {
        $malicious = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $output = $this->sanitizer->sanitizeSVG($malicious);
        $this->assertStringNotContainsString('<script', $output);
        $this->assertStringNotContainsString('alert(1)', $output);
    }

    public function testSvgStripsDoctype(): void {
        $payload = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg"/>';
        $output = $this->sanitizer->sanitizeSVG($payload);
        $this->assertStringNotContainsString('<!DOCTYPE', $output);
    }

    public function testSvgRejectsExternalEntity(): void {
        $this->expectException(\Exception::class);
        $payload = '<?xml version="1.0"?><!ENTITY xxe SYSTEM "file:///etc/passwd"><svg xmlns="http://www.w3.org/2000/svg">&xxe;</svg>';
        $this->sanitizer->sanitizeSVG($payload);
    }

    public function testSvgRejectsEmptyInput(): void {
        $this->expectException(\Exception::class);
        $this->sanitizer->sanitizeSVG('');
    }

    /**
     * Regression: REL-1. The release tarballs shipped without vendor/, so the
     * enshrined\svgSanitize\Sanitizer class was absent on every App Store
     * install. Instantiating a missing class raises an \Error, which is NOT an
     * \Exception — the original `catch (\Exception $e)` let it through and an
     * SVG upload became a fatal instead of a rejected file.
     *
     * The condition is "this class does not exist", which cannot be simulated
     * in-process while the real package is installed. So we run the sanitizer in
     * a subprocess whose autoloader deliberately does not provide the class, and
     * assert it reports a rejection rather than dying on an uncaught \Error.
     *
     * On the pre-fix code (catch \Exception) this subprocess exits FATAL.
     */
    public function testSvgFailsClosedWhenSanitizerDependencyIsUnavailable(): void {
        $mediaSanitizerSource = \dirname(__DIR__, 4) . '/lib/Service/Sanitize/MediaSanitizer.php';
        $this->assertFileExists($mediaSanitizerSource);

        // Load MediaSanitizer WITHOUT enshrined/svg-sanitize on the classpath.
        $script = <<<'PHPSRC'
            <?php
            spl_autoload_register(static function (string $class): void {
                if (str_starts_with($class, 'enshrined\\')) {
                    return; // deliberately unresolvable: mimics a vendor-less package
                }
            });
            interface_exists(\Psr\Log\LoggerInterface::class) || eval(
                'namespace Psr\Log; interface LoggerInterface {'
                . 'public function emergency($m, array $c = []);'
                . 'public function alert($m, array $c = []);'
                . 'public function critical($m, array $c = []);'
                . 'public function error($m, array $c = []);'
                . 'public function warning($m, array $c = []);'
                . 'public function notice($m, array $c = []);'
                . 'public function info($m, array $c = []);'
                . 'public function debug($m, array $c = []);'
                . 'public function log($l, $m, array $c = []);'
                . '}'
            );
            $logger = new class implements \Psr\Log\LoggerInterface {
                public function emergency($m, array $c = []): void {}
                public function alert($m, array $c = []): void {}
                public function critical($m, array $c = []): void {}
                public function error($m, array $c = []): void {}
                public function warning($m, array $c = []): void {}
                public function notice($m, array $c = []): void {}
                public function info($m, array $c = []): void {}
                public function debug($m, array $c = []): void {}
                public function log($l, $m, array $c = []): void {}
            };
            require $argv[1];
            $s = new \OCA\IntraVox\Service\Sanitize\MediaSanitizer($logger);
            try {
                $s->sanitizeSVG('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>');
                echo 'NO_THROW';
            } catch (\Exception $e) {
                echo 'REJECTED';
            }
            PHPSRC;

        $scriptFile = \tempnam(\sys_get_temp_dir(), 'ivsvg') . '.php';
        \file_put_contents($scriptFile, $script);

        try {
            $cmd = \escapeshellarg(PHP_BINARY) . ' ' . \escapeshellarg($scriptFile)
                . ' ' . \escapeshellarg($mediaSanitizerSource) . ' 2>&1';
            $output = (string)\shell_exec($cmd);
        } finally {
            @\unlink($scriptFile);
        }

        $this->assertStringNotContainsString(
            'Fatal error',
            $output,
            'A missing svg-sanitize dependency must not escape as a fatal; it must be a rejected upload.'
        );
        $this->assertStringContainsString(
            'REJECTED',
            $output,
            'sanitizeSVG must fail closed when the sanitizer dependency is unavailable.'
        );
    }

    // ---------- validateImageFile ----------

    public function testValidateImageAcceptsRealPng(): void {
        $tmp = $this->writeRealPng();
        try {
            $this->sanitizer->validateImageFile($tmp, 'image/png');
            $this->expectNotToPerformAssertions();
        } finally {
            unlink($tmp);
        }
    }

    public function testValidateImageRejectsMimeMismatch(): void {
        $tmp = $this->writeRealPng();
        try {
            $this->expectException(\InvalidArgumentException::class);
            // We hand it the same file but claim it's a JPEG.
            $this->sanitizer->validateImageFile($tmp, 'image/jpeg');
        } finally {
            unlink($tmp);
        }
    }

    public function testValidateImageRejectsNonImageFile(): void {
        $tmp = tempnam(sys_get_temp_dir(), 'iv-not-image-');
        file_put_contents($tmp, 'plain text, definitely not an image');
        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->sanitizer->validateImageFile($tmp, 'image/png');
        } finally {
            unlink($tmp);
        }
    }

    // ---------- validateImageFile: display metadata (dimensions + colour) ----------

    public function testValidateImageReturnsDimensions(): void {
        if (!function_exists('imagejpeg')) {
            $this->markTestSkipped('GD not available');
        }
        $tmp = $this->writeSolidJpeg(300, 200, 20, 40, 60);
        try {
            $meta = $this->sanitizer->validateImageFile($tmp, 'image/jpeg');
            $this->assertSame(300, $meta['width']);
            $this->assertSame(200, $meta['height']);
        } finally {
            unlink($tmp);
        }
    }

    public function testValidateImageReturnsDominantColourOfASolidImage(): void {
        if (!function_exists('imagejpeg')) {
            $this->markTestSkipped('GD not available');
        }
        // A solid colour must average back to (near) itself. JPEG is lossy, so
        // allow a small per-channel tolerance rather than asserting exact hex.
        $tmp = $this->writeSolidJpeg(64, 64, 200, 100, 50);
        try {
            $meta = $this->sanitizer->validateImageFile($tmp, 'image/jpeg');
            $this->assertNotNull($meta['dominantColor']);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $meta['dominantColor']);
            [$r, $g, $b] = sscanf($meta['dominantColor'], '#%02x%02x%02x');
            $this->assertEqualsWithDelta(200, $r, 12, 'red channel');
            $this->assertEqualsWithDelta(100, $g, 12, 'green channel');
            $this->assertEqualsWithDelta(50, $b, 12, 'blue channel');
        } finally {
            unlink($tmp);
        }
    }

    public function testValidateImageCorrectsExifOrientationForRotatedPhotos(): void {
        if (!function_exists('imagejpeg') || !function_exists('exif_read_data')) {
            $this->markTestSkipped('GD or EXIF not available');
        }
        // A landscape 400x200 sensor image flagged orientation=6 (rotate 90° CW)
        // DISPLAYS as 200x400 portrait. The reserved box must match the display,
        // not the raw sensor dims, or the aspect-ratio is 90° wrong.
        $tmp = $this->writeSolidJpeg(400, 200, 10, 10, 10, 6);
        try {
            $meta = $this->sanitizer->validateImageFile($tmp, 'image/jpeg');
            $this->assertSame(200, $meta['width'], 'width/height swapped for orientation 6');
            $this->assertSame(400, $meta['height']);
        } finally {
            unlink($tmp);
        }
    }

    public function testValidateImageStillReturnsDimensionsWhenColourIsSkipped(): void {
        // A PNG (no EXIF path) with a tiny valid raster still yields dimensions;
        // dominantColor is best-effort and may be present, but width/height are
        // always there — that is what the layout-shift fix depends on.
        $tmp = $this->writeRealPng();
        try {
            $meta = $this->sanitizer->validateImageFile($tmp, 'image/png');
            $this->assertSame(1, $meta['width']);
            $this->assertSame(1, $meta['height']);
            $this->assertArrayHasKey('dominantColor', $meta);
        } finally {
            unlink($tmp);
        }
    }

    /**
     * Write a minimal but valid 1x1 transparent PNG to a temp file.
     */
    private function writeRealPng(): string {
        $tmp = tempnam(sys_get_temp_dir(), 'iv-png-');
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk' .
            'YAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        );
        file_put_contents($tmp, $png);
        return $tmp;
    }

    /**
     * A solid-colour JPEG of the given size, optionally with an EXIF Orientation
     * tag, for the dimension/colour/orientation tests.
     */
    private function writeSolidJpeg(int $w, int $h, int $r, int $g, int $b, ?int $orientation = null): string {
        $tmp = tempnam(sys_get_temp_dir(), 'iv-jpg-') . '.jpg';
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w - 1, $h - 1, imagecolorallocate($img, $r, $g, $b));
        imagejpeg($img, $tmp, 92);
        imagedestroy($img);
        if ($orientation !== null) {
            $this->injectExifOrientation($tmp, $orientation);
        }
        return $tmp;
    }

    /**
     * Splice a minimal EXIF APP1 segment carrying only an Orientation tag into a
     * JPEG right after SOI, so exif_read_data() reports the given orientation.
     * Keeps the test self-contained (no image with baked-in EXIF committed).
     */
    private function injectExifOrientation(string $file, int $orientation): void {
        $jpeg = file_get_contents($file);
        // TIFF header (little-endian) + 1 IFD entry: tag 0x0112 (Orientation),
        // type 3 (SHORT), count 1, value = $orientation, then next-IFD = 0.
        $tiff = "II\x2a\x00\x08\x00\x00\x00"
            . "\x01\x00"                                   // 1 IFD entry
            . "\x12\x01\x03\x00\x01\x00\x00\x00"           // tag 0x0112, SHORT, count 1
            . pack('v', $orientation) . "\x00\x00"          // value (2 bytes) + pad
            . "\x00\x00\x00\x00";                            // next IFD offset = 0
        $exif = "Exif\x00\x00" . $tiff;
        $app1 = "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif;
        // Insert APP1 immediately after the SOI marker (first 2 bytes 0xFFD8).
        $spliced = substr($jpeg, 0, 2) . $app1 . substr($jpeg, 2);
        file_put_contents($file, $spliced);
    }
}
