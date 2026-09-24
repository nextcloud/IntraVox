<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\PublicShare;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * Resolve a shared page by uniqueId alone, without being told its language.
 *
 * A public link carries a token and a uniqueId; nothing in it names a language.
 * PublicShareController used to guess: it called PageReadService::getPage()
 * purely to read the language, that read resolved through the SESSION user's
 * mount, an anonymous visitor has no session user, and the guess fell back to
 * 'en'. validateShareAccess() then searched the 'en' tree first — 581 folders
 * on the instance this was measured on — missed, and swept every language
 * folder in turn before reaching the Dutch page. Every public page took 2.2
 * seconds; a uniqueId that did not exist cost exactly as much, because a wrong
 * guess has to be disproved the same way.
 *
 * The index already answers the question directly: PageIndexService::
 * findByUniqueId() keys on unique_id alone and uses language only as a
 * tie-breaker. The trick is what this class exists to name — handing
 * locateViaIndex() the IntraVox ROOT as both root and primary folder makes
 * PageLocator::languageOfFolder() return null (its first branch: the two paths
 * are equal), so no language preference is applied and one query resolves the
 * page wherever it lives.
 *
 * SECURITY: this decides WHICH file is handed to the scope check in
 * validateShareAccess(), never WHETHER that check runs. The resolved node still
 * has to sit inside the share's folder, and locateViaIndex() re-reads the file
 * and verifies its uniqueId, so a stale index falls through rather than serving
 * a different page. Returning null is always safe: the caller then walks the
 * language folders exactly as it did before the index existed.
 *
 * @see \OCA\IntraVox\Tests\Unit\Service\PublicShare\SharePageLanguageResolutionTest
 */
final class SharePageIndexResolver {
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * One index query, no directory listing.
     *
     * @param string $uniqueId the page to find
     * @param string|null $language returned as-is when the resolved folder sits
     *   outside any language folder, so the caller keeps whatever it knew
     * @return array{path:string,node:\OCP\Files\File,data:array,language:?string,title:?string}|null
     *   null whenever the index cannot answer — no entry, a stale one, or any
     *   failure at all. The caller falls back to the filesystem walk.
     */
    public function resolve(
        ?PageLocator $locator,
        ?Folder $root,
        string $uniqueId,
        ?string $language,
    ): ?array {
        if ($locator === null || $root === null) {
            return null;
        }

        try {
            // Root as BOTH arguments: that is what drops the language
            // preference. See the class docblock.
            $viaIndex = $locator->locateViaIndex(fn() => $root, $uniqueId, $root);
            if (!isset($viaIndex['file'])) {
                return null;
            }

            $data = json_decode($viaIndex['file']->getContent(), true);
            if (!is_array($data)) {
                return null;
            }

            // Pure path arithmetic against the root — no I/O.
            $found = $locator->languageOfFolder($root, $viaIndex['folder']);

            return [
                'path' => $viaIndex['file']->getPath(),
                'node' => $viaIndex['file'],
                'data' => $data,
                'language' => $found ?? $language,
                'title' => $data['title'] ?? null,
            ];
        } catch (\Throwable $e) {
            // Never let an index problem break a share: the caller walks
            // instead, the same way locateViaIndex() degrades internally.
            $this->logger->debug('[SharePageIndexResolver] index resolve failed, walking', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
