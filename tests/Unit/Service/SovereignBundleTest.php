<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\SovereignBundle;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;

final class SovereignBundleTest extends TestCase {
    public function testPackagedBundleIsPortableAndHashIsStable(): void {
        $apps = $this->createMock(IAppManager::class);
        $apps->method('getAppPath')->with('intravox')->willReturn(dirname(__DIR__, 3));
        $bundle = new SovereignBundle($apps);
        $files = $bundle->files();
        self::assertCount(23, $files);
        self::assertSame($bundle->hash($files), $bundle->hash(array_reverse($files, true)));
        $changed = $files;
        $changed['home.json'] .= "\n";
        self::assertNotSame($bundle->hash($files), $bundle->hash($changed));
        self::assertNotSame($bundle->hash(['ab' => 'c']), $bundle->hash(['a' => 'bc']));
        self::assertSame('page-3429f149-dd7c-492a-8a4a-dab3ff83d511', json_decode($files['home.json'], true)['uniqueId']);
        foreach ($files as $path => $content) {
            self::assertDoesNotMatchRegularExpression('~\.replit\.dev|localhost|__groupfolders/1~i', $content, $path);
        }
    }

    public function testMissingBundleFailsExplicitly(): void {
        $apps = $this->createMock(IAppManager::class);
        $apps->method('getAppPath')->willReturn('/nonexistent-intravox-release');
        $this->expectException(\RuntimeException::class);
        (new SovereignBundle($apps))->files();
    }
}
