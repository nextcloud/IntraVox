<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Translation;

use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCP\Files\Folder;

/**
 * The TRANSLATE-query/link domain carved out of the PageService god-class: the
 * four operations that read or wire the relationship between the language
 * versions of a page.
 *
 *   - linkTranslation / unlinkTranslation: wire two pages into one shared
 *     translation group, or detach a page into a fresh solo group.
 *   - getTranslatableLanguages: the languages a page can still be created in.
 *   - getTranslationCandidates: the existing pages a page could be linked to.
 *
 * The mechanics live in the TranslationGroupService engine (ctor-injected).
 * Page lookup (via PageLocator), the language-of-folder derivation (FolderContext)
 * and the display-name lookup (LanguageService) are now real injected
 * collaborators, so the two READ operations are closure-free and the service is
 * directly injectable. Only the two shared WRITE concerns arrive as bound
 * closures: the group-writer (also used by the compose-domain createTranslation,
 * so it belongs to the hub and both domains reach the one definition) and the
 * cache-clear (the cross-collaborator invalidation still resident on PageService).
 */
final class TranslationQueryService {

    /**
     * @param \Closure(array, string): void $groupWriter write a translation
     *        group into a located page and its index row.
     * @param \Closure(): void $clearCache invalidate the page caches.
     */
    public function __construct(
        private FolderContext $folders,
        private TranslationGroupService $groups,
        private PageLocator $locator,
        private LanguageService $languageService,
        private \Closure $groupWriter,
        private \Closure $clearCache,
    ) {
    }

    /**
     * Locate a page by uniqueId across every language folder (the former
     * locatePageAnyLanguage closure, now over the injected PageLocator).
     */
    private function locatePageAnyLanguage(Folder $primaryFolder, string $uniqueId): ?array {
        return $this->locator->locatePageAnyLanguage(
            fn(): Folder => $this->folders->intraVox(),
            $primaryFolder,
            $uniqueId
        );
    }

    /**
     * Human-readable name for a language code ('en' -> 'English'), for messages a
     * user reads (the former languageDisplayName closure, now over the injected
     * LanguageService). Falls back to the uppercased code, and drops Nextcloud's
     * interface-variant parenthetical since a content folder is a plain code.
     */
    private function languageDisplayName(string $code): string {
        try {
            // getAvailableLanguages() is typed array{code,name}[] — the keys are
            // guaranteed present, so the old ?? '' guards on them were dead.
            foreach ($this->languageService->getAvailableLanguages() as $lang) {
                if ($lang['code'] === $code) {
                    $name = $lang['name'];
                    if ($name === '') {
                        return strtoupper($code);
                    }
                    $base = trim(explode('(', $name)[0]);
                    return $base !== '' ? $base : $name;
                }
            }
        } catch (\Throwable $e) {
            // Naming is cosmetic; never let it break the operation's real error.
        }
        return strtoupper($code);
    }

    /**
     * Link two pages as translations of each other, returning the shared group.
     *
     * Adopts an existing group from whichever side already has one, so linking
     * is additive: linking A-B and later B-C leaves all three together instead
     * of splitting into pairs.
     *
     * @throws \InvalidArgumentException when a page is linked to itself, the two
     *   pages are in the same language, or adoption would give the group two
     *   members in one language.
     * @throws PageNotFoundException when either page cannot be found.
     * @throws ForbiddenException when the caller lacks edit rights on both.
     */
    public function linkTranslation(string $uniqueIdA, string $uniqueIdB): string {
        if ($uniqueIdA === $uniqueIdB) {
            throw new \InvalidArgumentException('A page cannot be a translation of itself');
        }

        $folder = $this->folders->readLanguageFolder();
        $a = $this->locatePageAnyLanguage($folder, $uniqueIdA);
        $b = $this->locatePageAnyLanguage($folder, $uniqueIdB);
        if ($a === null) {
            throw new PageNotFoundException('Page not found: ' . $uniqueIdA);
        }
        if ($b === null) {
            throw new PageNotFoundException('Page not found: ' . $uniqueIdB);
        }

        $langA = $this->folders->languageOfFolder($a['folder']);
        $langB = $this->folders->languageOfFolder($b['folder']);
        if ($langA !== null && $langA === $langB) {
            throw new \InvalidArgumentException(
                'These pages are both in the same language, so one cannot be a translation of the other.'
            );
        }

        // BOTH sides must be writable before either is written. The order
        // matters more than it looks: the group is adopted from whichever side
        // already has one, so writing A first and then failing on B would leave
        // A a member of B's existing group — a link B's editors never made,
        // created by someone without write access to B. Checking up front makes
        // denial happen before any state changes.
        foreach ([$a, $b] as $side) {
            if (!$side['file']->isUpdateable()) {
                throw new ForbiddenException('You need edit permission on both pages to link them');
            }
        }

        // Adopt an existing group when there is one, so linking is additive.
        $dataA = json_decode($a['file']->getContent(), true);
        $dataB = json_decode($b['file']->getContent(), true);
        $group = (is_array($dataA) ? ($dataA['translationGroup'] ?? null) : null)
            ?: (is_array($dataB) ? ($dataB['translationGroup'] ?? null) : null)
            ?: $this->groups->newGroupId();

        // Adoption must not smuggle in a language the group already has —
        // the invariant lives with the rest of the group rules.
        $this->groups->assertAdoptionAddsNoDuplicateLanguage(
            $group,
            [[$uniqueIdA, $langA], [$uniqueIdB, $langB]]
        );

        ($this->groupWriter)($a, $group);
        ($this->groupWriter)($b, $group);
        ($this->clearCache)();

        return $group;
    }

    /**
     * Detach a page from its translation group.
     *
     * The page gets a fresh group of its own rather than none at all, so
     * "linked" and "unlinked" stay the same shape and the page can be linked
     * again later without a special case.
     *
     * Only ever touches the page asked for. WPML shipped a bug where an update
     * silently re-linked translations an editor had deliberately unlinked;
     * nothing here infers a relationship from similarity.
     *
     * @throws PageNotFoundException when the page cannot be found
     */
    public function unlinkTranslation(string $uniqueId): string {
        $folder = $this->folders->readLanguageFolder();
        $result = $this->locatePageAnyLanguage($folder, $uniqueId);
        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $uniqueId);
        }

        $group = $this->groups->newGroupId();
        ($this->groupWriter)($result, $group);
        ($this->clearCache)();

        return $group;
    }

    /**
     * Languages this page could still be created in.
     *
     * A language qualifies when it has a content folder, is not the page's own,
     * and does not already hold a version of this page. Offering anything else
     * would produce a control that fails when used.
     *
     * @return array<int, array{code:string, name:string}>
     * @throws PageNotFoundException when the page cannot be found
     */
    public function getTranslatableLanguages(string $pageId): array {
        $result = $this->locatePageAnyLanguage($this->folders->readLanguageFolder(), $pageId);
        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        $ownLanguage = $this->folders->languageOfFolder($result['folder']);
        $data = json_decode($result['file']->getContent(), true);
        $group = is_array($data) ? ($data['translationGroup'] ?? null) : null;

        $root = $this->folders->intraVox();
        $taken = $this->groups->languagesTaken($group);

        $languages = [];
        foreach ($this->groups->otherContentLanguages($root, $ownLanguage) as $code) {
            if (isset($taken[$code])) {
                continue;
            }
            $languages[] = [
                'code' => $code,
                // Naming stays here: it reads LanguageService's list, which is
                // the interface-language source the admin tab shares.
                'name' => $this->languageDisplayName($code),
                // How many of this page's ancestors do not exist as pages in
                // that language yet. The translation still lands mirrored
                // (createTranslation creates the missing levels as bare
                // folders, and the tree renders those as non-clickable
                // pass-through nodes) — but the editor deserves to know
                // BEFORE creating, not by discovering grey levels afterwards.
                'missingAncestors' => $this->groups
                    ->countMissingAncestors($root, $result['folder'], $code),
            ];
        }

        return $languages;
    }

    /**
     * Pages this page could be linked to as a translation.
     *
     * Excludes three sets, each for a reason:
     *   - the page's own language, since a group holds one page per language;
     *   - pages already in a group with something else, so linking cannot
     *     silently steal a page out of an existing set;
     *   - the page itself.
     *
     * Answered from the index, so the picker stays cheap on a large intranet.
     *
     * @param string|null $language limit to one language, or null for all others
     * @return array<int, array{uniqueId:string, title:string, language:string}>
     * @throws PageNotFoundException when the page cannot be found
     */
    public function getTranslationCandidates(string $pageId, ?string $language = null): array {
        $folder = $this->folders->readLanguageFolder();
        $result = $this->locatePageAnyLanguage($folder, $pageId);
        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        $ownLanguage = $this->folders->languageOfFolder($result['folder']);
        $ownData = json_decode($result['file']->getContent(), true);
        $ownGroup = is_array($ownData) ? ($ownData['translationGroup'] ?? null) : null;

        // Languages this page's group already covers — candidates in those
        // languages are filtered below, one indexed query for the whole list.
        $takenLanguages = $this->groups->languagesTaken($ownGroup, $pageId);

        // Languages to offer: everything with content except this page's own,
        // listed through the caller's own mount so denied languages never
        // appear (see otherContentLanguages()).
        $languages = $this->groups->otherContentLanguages(
            $this->folders->intraVox(),
            $ownLanguage,
            $language
        );

        return $this->groups->candidatesInLanguages(
            $pageId,
            $ownGroup,
            $languages,
            $takenLanguages
        );
    }
}
