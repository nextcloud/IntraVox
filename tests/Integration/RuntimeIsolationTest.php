<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/** The tests may use dev PHPUnit, but real Nextcloud must use host dependencies. */
class RuntimeIsolationTest extends TestCase {
    public function testOcpComesFromTheHostNotDevelopmentStubs(): void {
        $source = (new ReflectionClass(\OCP\IUserManager::class))->getFileName();
        self::assertSame(getenv('NEXTCLOUD_ROOT') . '/lib/public/IUserManager.php', $source);
    }

    public function testDavUsesTheHostsSabreXmlInterface(): void {
        $source = (new ReflectionClass(\Sabre\Xml\XmlSerializable::class))->getFileName();
        self::assertSame(getenv('NEXTCLOUD_ROOT') . '/3rdparty/sabre/xml/lib/XmlSerializable.php', $source);
    }

    public function testAppDependenciesComeFromItsProductionVendor(): void {
        $app = \OCA\IntraVox\Service\AppAssets::appPath();
        self::assertSame(getenv('NEXTCLOUD_ROOT') . '/custom_apps/intravox', $app);
        $source = (new ReflectionClass(\enshrined\svgSanitize\Sanitizer::class))->getFileName();
        self::assertStringStartsWith($app . '/vendor/', $source);
        self::assertDirectoryDoesNotExist($app . '/vendor/nextcloud/ocp');
        self::assertDirectoryDoesNotExist($app . '/vendor/phpunit');
    }
}
