<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Folder;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Proves FolderContext resolves folders + language byte-identically to the
 * PageService seams it was lifted from (getIntraVoxFolder / getUserLanguage /
 * getLanguageFolder / resolveEffectiveLanguage / getReadLanguageFolder /
 * getLanguageFolderByCode). This is the empirical gate of clean-target step 1:
 * FolderContext ships UNUSED, and if these assertions cannot hold, the substrate
 * is not cleanly extractable and the plan folds back for one revert.
 *
 * The bodies mirror PageLanguageResolutionTest's pinning of the same seams, now
 * driven through FolderContext's front door instead of a PageService subclass.
 */
class FolderContextSeamTest extends TestCase {

    /** newFolder() codes recorded, to assert the create-on-miss side-effect. */
    private array $created = [];

    private function jsonFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        return $file;
    }

    /** A language folder; $home gives it a loose home.json (real vs _generated). */
    private function langFolder(string $path, ?array $home = null): Folder {
        $children = [];
        if ($home !== null) {
            $children['home.json'] = $this->jsonFile($path . '/home.json', $home);
        }
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new NotFoundException($path . '/' . $p);
        });
        return $folder;
    }

    /** The /IntraVox base folder resolving $languages, recording newFolder(). */
    private function baseFolder(array $languages): Folder {
        $base = $this->createMock(Folder::class);
        $base->method('getName')->willReturn('IntraVox');
        $base->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('nodeExists')->willReturnCallback(fn($n) => isset($languages[$n]));
        $base->method('get')->willReturnCallback(function ($code) use ($languages) {
            if (isset($languages[$code])) {
                return $languages[$code];
            }
            throw new NotFoundException('/IntraVox/' . $code);
        });
        $base->method('newFolder')->willReturnCallback(function ($code) {
            $this->created[] = $code;
            return $this->langFolder('/IntraVox/' . $code);
        });
        return $base;
    }

    /**
     * Build a FolderContext whose intraVox() resolves $base, with $userLangValue
     * as the profile language (null = logged out). The real-content probe treats
     * a folder as "real" when it has a home.json without _generated.
     */
    private function context(
        Folder $base,
        ?string $userLangValue = 'en',
        string $primaryLanguage = 'en'
    ): FolderContext {
        $loggedIn = $userLangValue !== null;

        // The getIntraVoxFolder seam closure: mirrors PageService — throws when
        // logged out, else returns the mounted base folder.
        $intraVox = function () use ($loggedIn, $base) {
            if (!$loggedIn) {
                throw new \Exception('User not logged in');
            }
            return $base;
        };

        // The getUserLanguage seam closure: default when logged out, else the
        // base code of the profile value (via the real LanguageResolver).
        $resolver = new LanguageResolver();
        $userLanguage = fn(): string => $loggedIn ? $resolver->baseLanguageCode($userLangValue) : 'en';

        $primary = fn(): string => $primaryLanguage;

        // The #75 real-content probe, mirroring languageFolderHasRealContent: a
        // loose home.json with a title and no _generated flag counts as real.
        $hasRealContent = function (Folder $folder): bool {
            try {
                if (!$folder->nodeExists('home.json')) {
                    return false;
                }
                $data = json_decode($folder->get('home.json')->getContent(), true);
                return is_array($data) && isset($data['title']) && empty($data['_generated']);
            } catch (\Throwable $e) {
                return false;
            }
        };

        $locator = new PageLocator(
            $this->createMock(PageIndexService::class),
            $this->createMock(LoggerInterface::class)
        );

        return new FolderContext(
            $intraVox,
            $userLanguage,
            $primary,
            $hasRealContent,
            $resolver,
            $locator
        );
    }

    // ---------------------------------------------------------------- intraVox

    public function testLoggedOutIntraVoxThrows(): void {
        $ctx = $this->context($this->baseFolder([]), userLangValue: null);
        $this->expectException(\Exception::class);
        $ctx->intraVox();
    }

    public function testIntraVoxResolvesTheMountedFolder(): void {
        $base = $this->baseFolder([]);
        $ctx = $this->context($base);
        $this->assertSame($base, $ctx->intraVox());
    }

    // ---------------------------------------------------------------- userLanguage

    public function testUserLanguageLoggedOutIsDefault(): void {
        $ctx = $this->context($this->baseFolder([]), userLangValue: null);
        $this->assertSame('en', $ctx->userLanguage());
    }

    public function testUserLanguageExtractsBaseCode(): void {
        $ctx = $this->context($this->baseFolder([]), userLangValue: 'nl_NL');
        $this->assertSame('nl', $ctx->userLanguage());
    }

    public function testUserLanguageMalformedFallsBack(): void {
        $ctx = $this->context($this->baseFolder([]), userLangValue: 'NL');
        $this->assertSame('en', $ctx->userLanguage());
    }

    // ---------------------------------------------------------------- languageFolderByCode (create-on-miss)

    public function testLanguageFolderByCodeExistingReused(): void {
        $nl = $this->langFolder('/IntraVox/nl');
        $ctx = $this->context($this->baseFolder(['nl' => $nl]));
        $this->assertSame($nl, $ctx->languageFolderByCode('nl'));
        $this->assertSame([], $this->created);
    }

    public function testLanguageFolderByCodeMissingCreatesDefault(): void {
        $ctx = $this->context($this->baseFolder([]));
        $ctx->languageFolderByCode('nl');
        $this->assertSame(['en'], $this->created, 'missing lang + missing default creates the default');
    }

    // ---------------------------------------------------------------- effectiveLanguage (#75)

    public function testEffectiveLanguageIsUserLanguageWithRealContent(): void {
        $nl = $this->langFolder('/IntraVox/nl', ['uniqueId' => 'page-home', 'title' => 'Welkom']);
        $ctx = $this->context($this->baseFolder(['nl' => $nl]), userLangValue: 'nl', primaryLanguage: 'nl');
        $this->assertSame('nl', $ctx->effectiveLanguage());
    }

    public function testEffectiveLanguageFallsToPrimaryThenEnglish(): void {
        // user nl (placeholder only) -> primary de (real) wins.
        $nl = $this->langFolder('/IntraVox/nl', ['title' => 'PH', '_generated' => true]);
        $de = $this->langFolder('/IntraVox/de', ['title' => 'Willkommen']);
        $ctx = $this->context($this->baseFolder(['nl' => $nl, 'de' => $de]), userLangValue: 'nl', primaryLanguage: 'de');
        $this->assertSame('de', $ctx->effectiveLanguage());
    }

    public function testEffectiveLanguageNullWhenNothingReal(): void {
        $nl = $this->langFolder('/IntraVox/nl', ['title' => 'PH', '_generated' => true]);
        $ctx = $this->context($this->baseFolder(['nl' => $nl]), userLangValue: 'nl', primaryLanguage: 'nl');
        $this->assertNull($ctx->effectiveLanguage());
    }

    // ---------------------------------------------------------------- readLanguageFolder

    public function testReadLanguageFolderReturnsResolved(): void {
        $nl = $this->langFolder('/IntraVox/nl', ['title' => 'Welkom']);
        $ctx = $this->context($this->baseFolder(['nl' => $nl]), userLangValue: 'nl', primaryLanguage: 'nl');
        $this->assertSame($nl, $ctx->readLanguageFolder());
    }

    public function testReadLanguageFolderFallsBackToWriteTarget(): void {
        // Nothing real resolves -> falls back to the write-target (nl exists).
        $nl = $this->langFolder('/IntraVox/nl', ['title' => 'PH', '_generated' => true]);
        $ctx = $this->context($this->baseFolder(['nl' => $nl]), userLangValue: 'nl', primaryLanguage: 'nl');
        $this->assertSame($nl, $ctx->readLanguageFolder());
        $this->assertSame([], $this->created, 'nl exists — fallback must not create');
    }
}
