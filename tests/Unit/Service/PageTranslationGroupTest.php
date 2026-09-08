<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * Translation groups: the link between the language versions of one page.
 *
 * Before this, pages in different languages were entirely independent —
 * uniqueId is unique only PER language, and nothing tied the Dutch and German
 * version of a subject together. That is why there could be no switcher, no
 * "also available in German", and no way to ask which languages a page exists
 * in.
 *
 * The model is deliberately symmetric: no source page, no derived translations.
 * Every version is equal, so removing one language shrinks the group rather
 * than orphaning anything — the failure mode that leaves SharePoint's
 * source-pointer model with dangling references and a hanging language menu.
 */
class PageTranslationGroupTest extends TestCase {

    use BuildsPageService;

    /** Content written per path. */
    private array $writes = [];

    protected function setUp(): void {
        $this->writes = [];
    }

    private function makeFile(string $path, array $json, bool $updateable = true): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getId')->willReturn(abs(crc32($path)));
        $file->method('isUpdateable')->willReturn($updateable);
        $file->method('getMTime')->willReturn(1000);
        // Reads reflect earlier writes, so a link followed by a read sees it.
        $file->method('getContent')->willReturnCallback(
            fn() => $this->writes[$path] ?? json_encode($json)
        );
        $file->method('putContent')->willReturnCallback(function ($data) use ($path) {
            $this->writes[$path] = $data;
        });
        return $file;
    }

    private function makeFolder(string $path, array $children): Folder {
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
            if (str_contains($p, '/')) {
                [$head, $rest] = explode('/', $p, 2);
                if (isset($children[$head]) && $children[$head] instanceof Folder) {
                    return $children[$head]->get($rest);
                }
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        return $folder;
    }

    /** Two languages, one page each: nl/over-ons and de/ueber-uns. */
    private function makeService(?array $nlJson = null, ?array $deJson = null, bool $deUpdateable = true): PageService {
        $nlJson ??= ['uniqueId' => 'page-nl', 'title' => 'Over ons'];
        $deJson ??= ['uniqueId' => 'page-de', 'title' => 'Über uns'];

        $nlFile = $this->makeFile('/IntraVox/nl/over-ons.json', $nlJson);
        $deFile = $this->makeFile('/IntraVox/de/ueber-uns.json', $deJson, $deUpdateable);

        $nl = $this->makeFolder('/IntraVox/nl', [
            'over-ons.json' => $nlFile,
            'over-ons' => $this->makeFolder('/IntraVox/nl/over-ons', []),
        ]);
        $de = $this->makeFolder('/IntraVox/de', [
            'ueber-uns.json' => $deFile,
            'ueber-uns' => $this->makeFolder('/IntraVox/de/ueber-uns', []),
        ]);
        $base = $this->makeFolder('/IntraVox', ['nl' => $nl, 'de' => $de]);

        // link/unlinkTranslation resolve via folders()->readLanguageFolder ($nl);
        // updatePage via folders()->languageFolder ($nl) + languageOfFolder/
        // userLanguage; the cross-language locate root ($base) also comes from the
        // injected FolderContext (intraVox).
        $svc = new class() extends PageService {
            public function __construct() {}
            public function clearCache(): void {
            }
        };

        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);

        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('nl');

        // updatePage() needs a session user before it resolves the page.
        $user = $this->createMock(\OCP\IUser::class);
        $user->method('getUID')->willReturn('tester');
        $user->method('getDisplayName')->willReturn('Tester');
        $session = $this->createMock(\OCP\IUserSession::class);
        $session->method('getUser')->willReturn($user);

        $explicit = [
            'userSession' => $session,
            'userId' => 'tester',
            'config' => $config,
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $this->createMock(\OCA\IntraVox\Service\LanguageService::class),
            'pageIndexService' => $index,
            'folderContext' => $this->fakeFolderContext(
                readLanguageFolder: $nl,
                intraVox: $base,
                languageFolder: $nl,
                userLanguage: 'nl'
            ),
        ];
        $this->injectPageServiceDependencies($svc, $explicit);
        return $svc;
    }

    private function writtenGroup(string $path): ?string {
        $data = json_decode($this->writes[$path] ?? '{}', true);
        return $data['translationGroup'] ?? null;
    }

    /** Linking gives both pages one shared group. */
    public function testLinkingSharesOneGroup(): void {
        $svc = $this->makeService();

        $group = $svc->linkTranslation('page-nl', 'page-de');

        $this->assertMatchesRegularExpression('/^tg-[a-f0-9-]{36}$/', $group);
        $this->assertSame($group, $this->writtenGroup('/IntraVox/nl/over-ons.json'));
        $this->assertSame($group, $this->writtenGroup('/IntraVox/de/ueber-uns.json'));
    }

    /**
     * Denial must come before ANY write. The group is adopted from whichever
     * side already has one, so writing A first and only then failing on B
     * would leave A a member of B's existing group — a link created by
     * someone without write access to B. (2.0 audit, finding M2.)
     */
    public function testLinkRefusedBeforeAnyWriteWhenOneSideIsReadOnly(): void {
        $svc = $this->makeService(
            null,
            ['uniqueId' => 'page-de', 'title' => 'Über uns', 'translationGroup' => 'tg-existing'],
            false // de is read-only for this user
        );

        try {
            $svc->linkTranslation('page-nl', 'page-de');
            $this->fail('Expected ForbiddenException');
        } catch (\OCA\IntraVox\Exception\ForbiddenException $e) {
            // expected
        }

        $this->assertSame([], $this->writes, 'nothing may be written when either side is read-only');
    }

    /**
     * Adopting an existing group must not smuggle in a duplicate language.
     * The same-language guard compares only the two pages being linked; the
     * ADOPTED group can hold members neither of them — linking nl-A to en-B
     * whose group already contains nl-C would give the group two Dutch
     * versions, making the reader switcher ambiguous. Found in live data via
     * a screenshot during the 2.0 screenshot round.
     */
    public function testLinkRefusesWhenAdoptedGroupAlreadyHoldsThatLanguage(): void {
        $svc = $this->makeService(
            null,
            // de-side already belongs to a group…
            ['uniqueId' => 'page-de', 'title' => 'Über uns', 'translationGroup' => 'tg-existing']
        );
        // …and that group already contains ANOTHER nl page.
        $mock = $this->createMock(PageIndexService::class);
        $mock->method('findByUniqueId')->willReturn(null);
        $mock->method('findByTranslationGroup')->willReturnCallback(fn($g) => $g === 'tg-existing' ? [
            ['unique_id' => 'page-de', 'language' => 'de'],
            ['unique_id' => 'page-nl-other', 'language' => 'nl'],
        ] : []);
        (new \ReflectionProperty(PageService::class, 'pageIndexService'))->setValue($svc, $mock);

        try {
            $svc->linkTranslation('page-nl', 'page-de');
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('already has a version', $e->getMessage());
        }

        $this->assertSame([], $this->writes, 'refusal must come before any write');
    }

    /**
     * The index is shared by every user, but readability is not: a group
     * member whose folder the caller's mount does not grant must not surface
     * its title, status or uniqueId in the translations list. (2.0 audit,
     * finding M1.)
     */
    public function testResolveTranslationsSkipsRowsTheMountDoesNotGrant(): void {
        $svc = $this->makeService();
        $mock = $this->createMock(PageIndexService::class);
        $mock->method('findByTranslationGroup')->willReturn([
            ['unique_id' => 'page-de', 'language' => 'de', 'title' => 'Über uns',
                'status' => 'published', 'path' => '/IntraVox/de/ueber-uns'],
            // fr/ is not mounted for this user — the ACL-denied case.
            ['unique_id' => 'page-fr', 'language' => 'fr', 'title' => 'Secret FR',
                'status' => 'published', 'path' => '/IntraVox/fr/secret'],
        ]);
        (new \ReflectionProperty(PageService::class, 'pageIndexService'))->setValue($svc, $mock);

        $m = new \ReflectionMethod(PageService::class, 'resolveTranslations');
        $rows = $m->invoke($svc, 'tg-x', 'page-nl');

        $this->assertCount(1, $rows, 'the fr row resolves to no folder on this mount and must be skipped');
        $this->assertSame('page-de', $rows[0]['uniqueId']);
    }

    /**
     * An existing group is adopted rather than replaced, so linking A-B and
     * later B-C leaves all three together instead of splitting into pairs.
     */
    public function testLinkingAdoptsAnExistingGroup(): void {
        $existing = 'tg-11111111-2222-3333-4444-555555555555';
        $svc = $this->makeService(
            ['uniqueId' => 'page-nl', 'title' => 'Over ons', 'translationGroup' => $existing]
        );

        $group = $svc->linkTranslation('page-nl', 'page-de');

        $this->assertSame($existing, $group, 'the existing group must win');
        $this->assertSame($existing, $this->writtenGroup('/IntraVox/de/ueber-uns.json'));
    }

    /**
     * A group holds at most one page per language: a second German version
     * would make "the German page" ambiguous for the switcher and the notice.
     */
    public function testCannotLinkTwoPagesInTheSameLanguage(): void {
        $svc = $this->makeService();

        // Both ids resolve inside nl/ — link must be refused.
        $this->expectException(\InvalidArgumentException::class);
        $svc->linkTranslation('page-nl', 'page-nl');
    }

    /** Unlinking gives the page a fresh group of its own, not none. */
    public function testUnlinkingAssignsAFreshGroup(): void {
        $shared = 'tg-11111111-2222-3333-4444-555555555555';
        $svc = $this->makeService(
            ['uniqueId' => 'page-nl', 'title' => 'Over ons', 'translationGroup' => $shared],
            ['uniqueId' => 'page-de', 'title' => 'Über uns', 'translationGroup' => $shared]
        );

        $fresh = $svc->unlinkTranslation('page-nl');

        $this->assertNotSame($shared, $fresh);
        $this->assertMatchesRegularExpression('/^tg-[a-f0-9-]{36}$/', $fresh);
        $this->assertSame($fresh, $this->writtenGroup('/IntraVox/nl/over-ons.json'));
        // The other page is untouched — unlink acts on one page only.
        $this->assertArrayNotHasKey('/IntraVox/de/ueber-uns.json', $this->writes);
    }

    /** An unknown page is reported as not found. */
    public function testLinkingAnUnknownPageFails(): void {
        $svc = $this->makeService();

        $this->expectException(\OCA\IntraVox\Exception\PageNotFoundException::class);
        $svc->linkTranslation('page-nl', 'page-nope');
    }

    /**
     * The group survives an ordinary save. The sanitiser is a strict
     * whitelist, so without explicit handling every edit would silently drop
     * the field and unlink the page from its translations.
     */
    public function testGroupSurvivesAnOrdinarySave(): void {
        $shared = 'tg-11111111-2222-3333-4444-555555555555';
        $svc = $this->makeService(
            ['uniqueId' => 'page-nl', 'title' => 'Over ons', 'translationGroup' => $shared]
        );

        // A client that knows nothing about translation groups saves the page.
        $svc->updatePage('page-nl', ['title' => 'Over ons (bijgewerkt)', 'widgets' => []]);

        $this->assertSame(
            $shared,
            $this->writtenGroup('/IntraVox/nl/over-ons.json'),
            'an ordinary save must not unlink the page'
        );
    }

    /** A malformed group is dropped rather than stored. */
    public function testMalformedGroupIsRejected(): void {
        $svc = $this->makeService();

        $svc->updatePage('page-nl', [
            'title' => 'Over ons',
            'widgets' => [],
            'translationGroup' => '../../etc/passwd',
        ]);

        $this->assertNull(
            $this->writtenGroup('/IntraVox/nl/over-ons.json'),
            'only well-formed group ids may be stored'
        );
    }


}
