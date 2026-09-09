<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Version;

use OCA\IntraVox\Exception\PageNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * The VERSION/HISTORY domain carved out of the PageService god-class: the five
 * operations behind the page version-history UI — list the versions of a page,
 * restore one, preview a version's content, label a version, and read the
 * current content for the "compare with current" panel.
 *
 * The version-manager mechanics live in the PageVersionService engine
 * (ctor-injected). Page resolution stays on PageService — deliberately, because
 * the cross-language page-lookup helpers are shared far beyond version history
 * — and arrives as two bound closures:
 *
 *   - the located-array closure used by the three version-manager reads, which
 *     follows a page across language folders and throws on a miss's caller side
 *     via a null return (this service owns the throw + the one log line);
 *   - the operation-locate closure used by the label + compare-current methods.
 *
 * The two closures reproduce two genuinely distinct pre-carve resolution idioms
 * verbatim; they are NOT unified here (that would change folder resolution, the
 * slug lookup and the exception class — a separate follow-up).
 */
final class PageVersionDomainService {

    /**
     * @param \Closure(string): ?array $locateVersionPage resolve a page for the
     *        version-manager reads (returns a locate result, or null on a miss).
     * @param \Closure(string): ?array $locateForOperation resolve a page for the
     *        label / compare-current reads (returns a locate result, or null).
     */
    public function __construct(
        private PageVersionService $engine,
        private LoggerInterface $logger,
        private \Closure $locateVersionPage,
        private \Closure $locateForOperation,
    ) {
    }

    /**
     * Every stored version of a page, newest first.
     *
     * @throws \Exception when the page cannot be found
     */
    public function getPageVersions(string $pageId): array {
        $result = ($this->locateVersionPage)($pageId);

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
        $result = ($this->locateVersionPage)($pageId);

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
        $result = ($this->locateVersionPage)($pageId);

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
        $result = ($this->locateForOperation)($pageId);

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
        $result = ($this->locateForOperation)($pageId);

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
