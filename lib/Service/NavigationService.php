<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\Service\Sanitize\UrlSanitizer;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IL10N;

class NavigationService {
    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private SetupService $setupService;
    private SystemFileService $systemFileService;
    private IL10N $l10n;
    private LanguageService $languageService;
    private string $userId;
    private UrlSanitizer $urlSanitizer;

    private ?ICache $pagesCache = null;
    private ?ICache $permissionsCache = null;

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        SetupService $setupService,
        SystemFileService $systemFileService,
        IL10N $l10n,
        ICacheFactory $cacheFactory,
        LanguageService $languageService,
        ?string $userId
    ) {
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->setupService = $setupService;
        $this->systemFileService = $systemFileService;
        $this->l10n = $l10n;
        $this->languageService = $languageService;
        $this->userId = $userId ?? '';
        // Stateless allowlist; instantiated directly to avoid widening the
        // constructor signature of a service with 11 test subclasses.
        $this->urlSanitizer = new UrlSanitizer();

        if ($cacheFactory->isAvailable()) {
            // We don't own these caches but we mutate state they index
            // (navigation.json drives nav rendering, which PermissionService
            // path-maps and PageService trees pull through). Holding thin
            // handles avoids circular DI with PageService / PermissionService.
            $this->pagesCache = $cacheFactory->createDistributed('intravox-pages');
            $this->permissionsCache = $cacheFactory->createDistributed('intravox-permissions');
        }
    }

    /**
     * Get navigation structure for current language
     *
     * First tries to read via user's folder view (respects ACL).
     * Falls back to SystemFileService for users with limited access (e.g., department-only).
     */
    public function getNavigation(?string $language = null): array {
        $lang = $language ?? $this->getCurrentLanguage();

        // Try to read via user's folder view first (respects ACL)
        try {
            $folder = $this->getLanguageFolder($lang);
            $navigationFile = 'navigation.json';

            if ($folder->nodeExists($navigationFile)) {
                $file = $folder->get($navigationFile);
                $content = $file->getContent();
                $navigation = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($navigation)) {
                    // Normalize navigation items to ensure pageId is set (not uniqueId)
                    if (isset($navigation['items']) && is_array($navigation['items'])) {
                        $navigation['items'] = $this->normalizeNavigationItems($navigation['items']);
                    }
                    return $navigation;
                }
            }
        } catch (\Exception $e) {
            // User doesn't have access to the language root folder
            // Fall back to SystemFileService
        }

        // Fallback: Use SystemFileService to read navigation via system context
        // This allows users with department-level access to still see the navigation.
        //
        // Gated: the fallback bypasses ACLs by design, so it may only run for
        // the case it exists for -- a user who cannot reach the language root
        // at all. An explicit deny on navigation.json itself used to arrive as
        // the same exception and was silently overridden (issue #112).
        if ($this->userId !== ''
            && !$this->systemFileService->mayUseSystemFallback($this->userId, $lang, 'navigation.json')) {
            return [
                'type' => 'dropdown',
                'items' => []
            ];
        }

        try {
            $navigation = $this->systemFileService->getNavigation($lang);

            if ($navigation !== null && is_array($navigation)) {
                // Normalize navigation items
                if (isset($navigation['items']) && is_array($navigation['items'])) {
                    $navigation['items'] = $this->normalizeNavigationItems($navigation['items']);
                }
                return $navigation;
            }
        } catch (\Exception $e) {
            // SystemFileService also failed
        }

        // Return default empty navigation
        return [
            'type' => 'dropdown', // dropdown or megamenu
            'items' => []
        ];
    }

    /**
     * Normalize navigation items to use uniqueId consistently
     */
    public function normalizeNavigationItems(array $items): array {
        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            // Convert legacy pageId to uniqueId
            if (!isset($item['uniqueId']) && isset($item['pageId'])) {
                $item['uniqueId'] = $item['pageId'];
                unset($item['pageId']);
            }

            // Sanitize on READ as well as on write. Files written before the
            // scheme allowlist landed still hold whatever FILTER_SANITIZE_URL
            // let through — including javascript: — and Navigation.vue binds
            // item.url straight into :href. Gating only saveNavigation() would
            // leave every existing navigation.json live, so this doubles as the
            // one-off sanitation of stored data: no migration needed, and a file
            // that is never re-saved is still safe to render.
            if (array_key_exists('url', $item)) {
                $item['url'] = $this->sanitizeNavigationUrl($item['url']);
            }

            // Recursively normalize children
            if (isset($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->normalizeNavigationItems($item['children']);
            }

            $normalized[] = $item;
        }
        return $normalized;
    }

    /**
     * Save navigation structure for current language
     */
    public function saveNavigation(array $navigation, ?string $language = null): array {
        try {
            // Validate navigation structure
            $validated = $this->validateNavigation($navigation);

            $lang = $language ?? $this->getCurrentLanguage();
            $folder = $this->getLanguageFolder($lang);
            $navigationFile = 'navigation.json';
            $content = json_encode($validated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if ($folder->nodeExists($navigationFile)) {
                $file = $folder->get($navigationFile);
                $file->putContent($content);
            } else {
                $folder->newFile($navigationFile, $content);
            }

            // A nav-save changes which uniqueIds are part of the menu, so
            // the path-map (PermissionService) and tree (PageService) caches
            // become stale immediately. Flushing both forces a rebuild on
            // the next read; without it users see the old menu for up to
            // 5 minutes (PR-3 distributed TTL).
            $this->pagesCache?->clear();
            $this->permissionsCache?->clear();

            return $validated;
        } catch (NotPermittedException $e) {
            // Preserve the type so the controller can map it to HTTP 403 rather
            // than a generic 500 — a write attempt without permission is a
            // permission failure, not a server error (issue #86 follow-up).
            throw $e;
        } catch (\Exception $e) {
            throw new \Exception('Failed to save navigation: ' . $e->getMessage());
        }
    }

    /**
     * Validate and sanitize navigation structure
     */
    private function validateNavigation(array $navigation): array {
        $validated = [
            'type' => in_array($navigation['type'] ?? '', ['dropdown', 'megamenu'])
                ? $navigation['type']
                : 'dropdown',
            'items' => []
        ];

        if (isset($navigation['items']) && is_array($navigation['items'])) {
            $validated['items'] = $this->validateNavigationItems($navigation['items'], 1);
        }

        return $validated;
    }

    /**
     * Sanitize a navigation URL through the scheme allowlist.
     *
     * This used to be filter_var($url, FILTER_SANITIZE_URL), which only strips
     * characters that are illegal in a URL — it does NOT validate the scheme.
     * "javascript:alert(1)" and "data:text/html,<script>..." passed through
     * untouched, and "java\tscript:alert(1)" was actively NORMALISED into a
     * working payload by having its tab removed.
     *
     * navigation.json is admin-editable and its urls are rendered straight into
     * :href in Navigation.vue (getItemUrl returns item.url verbatim), so a
     * javascript: URL there is stored XSS that fires on click for every visitor.
     *
     * UrlSanitizer allows http(s), mailto, tel, sms, root-relative paths and
     * anchors, and returns "" for anything else. An empty url is stored as null
     * so the item keeps rendering as plain text instead of a dead link.
     */
    private function sanitizeNavigationUrl(mixed $url): ?string {
        if (!is_string($url)) {
            return null;
        }

        $safe = $this->urlSanitizer->sanitize($url);

        return $safe === '' ? null : $safe;
    }

    /**
     * Validate navigation items recursively (max 3 levels)
     */
    private function validateNavigationItems(array $items, int $level): array {
        if ($level > 3) {
            return [];
        }

        $validated = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            // Use uniqueId consistently (support legacy pageId for backwards compatibility)
            $uniqueId = $item['uniqueId'] ?? $item['pageId'] ?? null;

            $validatedItem = [
                'id' => $item['id'] ?? uniqid('nav_'),
                'title' => $item['title'] ?? '',
                'uniqueId' => $uniqueId,
                'url' => isset($item['url']) ? $this->sanitizeNavigationUrl($item['url']) : null,
                'target' => in_array($item['target'] ?? '', ['_blank', '_self']) ? $item['target'] : null,
                'children' => []
            ];

            // Recursively validate children
            if (isset($item['children']) && is_array($item['children']) && $level < 3) {
                $validatedItem['children'] = $this->validateNavigationItems($item['children'], $level + 1);
            }

            $validated[] = $validatedItem;
        }

        return $validated;
    }

    /**
     * Get IntraVox folder from user's perspective (mounted GroupFolder)
     *
     * IMPORTANT: Uses the user's mounted folder view to respect GroupFolder ACL
     */
    private function getIntraVoxFolder() {
        if (!$this->userId) {
            throw new \Exception('User not logged in');
        }

        // Get user's folder (this respects GroupFolder ACL)
        $userFolder = $this->rootFolder->getUserFolder($this->userId);

        // Get folder from user's perspective (mounted GroupFolder)
        return $userFolder->get('IntraVox');
    }

    /**
     * Get language folder
     */
    private function getLanguageFolder(string $language) {
        $sharedFolder = $this->getIntraVoxFolder();

        // Validate language: must be admin-enabled, otherwise fall back to default.
        if (!$this->languageService->isLanguageEnabled($language)) {
            $language = $this->languageService->getDefaultLanguage();
        }

        if (!$sharedFolder->nodeExists($language)) {
            // Create the language folder only if the user may write here. A
            // read-only GroupFolder / Team Folder member must NOT trigger a
            // failing newFolder() (issue #70) — throwing NotFound lets the
            // callers fall back to SystemFileService's system-level read.
            if (!$sharedFolder->isCreatable()) {
                throw new NotFoundException('Language folder does not exist and cannot be created: ' . $language);
            }
            $sharedFolder->newFolder($language);
        }

        return $sharedFolder->get($language);
    }

    /**
     * Get current user's language
     */
    public function getCurrentLanguage(): string {
        $languageCode = $this->l10n->getLanguageCode();

        // Extract base language (e.g., 'nl' from 'nl_NL')
        $baseLang = strtolower(substr($languageCode, 0, 2));

        // Return if admin-enabled, otherwise fall back to the universal default (English).
        return $this->languageService->isLanguageEnabled($baseLang)
            ? $baseLang
            : $this->languageService->getDefaultLanguage();
    }

    /**
     * Check if current user has edit permissions
     * Uses Nextcloud's permission system to check write access to navigation.json
     */
    public function canEdit(): bool {
        try {
            $lang = $this->getCurrentLanguage();
            $languageFolder = $this->getLanguageFolder($lang);

            // Gate on the FILE when it exists, not on the folder. Editing the
            // navigation writes navigation.json; an ACL can deny that single
            // file while the language folder stays writable, and a
            // folder-derived answer then showed an Edit button whose save
            // fails with NotPermittedException (issue #112). Same reasoning as
            // permissionsForPage() for pages (issue #70).
            //
            // Falls back to the folder only when the file genuinely does not
            // exist yet: creating it is a folder-level operation.
            //
            // Careful: under an ACL deny nodeExists() also returns false --
            // the file is hidden from this user's view, not absent. Treating
            // that as "not created yet" would hand back isCreatable() and put
            // the Edit button straight back. SystemFileService reads in system
            // context and therefore sees the file for what it is, so it settles
            // which of the two cases this is.
            if ($languageFolder->nodeExists('navigation.json')) {
                return $languageFolder->get('navigation.json')->isUpdateable();
            }

            if ($this->systemFileService->getNavigation($lang) !== null) {
                // Exists, but this user cannot see it: a deliberate deny.
                return false;
            }

            return $languageFolder->isCreatable();
        } catch (\Exception $e) {
            return false;
        }
    }
}
