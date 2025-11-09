<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use OCP\IConfig;

class FooterService {
    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private string $userId;
    private SetupService $setupService;
    private IConfig $config;
    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];
    private const DEFAULT_LANGUAGE = 'en';

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        SetupService $setupService,
        IConfig $config,
        ?string $userId
    ) {
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->setupService = $setupService;
        $this->config = $config;
        $this->userId = $userId ?? '';
    }

    /**
     * Get the user's language preference
     */
    private function getUserLanguage(): string {
        if (!$this->userId) {
            return self::DEFAULT_LANGUAGE;
        }

        $lang = $this->config->getUserValue($this->userId, 'core', 'lang', self::DEFAULT_LANGUAGE);

        // Normalize language code (e.g., 'en_US' -> 'en')
        $lang = strtolower(substr($lang, 0, 2));

        // Check if language is supported, fallback to default if not
        if (!in_array($lang, self::SUPPORTED_LANGUAGES)) {
            $lang = self::DEFAULT_LANGUAGE;
        }

        return $lang;
    }

    /**
     * Get footer content for current user's language
     */
    public function getFooter(): array {
        $language = $this->getUserLanguage();

        try {
            $groupFolder = $this->setupService->getGroupFolder();
            $languageFolder = $groupFolder->get($language);

            // Try to get footer.json
            try {
                $footerFile = $languageFolder->get('footer.json');
                $content = $footerFile->getContent();
                $data = json_decode($content, true);

                if ($data && isset($data['content'])) {
                    return [
                        'content' => $data['content'],
                        'language' => $language,
                        'canEdit' => $this->canEditFooter()
                    ];
                }
            } catch (NotFoundException $e) {
                // Footer file doesn't exist yet, return empty
            }

            return [
                'content' => '',
                'language' => $language,
                'canEdit' => $this->canEditFooter()
            ];
        } catch (\Exception $e) {
            throw new \Exception('Could not load footer: ' . $e->getMessage());
        }
    }

    /**
     * Save footer content for current user's language
     */
    public function saveFooter(string $content): array {
        if (!$this->canEditFooter()) {
            throw new \Exception('You do not have permission to edit the footer');
        }

        $language = $this->getUserLanguage();

        try {
            $groupFolder = $this->setupService->getGroupFolder();
            $languageFolder = $groupFolder->get($language);

            // Sanitize content
            $sanitizedContent = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

            $data = [
                'content' => $sanitizedContent,
                'modified' => time(),
                'modifiedBy' => $this->userId
            ];

            // Try to get existing footer file or create new one
            try {
                $footerFile = $languageFolder->get('footer.json');
                $footerFile->putContent(json_encode($data, JSON_PRETTY_PRINT));
            } catch (NotFoundException $e) {
                // Create new footer file
                $languageFolder->newFile('footer.json', json_encode($data, JSON_PRETTY_PRINT));
            }

            return [
                'content' => $sanitizedContent,
                'language' => $language,
                'canEdit' => true
            ];
        } catch (\Exception $e) {
            throw new \Exception('Could not save footer: ' . $e->getMessage());
        }
    }

    /**
     * Check if current user can edit footer
     */
    private function canEditFooter(): bool {
        if (!$this->userId) {
            return false;
        }

        $user = $this->userSession->getUser();
        if (!$user) {
            return false;
        }

        // Check if user is in IntraVox Admins group
        $groupManager = \OC::$server->getGroupManager();
        $adminGroup = $groupManager->get('IntraVox Admins');

        return $adminGroup && $adminGroup->inGroup($user);
    }
}
