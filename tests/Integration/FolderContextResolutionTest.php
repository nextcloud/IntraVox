<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Integration;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCP\Files\Folder;

/**
 * FolderContext against a real container, a real session and a real mount —
 * the witness its docblock claimed to have.
 *
 * FolderContext took over three seams from PageService (getIntraVoxFolder,
 * getLanguageFolder, getReadLanguageFolder) and asserted in its own docblock
 * that the result was "byte-identical to the pre-promotion behaviour". Nothing
 * checked that. It was untrue on both paths, and both reached a real server:
 *
 *   1. No registerService factory meant the DI container satisfied the
 *      TEST-ONLY `?Folder $intraVoxOverride` seam with a real Folder — the
 *      user's LazyUserFolder. intraVox() returns the override first, so it
 *      handed back /<uid>/files instead of walking to the mounted IntraVox
 *      groupfolder. Every lookup then read an empty /<uid>/files/<lang>: the
 *      app showed no pages at all, and logged nothing.
 *   2. The user id was captured when the container built the service. An occ
 *      command calls setUser() during execute(), long after that, so the value
 *      stayed empty and every folder lookup threw "User not logged in". Every
 *      occ command that touches pages was dead.
 *
 * Neither is reachable from the unit suite: it injects the override seam, which
 * short-circuits precisely the walk that broke.
 *
 * Honest scope. Only the bug-2 tests are proven regression witnesses: revert the
 * late binding and testContextBuiltWithoutAUserResolvesOnceOneLogsIn fails with
 * the original "User not logged in". The seam assertions are guards, not
 * witnesses — removing the registerService factory on this codebase no longer
 * reproduces bug 1 (the container returns null for the ?Folder seam anyway), so
 * they pin the contract Sam's factory establishes without having been shown to
 * fail on the original defect. Treat them as cheap insurance, not as proof.
 *
 * These tests assert on the DI container, the session and the suite's own
 * throwaway groupfolder rather than on this instance's content, so they hold on
 * any install — including one with no IntraVox groupfolder at all.
 */
class FolderContextResolutionTest extends IntegrationTestCase {

    private function folderContext(): FolderContext {
        return self::server()->get(FolderContext::class);
    }

    /** Read a private property off the container-built service. */
    private function seam(FolderContext $context, string $name): mixed {
        $property = (new \ReflectionClass($context))->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($context);
    }

    /**
     * Bug 1 guard. The container must not fill the test seam.
     *
     * Asserted on the object the DI container actually hands out, because that
     * is where the defect lived — the class itself was fine. Unproven as a
     * witness (see the class docblock): it does not fail when the factory is
     * removed today. It pins the intent so a future signature change that DOES
     * make autowiring bite again is caught here rather than in a browser.
     */
    public function testContainerBuiltContextHasNoOverrideSeam(): void {
        self::assertNull(
            $this->seam($this->folderContext(), 'intraVoxOverride'),
            'the DI container filled the TEST-ONLY intraVoxOverride seam; '
            . 'intraVox() returns it before walking the mount, so every page '
            . 'lookup reads the wrong folder and the app renders empty'
        );
    }

    /** The other two seams are test-only in exactly the same way. */
    public function testContainerBuiltContextHasNoFolderClosureSeams(): void {
        $context = $this->folderContext();

        self::assertNull(
            $this->seam($context, 'readLanguageFolder'),
            'production must run the owned #75 read composition, not an injected closure'
        );
        self::assertNull(
            $this->seam($context, 'languageFolder'),
            'production must run the owned create-on-miss composition'
        );
    }

    /**
     * Bug 1, at the level users feel: the walk must reach the mount itself.
     *
     * Uses the suite's own throwaway groupfolder rather than the real IntraVox
     * one, so this holds on a bare install too. The mount point differs, the
     * mechanism is identical: resolve a groupfolder by name through the user's
     * mounted view, and land on the folder — not on the home directory that
     * contains it.
     *
     * Asserting on the NAME is the whole point, not a detail: /<uid>/files and
     * /<uid>/files/<mount> are both real readable Folders, so any weaker
     * assertion stays green while the app is empty.
     */
    public function testGroupfolderResolvesToTheMountNotTheUserHome(): void {
        $this->actingAs(self::$userId, function (): void {
            $mount = $this->testGroupFolder();

            self::assertInstanceOf(Folder::class, $mount);
            self::assertSame(
                self::$mountPoint,
                $mount->getName(),
                'resolution must land on the mounted groupfolder; returning the '
                . 'user home (/<uid>/files) is the shape of the empty-app bug'
            );
            self::assertStringEndsWith(
                '/files/' . self::$mountPoint,
                rtrim($mount->getPath(), '/'),
                'the mount must sit directly under the user files root'
            );
        });
    }

    /**
     * Bug 2, reproduced the way occ actually hits it.
     *
     * The container-built service is useless as a witness here: by the time
     * phpunit runs, a user is already in session, so its captured id is filled
     * and late binding never has to do anything. A green assertion on THAT
     * object proved nothing — it stayed green with the fix reverted, while
     * `occ intravox:reindex` indexed 0 pages.
     *
     * So build the CLI shape explicitly: a context constructed with NO user, as
     * the DI container does on the command line, handed the session so it can
     * ask later. Then log a user in — which is what Command::execute() does —
     * and require that the mount resolves.
     *
     * With the late binding removed this throws "User not logged in", which is
     * exactly what every occ command did.
     */
    public function testContextBuiltWithoutAUserResolvesOnceOneLogsIn(): void {
        $container = self::server();

        $cliShaped = new FolderContext(
            $container->get(\OCP\Files\IRootFolder::class),
            null, // no user at construction — the CLI case
            $container->get(\OCP\IConfig::class),
            $container->get(\OCA\IntraVox\Service\LanguageService::class),
            $container->get(\OCA\IntraVox\Service\Language\LanguageResolver::class),
            $container->get(\OCA\IntraVox\Service\Locator\PageLocator::class),
            null,
            null,
            null,
            $container->get(\OCP\IUserSession::class),
        );

        $this->actingAs(self::$userId, function () use ($cliShaped): void {
            try {
                $resolved = $cliShaped->intraVox();
                self::assertInstanceOf(Folder::class, $resolved);
            } catch (\Throwable $e) {
                // No IntraVox groupfolder on this instance is fine; being blind
                // to the logged-in user is not.
                self::assertStringNotContainsString(
                    'User not logged in',
                    $e->getMessage(),
                    'a context built without a user must still see one that logs in '
                    . 'afterwards — this is every occ command (reindex, import, '
                    . 'repair-entities), and without it reindex reports 0 pages'
                );
            }
        });
    }

    /**
     * Same late-binding path for the language, where a regression is silent.
     *
     * userLanguage() degrades to 'en' instead of throwing, so a CLI run would
     * quietly read and write the wrong language folder rather than fail.
     */
    public function testContextBuiltWithoutAUserResolvesTheLanguageOnceOneLogsIn(): void {
        $container = self::server();

        $cliShaped = new FolderContext(
            $container->get(\OCP\Files\IRootFolder::class),
            null,
            $container->get(\OCP\IConfig::class),
            $container->get(\OCA\IntraVox\Service\LanguageService::class),
            $container->get(\OCA\IntraVox\Service\Language\LanguageResolver::class),
            $container->get(\OCA\IntraVox\Service\Locator\PageLocator::class),
            null,
            null,
            null,
            $container->get(\OCP\IUserSession::class),
        );

        $expected = $this->actingAs(self::$userId, fn (): string => $this->folderContext()->userLanguage());
        $actual = $this->actingAs(self::$userId, fn (): string => $cliShaped->userLanguage());

        self::assertSame(
            $expected,
            $actual,
            'a context built without a user must resolve the same language as one '
            . 'built with it, once that user is logged in'
        );
    }

    /**
     * Without a user, intraVox() must refuse rather than guess.
     *
     * The late-binding fallback must not decay into "resolve something for
     * whoever happens to be around". With nobody logged in the only correct
     * answer is to throw.
     */
    public function testNoUserStillThrowsRatherThanResolvingSomething(): void {
        $userSession = self::server()->get(\OCP\IUserSession::class);
        $previous = $userSession->getUser();

        $userSession->setUser(null);
        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('User not logged in');
            $this->folderContext()->intraVox();
        } finally {
            $userSession->setUser($previous);
            if ($previous !== null) {
                \OC_Util::setupFS($previous->getUID());
            }
        }
    }
}
