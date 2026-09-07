<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use OCP\IConfig;

class FooterService {
    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private string $userId;
    private SetupService $setupService;
    private SystemFileService $systemFileService;
    private IConfig $config;
    private LanguageService $languageService;
    private HtmlSanitizer $htmlSanitizer;
    private const DEFAULT_LANGUAGE = 'en';

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        SetupService $setupService,
        SystemFileService $systemFileService,
        IConfig $config,
        LanguageService $languageService,
        HtmlSanitizer $htmlSanitizer,
        ?string $userId
    ) {
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->setupService = $setupService;
        $this->systemFileService = $systemFileService;
        $this->config = $config;
        $this->languageService = $languageService;
        $this->htmlSanitizer = $htmlSanitizer;
        $this->userId = $userId ?? '';
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
     * Get the user's language preference
     */
    private function getUserLanguage(): string {
        if (!$this->userId) {
            return self::DEFAULT_LANGUAGE;
        }

        $lang = $this->config->getUserValue($this->userId, 'core', 'lang', self::DEFAULT_LANGUAGE);

        // Normalize language code (e.g., 'en_US' -> 'en')
        $lang = strtolower(substr($lang, 0, 2));

        // Check if language is enabled by admin, fallback to default if not
        if (!$this->languageService->isLanguageEnabled($lang)) {
            $lang = self::DEFAULT_LANGUAGE;
        }

        return $lang;
    }

    /**
     * Get footer content for current user's language
     *
     * First tries to read via user's folder view (respects ACL).
     * Falls back to SystemFileService for users with limited access (e.g., department-only).
     */
    public function getFooter(): array {
        $language = $this->getUserLanguage();

        // Try to read via user's folder view first (respects ACL)
        try {
            $groupFolder = $this->getIntraVoxFolder();
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
            // User doesn't have access to the language root folder
            // Fall back to SystemFileService
        }

        // Fallback: Use SystemFileService to read footer via system context
        // This allows users with department-level access to still see the footer.
        //
        // Gated for the same reason as the navigation fallback: it bypasses
        // ACLs by design, so an explicit deny on footer.json must not be
        // overridden by it (issue #112).
        $uid = $this->userSession->getUser()?->getUID();
        if ($uid !== null
            && !$this->systemFileService->mayUseSystemFallback($uid, $language, 'footer.json')) {
            return [
                'content' => '',
                'language' => $language,
                'canEdit' => false
            ];
        }

        try {
            $data = $this->systemFileService->getFooter($language);

            if ($data !== null && isset($data['content'])) {
                return [
                    'content' => $data['content'],
                    'language' => $language,
                    'canEdit' => false // Users without root access cannot edit footer
                ];
            }
        } catch (\Exception $e) {
            // SystemFileService also failed
        }

        // Return empty footer
        return [
            'content' => '',
            'language' => $language,
            'canEdit' => false
        ];
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
            $groupFolder = $this->getIntraVoxFolder();
            $languageFolder = $groupFolder->get($language);

            // Sanitize server-side. The comment here used to say the frontend had
            // already done it with DOMPurify -- which is true of the editor and
            // irrelevant to security: POST /api/footer is a plain endpoint and a
            // crafted request never runs that code. The footer is rendered with
            // v-html on every page, so a stored script would run for every logged-in
            // viewer.
            //
            // Text widgets have always been sanitized here (PageShapeSanitizer:306
            // calls the same service); the footer was the one v-html surface that
            // was not. Same renderer, same trust, so the same treatment.
            $content = $this->htmlSanitizer->sanitize($content);

            $data = [
                'content' => $content,
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
                'content' => $content,
                'language' => $language,
                'canEdit' => true
            ];
        } catch (\Exception $e) {
            throw new \Exception('Could not save footer: ' . $e->getMessage());
        }
    }

    /**
     * Check if current user can edit footer
     * Uses Nextcloud's permission system to check write access to footer.json
     */
    private function canEditFooter(): bool {
        if (!$this->userId) {
            return false;
        }

        $user = $this->userSession->getUser();
        if (!$user) {
            return false;
        }

        try {
            $language = $this->getUserLanguage();
            $groupFolder = $this->getIntraVoxFolder();
            $languageFolder = $groupFolder->get($language);

            // Gate on the FILE when it exists, not on the folder -- an ACL can
            // deny footer.json while the language folder stays writable, which
            // showed an Edit affordance whose save then fails (issue #112).
            if ($languageFolder->nodeExists('footer.json')) {
                return $languageFolder->get('footer.json')->isUpdateable();
            }

            // An ACL deny also makes nodeExists() false, so confirm through
            // system context before treating this as "not created yet".
            if ($this->systemFileService->getFooter($language) !== null) {
                return false;
            }

            return $languageFolder->isCreatable();
        } catch (\Exception $e) {
            // If we can't access the folder, user can't edit
            return false;
        }
    }
}
