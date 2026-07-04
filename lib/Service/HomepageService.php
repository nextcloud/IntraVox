<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IL10N;

/**
 * Per-language homepage pointer (issue: configurable homepage).
 *
 * Stores which page is the homepage in a small per-language config file
 * `{lang}/homepage.json` = { "homepageUniqueId": "page-…" }. This lives next to
 * navigation.json / footer.json and is skipped in page enumeration.
 *
 * The pointer is OPTIONAL: when absent (every pre-existing install), the app
 * falls back to the legacy loose `home.json`-in-root behaviour, so nothing
 * changes until an admin explicitly picks a different homepage. This service
 * only reads/writes the pointer; PageService owns the resolution + fallback.
 */
class HomepageService {
    public const CONFIG_FILE = 'homepage.json';

    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private LanguageService $languageService;
    private SystemFileService $systemFileService;
    private IL10N $l10n;
    private string $userId;

    private ?ICache $pagesCache = null;
    private ?ICache $permissionsCache = null;

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        LanguageService $languageService,
        SystemFileService $systemFileService,
        IL10N $l10n,
        ICacheFactory $cacheFactory,
        ?string $userId
    ) {
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->languageService = $languageService;
        $this->systemFileService = $systemFileService;
        $this->l10n = $l10n;
        $this->userId = $userId ?? '';

        if ($cacheFactory->isAvailable()) {
            $this->pagesCache = $cacheFactory->createDistributed('intravox-pages');
            $this->permissionsCache = $cacheFactory->createDistributed('intravox-permissions');
        }
    }

    /**
     * The configured homepage uniqueId for a language, or null when unset
     * (→ caller falls back to the legacy loose home.json).
     *
     * Read via the user's folder view first (respects ACL); falls back to the
     * system context so department-only users still resolve the homepage.
     */
    public function getHomepageUniqueId(?string $language = null): ?string {
        $lang = $language ?? $this->getCurrentLanguage();

        // Try the user's own view first (ACL-respecting).
        try {
            $folder = $this->getLanguageFolder($lang);
            if ($folder->nodeExists(self::CONFIG_FILE)) {
                $data = json_decode($folder->get(self::CONFIG_FILE)->getContent(), true);
                $uid = $data['homepageUniqueId'] ?? null;
                if (is_string($uid) && $uid !== '') {
                    return $uid;
                }
            }
        } catch (\Exception $e) {
            // No access to the language root — fall through to system context.
        }

        // Fallback: system context (for limited-access users).
        try {
            $content = $this->systemFileService->getSharedResource($lang, self::CONFIG_FILE);
            if ($content !== null) {
                $data = json_decode($content, true);
                $uid = $data['homepageUniqueId'] ?? null;
                if (is_string($uid) && $uid !== '') {
                    return $uid;
                }
            }
        } catch (\Exception $e) {
            // System read failed too — treat as unset.
        }

        return null;
    }

    /**
     * Persist the homepage pointer for a language and flush the page/permission
     * caches (mirrors NavigationService::saveNavigation).
     */
    public function setHomepageUniqueId(string $uniqueId, ?string $language = null): void {
        $lang = $language ?? $this->getCurrentLanguage();
        $folder = $this->getLanguageFolder($lang);
        $content = json_encode(['homepageUniqueId' => $uniqueId], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($folder->nodeExists(self::CONFIG_FILE)) {
            $folder->get(self::CONFIG_FILE)->putContent($content);
        } else {
            $folder->newFile(self::CONFIG_FILE, $content);
        }

        $this->pagesCache?->clear();
        $this->permissionsCache?->clear();
    }

    /**
     * Remove the pointer (revert to legacy home.json fallback).
     */
    public function clearHomepagePointer(?string $language = null): void {
        $lang = $language ?? $this->getCurrentLanguage();
        try {
            $folder = $this->getLanguageFolder($lang);
            if ($folder->nodeExists(self::CONFIG_FILE)) {
                $folder->get(self::CONFIG_FILE)->delete();
            }
        } catch (\Exception $e) {
            // Nothing to clear.
        }
        $this->pagesCache?->clear();
        $this->permissionsCache?->clear();
    }

    // ---- helpers (mirror NavigationService) ----

    private function getIntraVoxFolder() {
        if (!$this->userId) {
            throw new \Exception('User not logged in');
        }
        $userFolder = $this->rootFolder->getUserFolder($this->userId);
        return $userFolder->get('IntraVox');
    }

    private function getLanguageFolder(string $language) {
        $sharedFolder = $this->getIntraVoxFolder();
        if (!$this->languageService->isLanguageEnabled($language)) {
            $language = $this->languageService->getDefaultLanguage();
        }
        if (!$sharedFolder->nodeExists($language)) {
            $sharedFolder->newFolder($language);
        }
        return $sharedFolder->get($language);
    }

    public function getCurrentLanguage(): string {
        $languageCode = $this->l10n->getLanguageCode();
        $baseLang = strtolower(substr($languageCode, 0, 2));
        return $this->languageService->isLanguageEnabled($baseLang)
            ? $baseLang
            : $this->languageService->getDefaultLanguage();
    }
}
