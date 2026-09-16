<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Translation;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Translation\TranslationGroupService;
use OCA\IntraVox\Service\Translation\TranslationQueryService;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsFolderFixtures;
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
 *
 * fase-10: the behaviour was carved off the retired PageService facade onto
 * TranslationQueryService, built DIRECTLY here. link/unlink take the two shared
 * WRITE concerns as per-call closures (the group-writer, shared with the compose
 * domain, and the cache-clear); this test supplies the exact equivalents the
 * TranslationApiController reproduces from its injected collaborators — a
 * writeTranslationGroup that resolves the language via languageOfFolder() (else
 * the caller's own language) and calls TranslationGroupService::writeGroup, and
 * an inert clearCache. Only construction + those two closures change; every
 * migrated assertion is byte-identical to the PageService-era test.
 */
class TranslationQueryServiceTest extends TestCase {

    use BuildsFolderFixtures;

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

    /**
     * Two languages, one page each: nl/over-ons and de/ueber-uns. Returns the
     * FolderContext + a lazily-buildable TranslationQueryService over an index the
     * caller may reset (the query tests inject a custom index BEFORE reading).
     *
     * link/unlinkTranslation resolve via folders()->readLanguageFolder ($nl); the
     * language derivation (languageOfFolder) and the cross-language locate root
     * ($base) both come from the injected FolderContext (intraVox).
     *
     * @param PageIndexService|null $index the shared index; a bare no-hit mock by default.
     * @return array{0: FolderContext, 1: Folder} [folderContext, base]
     */
    private function makeContext(?array $nlJson = null, ?array $deJson = null, bool $deUpdateable = true): array {
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

        $folders = $this->fakeFolderContext(
            readLanguageFolder: $nl,
            intraVox: $base,
            languageFolder: $nl,
            userLanguage: 'nl'
        );

        return [$folders, $base];
    }

    /**
     * Build the TranslationQueryService directly over a FolderContext and an
     * index, the way TranslationApiController wires it: PageLocator over the
     * index, TranslationGroupService over locator+index, and a bare
     * LanguageService mock (no getAvailableLanguages) so display names fall back
     * to the uppercased code.
     */
    private function buildService(FolderContext $folders, ?PageIndexService $index = null): TranslationQueryService {
        $index ??= $this->createMock(PageIndexService::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $locator = new PageLocator($index, $logger);
        $groups = new TranslationGroupService(
            $index,
            $locator,
            new PageIdUtils(),
            $logger
        );
        return new TranslationQueryService(
            $folders,
            $groups,
            $locator,
            $this->createMock(\OCA\IntraVox\Service\LanguageService::class)
        );
    }

    /**
     * The group-writer closure the write callers supply — byte-identical in effect
     * to TranslationApiController::writeTranslationGroup: the language is where the
     * page actually sits (languageOfFolder), else the caller's own language, then
     * TranslationGroupService::writeGroup. Rebuilt here over the SAME index the
     * service holds so the write and the reads stay in step.
     */
    private function groupWriter(FolderContext $folders, PageIndexService $index): \Closure {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $groups = new TranslationGroupService(
            $index,
            new PageLocator($index, $logger),
            new PageIdUtils(),
            $logger
        );
        return function (array $result, string $group) use ($folders, $groups): void {
            $language = $folders->languageOfFolder($result['folder']) ?? $folders->userLanguage();
            $groups->writeGroup($result, $group, $language);
        };
    }

    /** The cache-clear closure: inert, as the unit fixture no-ops invalidation. */
    private function inertClearCache(): \Closure {
        return function (): void {
        };
    }

    private function writtenGroup(string $path): ?string {
        $data = json_decode($this->writes[$path] ?? '{}', true);
        return $data['translationGroup'] ?? null;
    }

    /** Linking gives both pages one shared group. */
    public function testLinkingSharesOneGroup(): void {
        [$folders] = $this->makeContext();
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $svc = $this->buildService($folders, $index);

        $group = $svc->linkTranslation(
            'page-nl',
            'page-de',
            $this->groupWriter($folders, $index),
            $this->inertClearCache()
        );

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
        [$folders] = $this->makeContext(
            null,
            ['uniqueId' => 'page-de', 'title' => 'Über uns', 'translationGroup' => 'tg-existing'],
            false // de is read-only for this user
        );
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $svc = $this->buildService($folders, $index);

        try {
            $svc->linkTranslation(
                'page-nl',
                'page-de',
                $this->groupWriter($folders, $index),
                $this->inertClearCache()
            );
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
        [$folders] = $this->makeContext(
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
        $svc = $this->buildService($folders, $mock);

        try {
            $svc->linkTranslation(
                'page-nl',
                'page-de',
                $this->groupWriter($folders, $mock),
                $this->inertClearCache()
            );
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
        // The M1 ACL-filtering lives in TranslationGroupService::resolveTranslations
        // (facade elimination phase 2 internalized the old PageService closure into
        // PageReadService/PageDataEnricher, both over this engine). Drive the engine
        // directly: a row whose path the caller's mount cannot resolve is skipped.
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByTranslationGroup')->willReturn([
            ['unique_id' => 'page-de', 'language' => 'de', 'title' => 'Über uns',
                'status' => 'published', 'path' => '/IntraVox/de/ueber-uns'],
            // fr/ is not mounted for this user — the ACL-denied case.
            ['unique_id' => 'page-fr', 'language' => 'fr', 'title' => 'Secret FR',
                'status' => 'published', 'path' => '/IntraVox/fr/secret'],
        ]);

        $root = $this->createMock(Folder::class);
        $locator = $this->createMock(\OCA\IntraVox\Service\Locator\PageLocator::class);
        // The mount grants de/, denies fr/ — folderFromAbsolutePath returns null
        // for the path the caller cannot read.
        $locator->method('folderFromAbsolutePath')->willReturnCallback(
            fn($rootFolder, string $path) => str_contains($path, '/fr/')
                ? null
                : $this->createMock(Folder::class)
        );

        $groups = new \OCA\IntraVox\Service\Translation\TranslationGroupService(
            $index,
            $locator,
            new \OCA\IntraVox\Service\Util\PageIdUtils(),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );

        $rows = $groups->resolveTranslations('tg-x', 'page-nl', fn(): Folder => $root);

        $this->assertCount(1, $rows, 'the fr row resolves to no folder on this mount and must be skipped');
        $this->assertSame('page-de', $rows[0]['uniqueId']);
    }

    /**
     * An existing group is adopted rather than replaced, so linking A-B and
     * later B-C leaves all three together instead of splitting into pairs.
     */
    public function testLinkingAdoptsAnExistingGroup(): void {
        $existing = 'tg-11111111-2222-3333-4444-555555555555';
        [$folders] = $this->makeContext(
            ['uniqueId' => 'page-nl', 'title' => 'Over ons', 'translationGroup' => $existing]
        );
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $svc = $this->buildService($folders, $index);

        $group = $svc->linkTranslation(
            'page-nl',
            'page-de',
            $this->groupWriter($folders, $index),
            $this->inertClearCache()
        );

        $this->assertSame($existing, $group, 'the existing group must win');
        $this->assertSame($existing, $this->writtenGroup('/IntraVox/de/ueber-uns.json'));
    }

    /**
     * A group holds at most one page per language: a second German version
     * would make "the German page" ambiguous for the switcher and the notice.
     */
    public function testCannotLinkTwoPagesInTheSameLanguage(): void {
        [$folders] = $this->makeContext();
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $svc = $this->buildService($folders, $index);

        // Both ids resolve inside nl/ — link must be refused.
        $this->expectException(\InvalidArgumentException::class);
        $svc->linkTranslation(
            'page-nl',
            'page-nl',
            $this->groupWriter($folders, $index),
            $this->inertClearCache()
        );
    }

    /** Unlinking gives the page a fresh group of its own, not none. */
    public function testUnlinkingAssignsAFreshGroup(): void {
        $shared = 'tg-11111111-2222-3333-4444-555555555555';
        [$folders] = $this->makeContext(
            ['uniqueId' => 'page-nl', 'title' => 'Over ons', 'translationGroup' => $shared],
            ['uniqueId' => 'page-de', 'title' => 'Über uns', 'translationGroup' => $shared]
        );
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $svc = $this->buildService($folders, $index);

        $fresh = $svc->unlinkTranslation(
            'page-nl',
            $this->groupWriter($folders, $index),
            $this->inertClearCache()
        );

        $this->assertNotSame($shared, $fresh);
        $this->assertMatchesRegularExpression('/^tg-[a-f0-9-]{36}$/', $fresh);
        $this->assertSame($fresh, $this->writtenGroup('/IntraVox/nl/over-ons.json'));
        // The other page is untouched — unlink acts on one page only.
        $this->assertArrayNotHasKey('/IntraVox/de/ueber-uns.json', $this->writes);
    }

    /** An unknown page is reported as not found. */
    public function testLinkingAnUnknownPageFails(): void {
        [$folders] = $this->makeContext();
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $svc = $this->buildService($folders, $index);

        $this->expectException(\OCA\IntraVox\Exception\PageNotFoundException::class);
        $svc->linkTranslation(
            'page-nl',
            'page-nope',
            $this->groupWriter($folders, $index),
            $this->inertClearCache()
        );
    }

    // ------------------------------------------------- getTranslatableLanguages

    /**
     * The languages a page can still be created in: every content language
     * except the page's own and any the group already covers. This picker had
     * no behavioural coverage before the TRANSLATE-query carve.
     */
    public function testTranslatableLanguagesExcludesOwnLanguageAndCarriesName(): void {
        // page-nl lives in nl/; base holds nl + de, so the only candidate is de.
        [$folders] = $this->makeContext();
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $svc = $this->buildService($folders, $index);

        $languages = $svc->getTranslatableLanguages('page-nl');
        $codes = array_column($languages, 'code');

        $this->assertSame(['de'], $codes, 'own language nl is excluded, de remains');
        // name comes from languageDisplayName; with a bare LanguageService mock
        // (no getAvailableLanguages) it falls back to the uppercased code.
        $this->assertSame('DE', $languages[0]['name']);
        $this->assertArrayHasKey('missingAncestors', $languages[0]);
    }

    /** A language the page's group already covers is not offered again. */
    public function testTranslatableLanguagesExcludesAlreadyTakenLanguages(): void {
        $existing = 'tg-11111111-2222-3333-4444-555555555555';
        [$folders] = $this->makeContext(
            ['uniqueId' => 'page-nl', 'title' => 'Over ons', 'translationGroup' => $existing]
        );
        // The group already holds a German version, so de must drop out — the
        // only other content language — leaving an empty offer.
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $index->method('findByTranslationGroup')->willReturnCallback(
            fn($g) => $g === $existing ? [['unique_id' => 'page-de', 'language' => 'de']] : []
        );
        $svc = $this->buildService($folders, $index);

        $codes = array_column($svc->getTranslatableLanguages('page-nl'), 'code');
        $this->assertSame([], $codes, 'de is already in the group, so nothing is offered');
    }

    public function testTranslatableLanguagesRejectsAnUnknownPage(): void {
        [$folders] = $this->makeContext();
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $svc = $this->buildService($folders, $index);
        $this->expectException(\OCA\IntraVox\Exception\PageNotFoundException::class);
        $svc->getTranslatableLanguages('page-nope');
    }

    // ------------------------------------------------- getTranslationCandidates

    /**
     * Candidate pages to link to: excludes the page itself, its own language,
     * and pages already grouped elsewhere. Answered from the index. No
     * behavioural coverage before the carve.
     */
    public function testTranslationCandidatesExcludeSelfAndGroupedPages(): void {
        [$folders] = $this->makeContext();
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        // tg-other holds a member OTHER than page-de-taken, so hasOtherMembers()
        // (which reads findByTranslationGroup) reports it as genuinely grouped.
        $index->method('findByTranslationGroup')->willReturnCallback(
            fn($g) => $g === 'tg-other'
                ? [['unique_id' => 'page-en-partner', 'language' => 'en']]
                : []
        );
        // de holds three pages: a free one, the page itself (must never appear),
        // and one already in another multi-member group (must be excluded).
        $index->method('getPagesByLanguage')->willReturnCallback(fn($code) => $code === 'de' ? [
            ['unique_id' => 'page-de-free', 'language' => 'de', 'title' => 'Frei'],
            ['unique_id' => 'page-nl', 'language' => 'de', 'title' => 'Self echo'],
            ['unique_id' => 'page-de-taken', 'language' => 'de', 'title' => 'Belegt',
                'translation_group' => 'tg-other'],
        ] : []);
        $svc = $this->buildService($folders, $index);

        $ids = array_column($svc->getTranslationCandidates('page-nl'), 'uniqueId');

        $this->assertContains('page-de-free', $ids, 'a free page in another language is offered');
        $this->assertNotContains('page-nl', $ids, 'the page itself is never a candidate');
        $this->assertNotContains('page-de-taken', $ids, 'a page already grouped elsewhere is excluded');
    }

    /** Passing a language narrows the offer to that one language. */
    public function testTranslationCandidatesNarrowToOneLanguage(): void {
        [$folders] = $this->makeContext();
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $index->method('findByTranslationGroup')->willReturn([]);
        $index->method('getPagesByLanguage')->willReturnCallback(fn($code) => [
            ['unique_id' => 'page-' . $code . '-x', 'language' => $code, 'title' => strtoupper($code)],
        ]);
        $svc = $this->buildService($folders, $index);

        // Only de is a content language besides nl, and narrowing to de keeps it.
        $ids = array_column($svc->getTranslationCandidates('page-nl', 'de'), 'uniqueId');
        $this->assertSame(['page-de-x'], $ids);

        // Narrowing to a language with no content folder yields nothing.
        $this->assertSame([], $svc->getTranslationCandidates('page-nl', 'fr'));
    }

    public function testTranslationCandidatesRejectAnUnknownPage(): void {
        [$folders] = $this->makeContext();
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $svc = $this->buildService($folders, $index);
        $this->expectException(\OCA\IntraVox\Exception\PageNotFoundException::class);
        $svc->getTranslationCandidates('page-nope');
    }
}
