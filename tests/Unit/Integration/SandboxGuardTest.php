<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once dirname(__DIR__, 3) . '/scripts/integration/guard.php';

class SandboxGuardTest extends TestCase {
    private string $sandbox;
    private array $environment = [];

    protected function setUp(): void {
        $this->sandbox = dirname(__DIR__, 3) . '/.runtime/integration-' . bin2hex(random_bytes(8));
        mkdir($this->sandbox . '/nextcloud/config', 0700, true);
        mkdir($this->sandbox . '/data', 0700);
        foreach (['INTRAVOX_TEST_SANDBOX', 'INTRAVOX_TEST_TOKEN', 'NEXTCLOUD_ROOT'] as $key) {
            $this->environment[$key] = getenv($key);
        }
        putenv('INTRAVOX_TEST_SANDBOX=' . $this->sandbox);
        putenv('INTRAVOX_TEST_TOKEN=' . str_repeat('a', 64));
        putenv('NEXTCLOUD_ROOT=' . $this->sandbox . '/nextcloud');
        file_put_contents($this->sandbox . '/.disposable', str_repeat('a', 64));
        $this->config();
    }

    protected function tearDown(): void {
        foreach ($this->environment as $key => $value) {
            putenv($value === false ? $key : $key . '=' . $value);
        }
        foreach (['nextcloud/config/config.php', '.disposable', 'linked-root'] as $path) {
            if (file_exists($this->sandbox . '/' . $path) || is_link($this->sandbox . '/' . $path)) {
                unlink($this->sandbox . '/' . $path);
            }
        }
        rmdir($this->sandbox . '/nextcloud/config');
        rmdir($this->sandbox . '/nextcloud');
        rmdir($this->sandbox . '/data');
        rmdir($this->sandbox);
    }

    private function config(array $overrides = []): void {
        file_put_contents($this->sandbox . '/nextcloud/config/config.php', '<?php $CONFIG = '
            . var_export(array_replace([
                'installed' => true, 'dbtype' => 'sqlite3', 'datadirectory' => $this->sandbox . '/data',
            ], $overrides), true) . ';');
    }

    public function testAcceptsOnlyTheMarkedLocalDatabase(): void {
        self::assertSame($this->sandbox . '/nextcloud', \requireDisposableNextcloud());
    }

    public function testRejectsTheOwnerRootBeforeLoadingItsConfiguration(): void {
        putenv('NEXTCLOUD_ROOT=' . dirname(__DIR__, 3) . '/.runtime/nextcloud');
        $this->expectException(RuntimeException::class);
        \requireDisposableNextcloud();
    }

    public function testRejectsAMissingMarker(): void {
        unlink($this->sandbox . '/.disposable');
        $this->expectException(RuntimeException::class);
        \requireDisposableNextcloud();
    }

    public function testRejectsAnotherRunsToken(): void {
        putenv('INTRAVOX_TEST_TOKEN=' . str_repeat('b', 64));
        $this->expectException(RuntimeException::class);
        \requireDisposableNextcloud();
    }

    public function testRejectsAnExternalDatabaseDirectory(): void {
        $this->config(['datadirectory' => dirname(__DIR__, 3) . '/.runtime/data']);
        $this->expectException(RuntimeException::class);
        \requireDisposableNextcloud();
    }

    public function testRejectsANetworkDatabase(): void {
        $this->config(['dbtype' => 'pgsql']);
        $this->expectException(RuntimeException::class);
        \requireDisposableNextcloud();
    }

    public function testRejectsASymlinkedRoot(): void {
        symlink($this->sandbox . '/nextcloud', $this->sandbox . '/linked-root');
        putenv('NEXTCLOUD_ROOT=' . $this->sandbox . '/linked-root');
        $this->expectException(RuntimeException::class);
        \requireDisposableNextcloud();
    }
}
