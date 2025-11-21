<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;

class NavigationService {
    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private SetupService $setupService;
    private IL10N $l10n;
    private string $userId;
    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        SetupService $setupService,
        IL10N $l10n,
        ?string $userId
    ) {
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->setupService = $setupService;
        $this->l10n = $l10n;
        $this->userId = $userId ?? '';
    }

    /**
     * Get navigation structure for current language
     */
    public function getNavigation(?string $language = null): array {
        try {
            $lang = $language ?? $this->getCurrentLanguage();
            $folder = $this->getLanguageFolder($lang);
            $navigationFile = 'navigation.json';

            if ($folder->nodeExists($navigationFile)) {
                $file = $folder->get($navigationFile);
                $content = $file->getContent();
                $navigation = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($navigation)) {
                    return $navigation;
                }
            }

            // Return default empty navigation
            return [
                'type' => 'dropdown', // dropdown or megamenu
                'items' => []
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to get navigation: ' . $e->getMessage());
        }
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

            return $validated;
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

            $validatedItem = [
                'id' => $item['id'] ?? uniqid('nav_'),
                'title' => htmlspecialchars($item['title'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'pageId' => $item['pageId'] ?? null,
                'url' => isset($item['url']) ? filter_var($item['url'], FILTER_SANITIZE_URL) : null,
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
     * Get IntraVox folder (shared)
     */
    private function getIntraVoxFolder() {
        return $this->setupService->getSharedFolder();
    }

    /**
     * Get language folder
     */
    private function getLanguageFolder(string $language) {
        $sharedFolder = $this->getIntraVoxFolder();

        // Validate language
        if (!in_array($language, self::SUPPORTED_LANGUAGES)) {
            $language = 'nl'; // Default to Dutch
        }

        if (!$sharedFolder->nodeExists($language)) {
            // Create language folder if it doesn't exist
            $sharedFolder->newFolder($language);
        }

        return $sharedFolder->get($language);
    }

    /**
     * Get current user's language
     */
    private function getCurrentLanguage(): string {
        $languageCode = $this->l10n->getLanguageCode();

        // Extract base language (e.g., 'nl' from 'nl_NL')
        $baseLang = strtolower(substr($languageCode, 0, 2));

        // Return if supported, otherwise default to Dutch
        return in_array($baseLang, self::SUPPORTED_LANGUAGES) ? $baseLang : 'nl';
    }

    /**
     * Check if current user has edit permissions
     */
    public function canEdit(): bool {
        try {
            $folder = $this->getIntraVoxFolder();
            return $folder->isUpdateable();
        } catch (\Exception $e) {
            return false;
        }
    }
}
