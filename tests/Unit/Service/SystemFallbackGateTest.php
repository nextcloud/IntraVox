<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\SetupService;
use OCA\IntraVox\Service\SystemFileService;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\ICacheFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * SystemFileService reads navigation.json / footer.json in SYSTEM context and
 * therefore ignores ACLs on purpose: a user with department-only access has no
 * read right on the language root, and must still get a menu and a footer.
 *
 * That fallback is correct for exactly one situation -- the user cannot reach
 * the PARENT FOLDER. It used to also fire for a second one that arrives as the
 * very same exception: an admin put an explicit deny on the FILE. IntraVox then
 * served the file the admin had just forbidden, page titles and all (#112).
 *
 * mayUseSystemFallback() tells the two apart from the USER's view. These lock
 * both directions down: reinstating the bypass would re-open the leak, and
 * removing it altogether would blank out the menu for department-only users --
 * the reason it exists.
 */
class SystemFallbackGateTest extends TestCase {
    private function service(IRootFolder $rootFolder): SystemFileService {
        $cacheFactory = $this->createMock(ICacheFactory::class);
        $cacheFactory->method('isAvailable')->willReturn(false);

        return new SystemFileService(
            $rootFolder,
            $this->createMock(SetupService::class),
            new NullLogger(),
            $this->createMock(LanguageService::class),
            $cacheFactory
        );
    }

    /**
     * A root folder whose IntraVox/<lang> lookup behaves as $behaviour dictates.
     *
     * $hasMount is what the user's own view says about the IntraVox folder
     * itself. It defaults to true because every case this helper builds is a
     * user who IS in IntraVox — a department-only user, or one facing a deny
     * on a single file. A user with no mount at all is the separate case
     * below, and it must not be reachable by accident here.
     */
    private function rootFolderWhereLanguageFolder(callable $behaviour, bool $hasMount = true): IRootFolder {
        $userFolder = $this->createMock(Folder::class);
        $userFolder->method('nodeExists')->willReturnCallback(
            fn(string $path): bool => $path === 'IntraVox' ? $hasMount : false
        );
        $userFolder->method('get')->willReturnCallback($behaviour);

        $rootFolder = $this->createMock(IRootFolder::class);
        $rootFolder->method('getUserFolder')->willReturn($userFolder);

        return $rootFolder;
    }

    public function testFallbackIsAllowedWhenTheLanguageFolderIsUnreachable(): void {
        $rootFolder = $this->rootFolderWhereLanguageFolder(
            fn() => throw new NotFoundException('no access to the language root')
        );

        $this->assertTrue(
            $this->service($rootFolder)->mayUseSystemFallback('bob', 'nl', 'navigation.json'),
            'this is the department-only user the fallback exists for'
        );
    }

    public function testFallbackIsRefusedWhenTheUserCanSeeTheLanguageFolder(): void {
        $languageFolder = $this->createMock(Folder::class);
        // The file is denied, so it is not visible in the user's own view.
        $languageFolder->method('nodeExists')->willReturn(false);

        $rootFolder = $this->rootFolderWhereLanguageFolder(fn() => $languageFolder);

        $this->assertFalse(
            $this->service($rootFolder)->mayUseSystemFallback('bob', 'nl', 'navigation.json'),
            'the user reaches the folder, so a file they cannot see there is a deliberate deny (#112)'
        );
    }

    public function testFallbackIsRefusedWhenTheUserCanReadTheFileThemselves(): void {
        $languageFolder = $this->createMock(Folder::class);
        $languageFolder->method('nodeExists')->willReturn(true);

        $rootFolder = $this->rootFolderWhereLanguageFolder(fn() => $languageFolder);

        $this->assertFalse(
            $this->service($rootFolder)->mayUseSystemFallback('bob', 'nl', 'footer.json'),
            'no bypass is needed for a file the user can already read'
        );
    }

    public function testFilesOutsideTheAllowlistAreNeverServedInSystemContext(): void {
        $rootFolder = $this->rootFolderWhereLanguageFolder(fn() => $this->createMock(Folder::class));

        $this->assertFalse(
            $this->service($rootFolder)->mayUseSystemFallback('bob', 'nl', 'secrets.json'),
            'the ACL bypass is limited to the shared infrastructure files'
        );
    }

    /**
     * The leak this test exists for: a user with NO access to IntraVox at all.
     *
     * "Language folder unreachable" was read as "department-only user", but it
     * covers a second case that looks identical from inside the try block —
     * someone who is in no IntraVox group whatsoever. Found on a live install:
     * a demo user in two unrelated groups, with zero permissions on every path
     * checked, was served the complete navigation tree by the fallback: seven
     * top-level items with their titles, the department structure beneath them
     * and every page id.
     *
     * The UI showed her one entry, because filterNavigation() drops what she
     * cannot read — but the filtering happens after the fetch, so the titles
     * and ids had already crossed the wire. Content stayed closed; the shape
     * of the intranet did not.
     */
    public function testFallbackIsRefusedWhenTheUserHasNoIntraVoxAtAll(): void {
        $rootFolder = $this->rootFolderWhereLanguageFolder(
            fn() => throw new NotFoundException('no access to the language root'),
            false // no IntraVox mount in this user's view
        );

        $this->assertFalse(
            $this->service($rootFolder)->mayUseSystemFallback('rachel', 'nl', 'navigation.json'),
            'a user outside every IntraVox group has no foothold to fall back on'
        );
    }

    /**
     * The other direction, and the reason the check is `nodeExists` on the
     * mount rather than something stricter: a department-only user DOES see
     * the IntraVox folder — only the language root is denied — and must keep
     * their menu. Removing the fallback for them is the regression this guards.
     */
    public function testDepartmentOnlyUserStillGetsTheFallback(): void {
        $rootFolder = $this->rootFolderWhereLanguageFolder(
            fn() => throw new NotFoundException('no access to the language root'),
            true // the mount is there; only the language folder is out of reach
        );

        $this->assertTrue(
            $this->service($rootFolder)->mayUseSystemFallback('bob', 'nl', 'navigation.json'),
            'the department-only user is exactly who the fallback is for'
        );
    }

    public function testAnUnexpectedFailureKeepsTheMenuWorking(): void {
        $rootFolder = $this->rootFolderWhereLanguageFolder(
            fn() => throw new \RuntimeException('storage hiccup')
        );

        $this->assertTrue(
            $this->service($rootFolder)->mayUseSystemFallback('bob', 'nl', 'navigation.json'),
            'navigation is infrastructure: a broken check must not blank out everyone\'s menu'
        );
    }
}
