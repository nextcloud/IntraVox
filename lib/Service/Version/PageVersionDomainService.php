<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Version;

use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * The VERSION/HISTORY domain carved out of the god-class: the five
 * operations behind the page version-history UI — list the versions of a page,
 * restore one, preview a version's content, label a version, and read the
 * current content for the "compare with current" panel.
 *
 * The version-manager mechanics live in the PageVersionService engine
 * (ctor-injected). Page resolution runs through the injected FolderContext +
 * PageLocator directly: two genuinely distinct pre-carve idioms are reproduced
 * verbatim as the private locateVersionPage() (languageFolder -> page-* locate ->
 * findPageById fallback, for the version-manager reads) and locateForOperation()
 * (readLanguageFolder -> page-* locate -> slug locate, for the label +
 * compare-current reads). They are NOT unified (that would change folder
 * resolution, the slug lookup and the exception class).
 */
final class PageVersionDomainService {

    public function __construct(
        private PageVersionService $engine,
        private LoggerInterface $logger,
        private FolderContext $folders,
        private PageLocator $locator,
        private PageIdUtils $idUtils,
    ) {
    }

    /**
     * Resolve a page for the version-manager reads: follow a page-… uniqueId
     * across language folders (issue #90), else fall back to the legacy id
     * lookup in the user's own language folder. Returns null on a miss so the
     * caller owns the throw. (Verbatim from the former god-class prologue.)
     */
    private function locateVersionPage(string $pageId): ?array {
        $folder = $this->folders->languageFolder();
        $result = null;

        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locator->locatePageAnyLanguage(
                fn(): Folder => $this->folders->intraVox(),
                $folder,
                $pageId
            );
        }

        if ($result === null) {
            $result = $this->locator->findPageById($folder, $this->idUtils->sanitizeId($pageId));
        }

        return $result;
    }

    /**
     * Resolve a page for the label / compare-current reads: page-… uniqueId
     * across languages, else the legacy slug across languages. (Verbatim from
     * the former god-class operation-locate.)
     */
    private function locateForOperation(string $pageId): ?array {
        $folder = $this->folders->readLanguageFolder();

        if (strpos($pageId, 'page-') === 0) {
            $byUniqueId = $this->locator->locatePageAnyLanguage(
                fn(): Folder => $this->folders->intraVox(),
                $folder,
                $pageId
            );
            if ($byUniqueId !== null) {
                return $byUniqueId;
            }
        }

        return $this->locator->locatePageBySlugAnyLanguage(
            fn(): Folder => $this->folders->intraVox(),
            $folder,
            $this->idUtils->sanitizeId($pageId)
        );
    }

    /**
     * Every stored version of a page, newest first.
     *
     * @throws \Exception when the page cannot be found
     */
    public function getPageVersions(string $pageId): array {
        $result = $this->locateVersionPage($pageId);

        if (!$result) {
            $this->logger->warning('[getPageVersions] Page not found: ' . $pageId);
            throw new \Exception('Page not found: ' . $pageId);
        }

        return $this->engine->listForFile($result['file']);
    }

    /**
     * Restore a specific version of a page.
     * Uses IVersionManager for reliable version restoration across all storage types.
     *
     * @throws \Exception if page or version not found
     */
    public function restorePageVersion(string $pageId, int $timestamp): array {
        $result = $this->locateVersionPage($pageId);

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $restoredData = $this->engine->restoreToTimestamp(
            $result['file'],
            $result['folder'],
            $timestamp
        );

        // Return data with id for frontend (id is derived from folder name)
        // For home page it's 'home', otherwise use the folder basename
        $resolvedId = ($pageId === 'home') ? 'home' : $result['folder']->getName();
        return array_merge(['id' => $resolvedId], $restoredData);
    }

    /**
     * Get version content for preview.
     * Uses IVersionManager for reliable version content retrieval across all storage types.
     *
     * @throws \Exception when the page cannot be found
     */
    public function getVersionContent(string $pageId, int $timestamp): array {
        $result = $this->locateVersionPage($pageId);

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        return $this->engine->contentAtTimestamp($result['file'], $timestamp);
    }

    /**
     * Set (or clear) the human label on one stored version.
     *
     * @throws PageNotFoundException when the page cannot be found
     */
    public function updateVersionLabel(string $pageId, int $timestamp, ?string $label): void {
        // Verify page exists. Had neither a uniqueId branch nor a cross-language
        // fallback, so labelling a version failed on any page-… id and on any
        // page outside the caller's own language (#90).
        $result = $this->locateForOperation($pageId);

        if (!$result) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        $this->engine->setLabel($result['file'], $timestamp, $label);
    }

    /**
     * Get current page content for comparison.
     *
     * @throws PageNotFoundException when the page cannot be found
     */
    public function getCurrentPageContent(string $pageId): array {
        // Same shape as updateVersionLabel(): no uniqueId branch and no
        // cross-language fallback, so the "compare with current" panel in the
        // version history broke on page-… ids and on foreign-language pages.
        $result = $this->locateForOperation($pageId);

        if (!$result) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $content = $file->getContent();

        return [
            'title' => $result['page']['name'] ?? 'Untitled',
            'content' => $content,
            'rawContent' => $content
        ];
    }
}
