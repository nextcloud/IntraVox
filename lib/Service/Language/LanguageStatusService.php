<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Language;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Listing\PageLister;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * The "where does content live, per language?" reader — the language-CONTENT-
 * STATUS domain carved out of the PageService god-class.
 *
 * Two questions, one responsibility:
 *   - getContentStatus(): which languages have real vs placeholder homepages,
 *     which language the user is actually served (#75 fallback), and the
 *     homepage the app should land on. Drives the landing-page fallback notice
 *     and the admin "Languages with content" chips.
 *   - getPageCountByLanguage(): how many pages each language folder holds — the
 *     number the admin "remove language" confirmation warns with.
 *
 * Deliberately NOT in scope: the homepage-resolution subsystem and the
 * real-content probe. Those stay resident on PageService — the probe because
 * the FolderContext substrate is built from it (a circular seam), homepage
 * resolution because it is shared with the homepage seam and the API
 * controller. This service receives both only as bound closures, so the shared
 * subsystems cannot leak in here. LanguageStatusIsolationTest enforces that no
 * such symbol is even referenced from this namespace, which is why this
 * docblock names none of them literally.
 */
final class LanguageStatusService {

    public function __construct(
        private FolderContext $folders,
        private PageLister $pageLister,
        private PageLocator $locator,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Language content status for the CURRENT user. Drives the landing-page
     * fallback notice and is the "active = where content is" signal for the
     * VoxCloud language model (replaces the enabled_languages opt-in list).
     *
     * Two distinct sets:
     *   - languagesWithContent: only REAL (editor-authored) homepages. The
     *     fallback notice uses this so a placeholder doesn't mask "no content".
     *   - activeLanguages: every language with ANY homepage (incl. an added
     *     placeholder). The admin "Languages with content" chips use this so a
     *     just-added language appears immediately.
     *
     * The homepage-resolution and real-content probes stay on PageService and
     * arrive as closures ($resolveHomepage / $hasHomepage / $hasRealContent) so
     * the shared homepage subsystem and the FolderContext-circular probe are not
     * reconstructed here.
     *
     * @param \Closure(?string): string $resolveHomepage resolves the homepage
     *        uniqueId for a language (bound to PageService's homepage resolver).
     * @param \Closure(Folder): bool $hasHomepage whether a language folder has
     *        ANY homepage (real or placeholder).
     * @param \Closure(Folder): bool $hasRealContent whether a language folder
     *        has a REAL (non-_generated) homepage.
     * @return array{
     *   language: string,
     *   hasContent: bool,
     *   servedLanguage: ?string,
     *   languagesWithContent: string[],
     *   activeLanguages: string[],
     *   homepageUniqueId: ?string
     * }
     */
    public function getContentStatus(
        \Closure $resolveHomepage,
        \Closure $hasHomepage,
        \Closure $hasRealContent,
    ): array {
        $userLang = $this->folders->userLanguage();
        $withContent = [];
        $active = [];

        try {
            $baseFolder = $this->folders->intraVox();
            foreach ($this->locator->cachedDirectoryListing($baseFolder) as $item) {
                if ($item->getType() !== FileInfo::TYPE_FOLDER) {
                    continue;
                }
                $name = $item->getName();
                // Language folders are two-letter base codes (nl, en, de, ...).
                if (!preg_match('/^[a-z]{2,3}$/', $name) || !($item instanceof Folder)) {
                    continue;
                }
                if ($hasHomepage($item)) {
                    $active[] = $name;
                }
                if ($hasRealContent($item)) {
                    $withContent[] = $name;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[PageService] getLanguageContentStatus failed: ' . $e->getMessage());
        }

        sort($withContent);
        sort($active);

        // The language the user will actually be shown: own language, else the
        // recommended (primary) language, else English — issue #75. null means
        // nothing can be served (only then does the fallback notice appear).
        $served = $this->folders->effectiveLanguage();

        // Resolve the homepage for the SERVED language (not necessarily the
        // user's), so the app lands on the correct homepage after fallback.
        $homepageUniqueId = null;
        try {
            $homepageUniqueId = $resolveHomepage($served ?? $userLang);
        } catch (\Throwable $e) {
            // Non-fatal: the frontend falls back to its own heuristic.
        }

        return [
            'language' => $userLang,
            // hasContent = "the user will see real content" (own language, the
            // recommended language, or English all count). Only false when
            // nothing resolves — the sole trigger for the fallback notice.
            'hasContent' => $served !== null,
            'servedLanguage' => $served,
            'languagesWithContent' => $withContent,
            'activeLanguages' => $active,
            'homepageUniqueId' => $homepageUniqueId,
        ];
    }

    /**
     * Number of pages per language folder (base code => count). Used by the
     * admin "remove language" confirmation so it can warn how many pages would
     * be deleted. Counts the homepage plus every `{name}/{name}.json` subpage.
     *
     * @return array<string,int>
     */
    public function getPageCountByLanguage(): array {
        $counts = [];
        try {
            $baseFolder = $this->folders->intraVox();
            foreach ($this->locator->cachedDirectoryListing($baseFolder) as $item) {
                if ($item->getType() !== FileInfo::TYPE_FOLDER) {
                    continue;
                }
                $name = $item->getName();
                if (!preg_match('/^[a-z]{2,3}$/', $name) || !($item instanceof Folder)) {
                    continue;
                }
                $pages = [];
                $this->pageLister->walkPlain($item, $pages, '');
                $count = count($pages);
                // Homepage counts as a page when present (findPagesInFolder skips it).
                if ($item->nodeExists('home.json')) {
                    $count++;
                }
                $counts[$name] = $count;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[PageService] getPageCountByLanguage failed: ' . $e->getMessage());
        }
        return $counts;
    }
}
