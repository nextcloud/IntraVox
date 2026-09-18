<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\ImportService;
use OCA\IntraVox\Service\Sanitize\MediaSanitizer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * IV-20: the ZIP import writes straight into _media/_resources, bypassing the
 * upload allowlist and SVG sanitiser. safeMediaContent() is the guard that
 * refuses executable/active-document files and sanitises SVG before the write.
 */
class ImportMediaSafetyTest extends TestCase {
    private ImportService $service;

    protected function setUp(): void {
        parent::setUp();

        // Real MediaSanitizer (simple ctor); everything else the ImportService
        // constructor wants is mocked — safeMediaContent only touches the sanitiser.
        $sanitizer = new MediaSanitizer($this->createMock(LoggerInterface::class));

        $ref = new \ReflectionClass(ImportService::class);
        $args = [];
        foreach ($ref->getConstructor()->getParameters() as $p) {
            $type = $p->getType();
            $name = $type instanceof \ReflectionNamedType ? $type->getName() : '';
            if ($name === MediaSanitizer::class) {
                $args[] = $sanitizer;
            } elseif ($name !== '' && !$type->isBuiltin()) {
                // safeMediaContent() only touches MediaSanitizer, so the other
                // collaborators just need to satisfy the type. Some are final and
                // cannot be mocked; build those without running their constructor.
                $dep = new \ReflectionClass($name);
                $args[] = $dep->isFinal()
                    ? $dep->newInstanceWithoutConstructor()
                    : $this->createMock($name);
            } else {
                $args[] = $p->isOptional() ? $p->getDefaultValue() : null;
            }
        }
        $this->service = $ref->newInstanceArgs($args);
    }

    private function safeMediaContent(string $path, string $name): ?string {
        $m = new \ReflectionMethod(ImportService::class, 'safeMediaContent');
        $m->setAccessible(true);
        return $m->invoke($this->service, $path, $name);
    }

    private function tmp(string $name, string $content): string {
        $path = sys_get_temp_dir() . '/iv20-' . uniqid() . '-' . $name;
        file_put_contents($path, $content);
        return $path;
    }

    public function testExecutableAndActiveDocumentExtensionsAreRefused(): void {
        foreach (['evil.php', 'evil.phtml', 'page.html', 'page.htm', 'script.js', 'shell.sh'] as $name) {
            $path = $this->tmp($name, '<?php echo 1; ?>');
            $this->assertNull($this->safeMediaContent($path, $name), "$name must be refused");
            @unlink($path);
        }
    }

    public function testAnExtensionlessFileIsRefused(): void {
        $path = $this->tmp('noext', 'data');
        $this->assertNull($this->safeMediaContent($path, 'noext'));
        @unlink($path);
    }

    public function testOrdinaryImageIsReturnedUnchanged(): void {
        $bytes = "\x89PNG\r\n\x1a\nfake-png-bytes";
        $path = $this->tmp('logo.png', $bytes);
        $this->assertSame($bytes, $this->safeMediaContent($path, 'logo.png'));
        @unlink($path);
    }

    public function testResourceTypesLikeCssAndFontsAreAllowed(): void {
        foreach (['theme.css' => 'body{}', 'font.woff2' => 'wOFF', 'doc.pdf' => '%PDF-1.4'] as $name => $content) {
            $path = $this->tmp($name, $content);
            $this->assertSame($content, $this->safeMediaContent($path, $name), "$name should round-trip");
            @unlink($path);
        }
    }

    public function testSvgIsSanitisedNotWrittenRaw(): void {
        $malicious = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect/></svg>';
        $path = $this->tmp('logo.svg', $malicious);
        $result = $this->safeMediaContent($path, 'logo.svg');
        @unlink($path);

        $this->assertNotNull($result, 'a sanitisable SVG is kept');
        $this->assertStringNotContainsStringIgnoringCase('<script', $result, 'the script must be stripped');
    }
}
