<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Constants;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\SetupService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * API Controller for IntraVox pages
 *
 * All permission checks use Nextcloud's native filesystem permissions
 * which automatically respect GroupFolder ACL rules.
 */
class ApiController extends Controller {
    private PageService $pageService;
    private SetupService $setupService;
    private LoggerInterface $logger;
    private IConfig $config;
    private IGroupManager $groupManager;
    private IUserSession $userSession;

    public function __construct(
        string $appName,
        IRequest $request,
        PageService $pageService,
        SetupService $setupService,
        LoggerInterface $logger,
        IConfig $config,
        IGroupManager $groupManager,
        IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
        $this->pageService = $pageService;
        $this->setupService = $setupService;
        $this->logger = $logger;
        $this->config = $config;
        $this->groupManager = $groupManager;
        $this->userSession = $userSession;
    }

    /**
     * Check if current user is admin
     */
    private function isAdmin(): bool {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return false;
        }
        return $this->groupManager->isAdmin($user->getUID());
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function listPages(): DataResponse {
        try {
            $pages = $this->pageService->listPages();

            // PageService already includes permissions from Nextcloud's filesystem
            // Filter pages to only include those the user can read
            $filteredPages = [];
            foreach ($pages as $page) {
                if ($page['permissions']['canRead'] ?? false) {
                    $filteredPages[] = $page;
                }
            }

            return new DataResponse($filteredPages);
        } catch (\Exception $e) {
            // If IntraVox folder doesn't exist, return empty array
            // This allows the WelcomeScreen to be shown instead of an error
            if (strpos($e->getMessage(), 'IntraVox folder not found') !== false) {
                return new DataResponse([]);
            }
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPage(string $id): DataResponse {
        try {
            $page = $this->pageService->getPage($id);

            // PageService already includes permissions from Nextcloud's filesystem
            // which automatically respects GroupFolder ACL rules

            // Check if user can read (permissions are already in the page data)
            if (!($page['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            // Add breadcrumb to page response
            try {
                $page['breadcrumb'] = $this->pageService->getBreadcrumb($id);
            } catch (\Exception $e) {
                // Breadcrumb failed, but page is still valid
                $page['breadcrumb'] = [];
            }

            return new DataResponse($page);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        }
    }

    /**
     * @NoAdminRequired
     */
    public function createPage(): DataResponse {
        try {
            $data = $this->request->getParams();

            // Extract parentPath from request if provided
            $parentPath = $data['parentPath'] ?? null;
            unset($data['parentPath']); // Remove from data array to avoid storing it

            // Check create permission on parent path using Nextcloud's filesystem permissions
            $checkPath = $parentPath ?? '';
            $folderPerms = $this->pageService->getFolderPermissions($checkPath);
            if (!$folderPerms['canCreate']) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot create pages in this location'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $page = $this->pageService->createPage($data, $parentPath);
            return new DataResponse($page, Http::STATUS_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     */
    public function updatePage(string $id): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($id);

            // Check write permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot edit this page'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $data = $this->request->getParams();
            $page = $this->pageService->updatePage($id, $data);
            return new DataResponse($page);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     */
    public function deletePage(string $id): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($id);

            // Check delete permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canDelete'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot delete this page'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $this->pageService->deletePage($id);
            return new DataResponse(['success' => true]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Upload media (image or video) for a page
     * Unified endpoint that stores all media in a single 'media' folder
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function uploadMedia(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check write permission (uploading media requires write access)
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot upload media to this page'],
                    Http::STATUS_FORBIDDEN
                );
            }

            // Try 'media' field first, then fall back to 'image' or 'video' for compatibility
            $file = $this->request->getUploadedFile('media');
            if (!$file) {
                $file = $this->request->getUploadedFile('image');
            }
            if (!$file) {
                $file = $this->request->getUploadedFile('video');
            }

            if (!$file) {
                throw new \InvalidArgumentException('No media file provided');
            }

            if (empty($file['tmp_name'])) {
                throw new \InvalidArgumentException('File upload failed - tmp_name is empty. Upload error: ' . ($file['error'] ?? 'unknown'));
            }

            $filename = $this->pageService->uploadMedia($pageId, $file);
            return new DataResponse(['filename' => $filename], Http::STATUS_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get server upload limit
     * Returns the effective upload limit in bytes
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUploadLimit(): DataResponse {
        try {
            $limit = $this->pageService->getUploadLimit();
            return new DataResponse([
                'limit' => $limit,
                'limitMB' => round($limit / (1024 * 1024), 1)
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get media (image or video) for a page
     * Unified endpoint that serves all media from a single 'media' folder
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getMedia(string $pageId, string $filename) {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            return $this->pageService->getMedia($pageId, $filename);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPageVersions(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $versions = $this->pageService->getPageVersions($pageId);
            return new DataResponse($versions);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     */
    public function restorePageVersion(string $pageId, string $timestamp): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check write permission (restoring requires write access)
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot restore this page'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $page = $this->pageService->restorePageVersion($pageId, (int)$timestamp);
            return new DataResponse($page);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updateVersionLabel(string $pageId, string $timestamp): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check write permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $label = $this->request->getParam('label');
            $this->pageService->updateVersionLabel($pageId, (int)$timestamp, $label);
            return new DataResponse(['success' => true]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getVersionContent(string $pageId, string $timestamp): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $content = $this->pageService->getVersionContent($pageId, (int)$timestamp);
            return new DataResponse($content);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getCurrentPageContent(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $content = $this->pageService->getCurrentPageContent($pageId);
            return new DataResponse($content);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPageMetadata(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $metadata = $this->pageService->getPageMetadata($pageId);
            return new DataResponse($metadata);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     */
    public function updatePageMetadata(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check write permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $metadata = $this->request->getParams();
            $updated = $this->pageService->updatePageMetadata($pageId, $metadata);
            return new DataResponse($updated);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getMetaVoxStatus(): DataResponse {
        try {
            $appManager = \OC::$server->getAppManager();
            $installed = $appManager->isInstalled('metavox') && $appManager->isEnabledForUser('metavox');

            return new DataResponse([
                'installed' => $installed,
                'enabled' => $installed
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['installed' => false, 'enabled' => false],
                Http::STATUS_OK
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function checkPageCacheStatus(string $pageId): DataResponse {
        try {
            $status = $this->pageService->checkPageCacheStatus($pageId);
            return new DataResponse($status);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function searchPages(string $query): DataResponse {
        try {
            if (strlen($query) < 2) {
                return new DataResponse([
                    'results' => [],
                    'query' => $query,
                    'message' => 'Query too short'
                ]);
            }

            $results = $this->pageService->searchPages($query);

            // Filter results based on Nextcloud's permissions (already in the results)
            $filteredResults = [];
            foreach ($results as $result) {
                if ($result['permissions']['canRead'] ?? false) {
                    $filteredResults[] = $result;
                }
            }

            return new DataResponse([
                'results' => $filteredResults,
                'query' => $query,
                'count' => count($filteredResults)
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getBreadcrumb(string $id): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($id);

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $breadcrumb = $this->pageService->getBreadcrumb($id);
            return new DataResponse($breadcrumb);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        }
    }

    /**
     * Get the full page tree structure
     * Used for the "View structure" modal to show all accessible pages
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPageTree(?string $currentPageId = null): DataResponse {
        try {
            $tree = $this->pageService->getPageTree($currentPageId);

            // Filter tree to only include pages user can read
            // PageService already includes Nextcloud permissions in each page
            $filteredTree = $this->filterTreeByPermissions($tree);

            return new DataResponse([
                'tree' => $filteredTree
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Filter page tree to only include pages user can read
     */
    private function filterTreeByPermissions(array $tree): array {
        $filtered = [];
        foreach ($tree as $item) {
            if ($item['permissions']['canRead'] ?? false) {
                if (!empty($item['children'])) {
                    $item['children'] = $this->filterTreeByPermissions($item['children']);
                }
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

    /**
     * Get current user's permissions for IntraVox
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPermissions(?string $path = null): DataResponse {
        try {
            $checkPath = $path ?? '';
            // Use Nextcloud's native filesystem permissions
            $permissions = $this->pageService->getFolderPermissions($checkPath);

            return new DataResponse([
                'path' => $checkPath,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Run IntraVox setup (create GroupFolder)
     * Admin only - creates the IntraVox GroupFolder
     */
    public function runSetup(): DataResponse {
        // Security: Only admins can run setup
        if (!$this->isAdmin()) {
            return new DataResponse(
                ['error' => 'Admin access required'],
                Http::STATUS_FORBIDDEN
            );
        }

        try {
            $this->logger->info('[ApiController] Running setup');

            $result = $this->setupService->setup();

            return new DataResponse([
                'success' => true,
                'message' => 'Setup completed successfully',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Setup failed: ' . $e->getMessage());
            return new DataResponse(
                [
                    'success' => false,
                    'message' => 'Setup failed: ' . $e->getMessage(),
                ],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get video domain whitelist
     *
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function getVideoDomains(): DataResponse {
        $domains = $this->config->getAppValue(
            Application::APP_ID,
            'video_domains',
            Constants::getDefaultVideoDomainsJson()
        );

        // Decode the stored JSON
        $decoded = json_decode($domains, true);

        // Only use defaults if JSON decode FAILED (null), not for empty array
        // This allows admins to explicitly block all video embeds by removing all domains
        if ($decoded === null) {
            $decoded = Constants::DEFAULT_VIDEO_DOMAINS;
        }

        return new DataResponse(['domains' => $decoded]);
    }

    /**
     * Get domain category for warning system
     * Returns: recommended, commercial, discouraged, or blocked
     */
    private function getDomainCategory(string $host): array {
        // Category 1: Recommended - privacy-friendly platforms
        $recommended = [
            'video.edu.nl',
            'peertube.tv',
            'framatube.org',
            'tilvids.com',
            'peertube.social',
            'video.ploud.fr',
            'diode.zone',
            'tube.privacytools.io',
            'peertube.debian.social',
            'video.linux.it',
        ];

        // Category 2: Commercial but relatively safe (business platforms)
        $commercial = [
            'vimeo.com',
            'wistia.com',
            'loom.com',
            'streamable.com',
            'bunny.net',
            'bunnycdn.com',
        ];

        // Category 3: Discouraged - major tracking/privacy concerns
        $discouraged = [
            'youtube.com',
            'youtu.be',
            'dailymotion.com',
            'tiktok.com',
            'facebook.com',
            'fb.watch',
            'instagram.com',
            'twitter.com',
            'x.com',
            'twitch.tv',
        ];

        foreach ($recommended as $pattern) {
            if (str_contains($host, $pattern)) {
                return ['category' => 'recommended', 'level' => 1];
            }
        }

        foreach ($commercial as $pattern) {
            if (str_contains($host, $pattern)) {
                return ['category' => 'commercial', 'level' => 2];
            }
        }

        foreach ($discouraged as $pattern) {
            if (str_contains($host, $pattern)) {
                return ['category' => 'discouraged', 'level' => 3];
            }
        }

        // Unknown domains - treat as custom PeerTube instances (allowed)
        return ['category' => 'custom', 'level' => 1];
    }

    /**
     * Set video domain whitelist
     * Warning-based system: all domains allowed, but with category warnings
     *
     * @NoAdminRequired
     */
    public function setVideoDomains(): DataResponse {
        // Manual admin check since @AuthorizedAdminSetting has dependency issues
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change video domain settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get domains from request - handle both JSON body and form data
        $domains = $this->request->getParam('domains');

        // If domains is null, try parsing JSON body directly
        if ($domains === null) {
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);
            $domains = $data['domains'] ?? [];
            $this->logger->debug('[ApiController] Parsed JSON body for domains: ' . json_encode($domains));
        }

        // Ensure domains is an array
        if (!is_array($domains)) {
            $domains = [];
        }

        // Validate and categorize domains
        $validDomains = [];
        $invalidDomains = [];
        $domainCategories = [];
        $warnings = [];

        foreach ($domains as $domain) {
            $domain = trim($domain);
            if (empty($domain)) {
                continue;
            }

            // Must be HTTPS - this is a hard requirement for security
            if (!str_starts_with($domain, 'https://')) {
                $invalidDomains[] = $domain . ' (HTTPS required for security)';
                continue;
            }

            // Valid URL check
            if (!filter_var($domain, FILTER_VALIDATE_URL)) {
                $invalidDomains[] = $domain . ' (invalid URL format)';
                continue;
            }

            // Get domain category
            $parsedUrl = parse_url($domain);
            $host = $parsedUrl['host'] ?? '';
            $category = $this->getDomainCategory($host);

            // Remove trailing slash
            $domain = rtrim($domain, '/');

            $validDomains[] = $domain;
            $domainCategories[$domain] = $category;

            // Add warnings for non-recommended domains
            if ($category['category'] === 'commercial') {
                $warnings[] = $host . ': Commercial platform - consider privacy-friendly alternatives like PeerTube';
            } elseif ($category['category'] === 'discouraged') {
                $warnings[] = $host . ': This platform has significant tracking and privacy concerns. Consider using PeerTube instead.';
            }
        }

        // If there are invalid domains (HTTP or malformed), return error
        if (!empty($invalidDomains)) {
            return new DataResponse([
                'success' => false,
                'message' => 'Some domains are not valid: ' . implode(', ', $invalidDomains),
                'invalidDomains' => $invalidDomains,
            ], Http::STATUS_BAD_REQUEST);
        }

        // Save domains - all valid HTTPS domains are allowed
        $this->config->setAppValue(
            Application::APP_ID,
            'video_domains',
            json_encode(array_unique($validDomains))
        );

        $this->logger->info('[ApiController] Video domains updated: ' . implode(', ', $validDomains));

        // Return success with warnings if applicable
        $response = [
            'success' => true,
            'domains' => $validDomains,
            'categories' => $domainCategories,
        ];

        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        return new DataResponse($response);
    }
}
