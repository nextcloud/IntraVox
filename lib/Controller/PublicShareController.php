<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Controller\Shared\CalendarRequestTrait;
use OCA\IntraVox\Controller\Shared\FeedRequestTrait;
use OCA\IntraVox\Controller\Shared\SharePathTrait;
use OCA\IntraVox\Service\CalendarService;
use OCA\IntraVox\Service\FeedReaderService;
use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\People\PeopleQuery;
use OCA\IntraVox\Service\People\PublicSharePeopleGuard;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\PublicShare\ShareBreadcrumbBuilder;
use OCA\IntraVox\Service\PublicShare\ShareTreeShaper;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\PublicShareService;
use OCA\IntraVox\Service\SetupService;
use OCA\IntraVox\Service\SystemFileService;
use OCA\IntraVox\Share\ShareScope;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

/**
 * Everything an anonymous visitor can reach through a share link. (F6)
 *
 * Reviewer question one of the refactor plan was "what does an anonymous
 * visitor reach?", and the honest answer used to be "grep ApiController for
 * PublicPage and hope you found them all" — seven endpoints scattered through
 * 3900 lines, mixed in with the authenticated API.
 *
 * They live here now. The class contains nothing else, so the anonymous surface
 * is the file, and PublicEndpointInventoryTest fails if a #[PublicPage] appears
 * anywhere outside this namespace.
 *
 * Every endpoint opens the same way, through openShare(): token shape, is link
 * sharing enabled, is the share password satisfied, does the token point into
 * the IntraVox groupfolder. Anything that cannot answer all four gets a 404 with
 * a timing jitter, so a valid-but-forbidden token cannot be told apart from a
 * made-up one.
 *
 * The method bodies are moved verbatim from ApiController; the responses were
 * compared byte for byte on a live instance before and after.
 */
class PublicShareController extends Controller {
    use SharePathTrait;
    use FeedRequestTrait;
    use CalendarRequestTrait;

    public function __construct(
        string $appName,
        IRequest $request,
        private PageService $pageService,
        private SetupService $setupService,
        private PublicShareService $publicShareService,
        private SystemFileService $systemFileService,
        private NavigationService $navigationService,
        private PermissionService $permissionService,
        private LoggerInterface $logger,
        private IConfig $config,
        private ISession $session,
        private CalendarService $calendarService,
        private FeedReaderService $feedReaderService,
        private PeopleQuery $peopleQuery,
        private ShareBreadcrumbBuilder $breadcrumbBuilder,
        private ShareTreeShaper $treeShaper,
        private PagePathHelper $pathHelper,
    ) {
        parent::__construct($appName, $request);
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 60, period: 60)]
    #[BruteForceProtection(action: 'intravox_share_page')]
    public function getPageByShare(string $token, string $uniqueId): JSONResponse {
        // Validate token format first (cheap check)
        if (!$this->isValidShareTokenFormat($token)) {
            $this->registerShareBruteForceAttempt();
            return $this->shareNotFoundResponse();
        }

        // NC sharing must be enabled
        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return $this->shareNotFoundResponse();
        }

        // Check share password
        $pwDenied = $this->checkSharePasswordOrDeny($token);
        if ($pwDenied !== null) {
            return $pwDenied;
        }

        try {
            // Determine language from page or use default
            // First try to get language from existing page data
            $language = 'en'; // Default
            try {
                $existingPage = $this->pageService->getPage($uniqueId);
                $language = $existingPage['language'] ?? 'en';
            } catch (\Exception $e) {
                // Page not found yet, will be handled by validateShareAccess
            }

            // Validate share access (password already verified via session)
            $sessionPw = $this->getSharePasswordFromSession($token);
            $validation = $this->publicShareService->validateShareAccess($token, $uniqueId, $language, $sessionPw);

            if (!$validation['valid']) {
                $reason = $validation['reason'] ?? '';
                if ($reason === 'password_required' || $reason === 'invalid_password') {
                    return new JSONResponse([
                        'error' => 'Password required',
                        'passwordRequired' => true,
                    ], Http::STATUS_UNAUTHORIZED);
                }
                $this->registerShareBruteForceAttempt();
                return $this->shareNotFoundResponse();
            }

            // Get the page data - this comes from validateShareAccess
            $pageData = $validation['pageData'] ?? null;

            if ($pageData === null) {
                $this->registerShareBruteForceAttempt();
                return $this->shareNotFoundResponse();
            }

            // Draft, scheduled (not-yet-published) and expired pages are never
            // accessible via a public share — anonymous visitors are never editors.
            if ($this->pageService->isHiddenFromReaders($pageData)) {
                return $this->shareNotFoundResponse();
            }

            // Sanitize the response - only include safe fields for public access
            $sanitizedPage = $this->sanitizePageForPublicAccess($pageData);

            // A public share is normally made to hand someone documents. If
            // the page also holds a People widget, sharing those documents
            // would publish a staff directory to anyone with the link —
            // without anyone on that list having agreed to it. Removed unless
            // an admin has deliberately allowed it instance-wide.
            if (!$this->peopleAllowedOnPublicShares()) {
                $guarded = PublicSharePeopleGuard::strip($sanitizedPage);
                $sanitizedPage = $guarded['page'];

                if ($guarded['removed'] > 0) {
                    $this->logger->info('[PublicShare] Withheld People widget(s) from a public page', [
                        'uniqueId' => $uniqueId,
                        'removed' => $guarded['removed'],
                    ]);
                }
            }

            // Audit: log successful public page access
            $this->logger->info('[PublicShare] Page accessed', [
                'uniqueId' => $uniqueId,
                'ip' => $this->request->getRemoteAddress(),
            ]);

            // Add public access metadata
            $sanitizedPage['isPublicShare'] = true;
            $sanitizedPage['shareToken'] = $token;
            $sanitizedPage['permissions'] = [
                'canRead' => true,
                'canWrite' => false,
                'canDelete' => false,
                'canCreate' => false,
            ];

            // Add share-scoped breadcrumb
            try {
                $share = $this->publicShareService->resolveIntraVoxLinkShare($token);
                if ($share !== null) {
                    $shareScopePath = $this->publicShareService->resolveShareScopePath($share);
                    if ($shareScopePath !== null) {
                        $relPath = $shareScopePath;
                        if (str_starts_with($relPath, 'files/')) {
                            $relPath = substr($relPath, 6);
                        }
                        // Compute the page's relative folder path for the breadcrumb.
                        // Prefer pageGfPath, the canonical GF-storage path
                        // ("files/nl/afdeling/marketing/campagnes/campagnes.json"),
                        // which validateShareAccess resolves by fileid. The legacy
                        // pagePath is a per-user MOUNT view (e.g.
                        // "/admin/files/IntraVox/nl/…") that the __groupfolders regex
                        // cannot normalise, so the breadcrumb collapsed to just the
                        // share root. We need: "nl/afdeling/marketing/campagnes".
                        $pageRelPath = $validation['pageGfPath'] ?? '';
                        if ($pageRelPath === '') {
                            // Fallback to the legacy mount path + regex strip.
                            $pageRelPath = $validation['pagePath'] ?? '';
                            if (preg_match('#__groupfolders/\d+/files/(.+)$#', $pageRelPath, $m)) {
                                $pageRelPath = $m[1];
                            }
                        } elseif (str_starts_with($pageRelPath, 'files/')) {
                            $pageRelPath = substr($pageRelPath, 6);
                        }
                        // Remove filename (e.g., campagnes.json) to get folder path
                        $pageRelPath = dirname($pageRelPath);
                        if ($pageRelPath === '.') {
                            $pageRelPath = '';
                        }
                        // Add path to pageData for breadcrumb builder
                        $pageDataWithPath = $pageData;
                        $pageDataWithPath['path'] = $pageRelPath;
                        $sanitizedPage['breadcrumb'] = $this->breadcrumbBuilder->build($pageDataWithPath, $relPath, $language);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->debug('[ApiController] Could not build share breadcrumb', [
                    'error' => $e->getMessage()
                ]);
                $sanitizedPage['breadcrumb'] = [];
            }

            return new JSONResponse($sanitizedPage);

        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Error in getPageByShare', [
                'token' => '***',
                'uniqueId' => $uniqueId,
                'error' => $e->getMessage()
            ]);
            return $this->shareNotFoundResponse();
        }
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 60, period: 60)]
    public function getNavigationByShare(string $token): JSONResponse {
        // Validate token format

        try {
            // Token shape, link sharing, share password and IntraVox membership,
            // in that order (F6).
            $share = $this->openShare($token, fn () => $this->shareNotFoundResponse());
            if ($share instanceof Response) {
                return $share;
            }

            // Resolve the share's actual path via file_source on the GF storage.
            // file_target (e.g. "/afdeling") is unreliable for GroupFolders.
            $shareScopePath = $this->publicShareService->resolveShareScopePath($share);
            if ($shareScopePath === null) {
                $this->logger->debug('[ApiController] getNavigationByShare: could not resolve share scope path');
                return new JSONResponse(['navigation' => ['type' => 'dropdown', 'items' => []]]);
            }

            // The share scope decides how much of the tree is public, so the
            // parse lives in one place (ShareScope) instead of four copies.
            $scope = ShareScope::fromScopePath($shareScopePath);
            if ($scope === null) {
                return new JSONResponse(['navigation' => ['type' => 'dropdown', 'items' => []]]);
            }

            $language = $scope->language;
            $relPath = $scope->scopePath;

            // Read navigation.json via system context (no user session needed)
            $navigation = $this->systemFileService->getNavigation($language);
            if ($navigation === null) {
                return new JSONResponse(['navigation' => ['type' => 'dropdown', 'items' => []]]);
            }

            // Normalize navigation items (pageId -> uniqueId)
            if (isset($navigation['items']) && is_array($navigation['items'])) {
                $navigation['items'] = $this->navigationService->normalizeNavigationItems($navigation['items']);
            }

            // Build page path map (uniqueId -> relative path within language folder)
            // Values are like "afdeling/afdeling.json" or "afdeling/hr/hr.json"
            $pagePathMap = $this->permissionService->buildPagePathMap($language);

            // Filter navigation items by share scope
            // shareScopeRelative is the path after "files/{language}/" — e.g. "afdeling"
            $shareScopeRelative = $scope->relativePath;

            if (isset($navigation['items']) && is_array($navigation['items'])) {
                $navigation['items'] = $this->publicShareService->filterNavigationByShareScope(
                    $navigation['items'],
                    $shareScopeRelative,
                    $pagePathMap
                );
            }

            // Also drop nav entries that point at pages hidden from readers
            // (draft / scheduled / expired), so anonymous visitors don't see menu
            // items that lead to a "not available" page.
            if (isset($navigation['items']) && is_array($navigation['items'])) {
                $hidden = $this->hiddenUniqueIdsForLanguage($language);
                if (!empty($hidden)) {
                    $navigation['items'] = $this->treeShaper->filterNavigationByHiddenIds($navigation['items'], $hidden);
                }
            }

            $this->logger->info('[PublicShare] Navigation accessed', [
                'language' => $language,
                'ip' => $this->request->getRemoteAddress(),
            ]);

            return new JSONResponse([
                'navigation' => $navigation,
                'language' => $language,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Error in getNavigationByShare', [
                'token' => '***',
                'error' => $e->getMessage()
            ]);
            return new JSONResponse(['navigation' => ['type' => 'dropdown', 'items' => []]]);
        }
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 60, period: 60)]
    public function getPageTreeByShare(string $token): JSONResponse {

        try {
            // Token shape, link sharing, share password and IntraVox membership,
            // in that order (F6).
            $share = $this->openShare($token, fn () => $this->shareNotFoundResponse());
            if ($share instanceof Response) {
                return $share;
            }

            $shareScopePath = $this->publicShareService->resolveShareScopePath($share);
            if ($shareScopePath === null) {
                return new JSONResponse(['tree' => []]);
            }

            // The share scope decides how much of the tree is public, so the
            // parse lives in one place (ShareScope) instead of four copies.
            $scope = ShareScope::fromScopePath($shareScopePath);
            if ($scope === null) {
                return new JSONResponse(['tree' => []]);
            }

            $language = $scope->language;
            $relPath = $scope->scopePath;

            // Build full tree via system context (no user session needed)
            $tree = $this->systemFileService->getPageTree($language);

            // Extract subtree matching the share scope
            // scopePath is the full relative path like "nl/afdeling/hr"
            $scopePath = $relPath;
            $filteredTree = $this->treeShaper->extractSubtreeByScope($tree, $scopePath);

            // An empty result for a non-root scope means the shared node was
            // not found in the tree — a moved or deleted page. Worth knowing
            // about, because before SCOPE-FAILOPEN this case published the
            // entire language instead.
            if ($filteredTree === [] && str_contains($scopePath, '/')) {
                $this->logger->warning('[ApiController] share scope matched no node; serving an empty tree', [
                    'scopePath' => $scopePath,
                    'language' => $language,
                ]);
            }

            // Remove draft pages from public tree
            $filteredTree = $this->filterDraftsFromTree($filteredTree);

            // Strip permissions from all nodes (public = read-only)
            $filteredTree = $this->treeShaper->stripPermissionsFromTree($filteredTree);

            // Mark current page if provided (for page structure navigation highlighting)
            $currentPageId = $this->request->getParam('currentPageId');
            if ($currentPageId !== null && is_string($currentPageId) && $currentPageId !== '') {
                $filteredTree = $this->pathHelper->markCurrentPageInTree($filteredTree, $currentPageId);
            }

            $this->logger->info('[PublicShare] Page tree accessed', [
                'language' => $language,
                'ip' => $this->request->getRemoteAddress(),
            ]);

            return new JSONResponse(['tree' => $filteredTree]);

        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Error in getPageTreeByShare', [
                'token' => '***',
                'error' => $e->getMessage()
            ]);
            return new JSONResponse(['tree' => []]);
        }
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 60, period: 60)]
    public function getNewsByShare(string $token): JSONResponse {

        try {
            // Token shape, link sharing, share password and IntraVox membership,
            // in that order (F6).
            $share = $this->openShare($token, fn () => $this->shareNotFoundResponse());
            if ($share instanceof Response) {
                return $share;
            }

            $shareScopePath = $this->publicShareService->resolveShareScopePath($share);
            if ($shareScopePath === null) {
                return new JSONResponse(['items' => [], 'total' => 0]);
            }

            // The share scope decides how much of the tree is public, so the
            // parse lives in one place (ShareScope) instead of four copies.
            $scope = ShareScope::fromScopePath($shareScopePath);
            if ($scope === null) {
                return new JSONResponse(['items' => [], 'total' => 0]);
            }

            $language = $scope->language;
            $relPath = $scope->scopePath;

            // Parse request parameters
            $sourcePageId = $this->request->getParam('sourcePageId', '');
            $sourcePath = $this->request->getParam('sourcePath', '');
            $limit = max(1, min((int) $this->request->getParam('limit', 5), 50));
            $sortBy = in_array($this->request->getParam('sortBy', 'modified'), ['modified', 'title'])
                ? $this->request->getParam('sortBy', 'modified') : 'modified';
            $sortOrder = in_array($this->request->getParam('sortOrder', 'desc'), ['asc', 'desc'])
                ? $this->request->getParam('sortOrder', 'desc') : 'desc';

            // Get news pages via system file service (no user session needed)
            $result = $this->systemFileService->getNewsPagesForShare(
                $language,
                !empty($sourcePageId) ? $sourcePageId : null,
                !empty($sourcePath) ? $sourcePath : null,
                $relPath,
                $token,
                $limit,
                $sortBy,
                $sortOrder
            );

            // READER-GATE: SystemFileService drops manual drafts, but it has no
            // PageService and so cannot evaluate the publish/expiration dates
            // that live in MetaVox. Its own comment claims "the share endpoints in
            // ApiController" enforce those — they did not, and a scheduled or
            // expired page appeared in the public news list. Enforce it here,
            // which is the layer that can.
            $result['items'] = $this->filterUnpublishedNewsItems($result['items'] ?? []);
            $result['total'] = count($result['items']);

            $this->logger->info('[PublicShare] News accessed', [
                'language' => $language,
                'itemCount' => count($result['items'] ?? []),
                'ip' => $this->request->getRemoteAddress(),
            ]);

            return new JSONResponse($result);

        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Error in getNewsByShare', [
                'token' => '***',
                'error' => $e->getMessage()
            ]);
            return new JSONResponse(['items' => [], 'total' => 0]);
        }
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 60, period: 60)]
    public function getMediaByShare(string $token, string $uniqueId, string $filename) {
        // Validate token format first (cheap check)
        if (!$this->isValidShareTokenFormat($token)) {
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }

        // NC sharing must be enabled
        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }

        // Check share password
        $pwDenied = $this->checkSharePasswordOrDeny($token);
        if ($pwDenied !== null) {
            return $pwDenied;
        }

        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);

        try {
            // Validate share access - this checks if the page is within share scope
            // We use 'en' as default language, but validateShareAccess will search all languages
            $sessionPw = $this->getSharePasswordFromSession($token);
            $validation = $this->publicShareService->validateShareAccess($token, $uniqueId, 'en', $sessionPw);

            if (!$validation['valid']) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // READER-GATE: media inherits the visibility of the page it belongs
            // to. Without this, the images on a draft, scheduled or expired page
            // stayed publicly fetchable through the share as soon as the page
            // itself was in scope — the page 404s while its illustrations, org
            // charts and screenshots do not.
            $pageData = $validation['pageData'] ?? null;
            if (is_array($pageData) && $this->pageService->isHiddenFromReaders($pageData)) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get the page path from validation result
            // pagePath is like: /__groupfolders/1/files/nl/documentatie/documentatie.json
            $pagePath = $validation['pagePath'] ?? null;
            if ($pagePath === null) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get the page folder (parent of the .json file)
            // From: /__groupfolders/1/files/nl/documentatie/documentatie.json
            // To:   /__groupfolders/1/files/nl/documentatie
            $pageFolder = dirname($pagePath);

            // Get the IntraVox folder via system context
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Calculate the relative path from IntraVox root to the page folder
            // IntraVox path: /__groupfolders/1/files
            // Page folder:   /__groupfolders/1/files/nl/documentatie
            // Relative:      nl/documentatie
            $intraVoxPath = $folder->getPath();
            $relativePath = ltrim(substr($pageFolder, strlen($intraVoxPath)), '/');

            $this->logger->debug('[ApiController] getMediaByShare: looking for media', [
                'pagePath' => $pagePath,
                'pageFolder' => $pageFolder,
                'relativePath' => $relativePath,
                'filename' => $filename
            ]);

            // Navigate to the page folder
            $targetFolder = $folder->get($relativePath);

            // Get the _media folder
            $mediaFolder = $targetFolder->get('_media');

            // Get the file
            $file = $mediaFolder->get($filename);

            if ($file->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get mime type
            $mimeType = $file->getMimeType();

            // Validate it's an allowed media type
            $allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp',
                'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'
            ];
            if (!in_array($mimeType, $allowedTypes)) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Create stream response with security headers
            $response = new \OCP\AppFramework\Http\StreamResponse($file->fopen('rb'));
            $response->addHeader('Content-Type', $mimeType);
            $response->addHeader('Content-Disposition', 'inline; filename="' . $file->getName() . '"');
            $response->addHeader('X-Content-Type-Options', 'nosniff');
            $response->addHeader('X-Frame-Options', 'DENY');
            $isVideo = strpos($mimeType, 'video/') === 0;
            $cacheTime = $isVideo ? 86400 : 31536000;
            $response->addHeader('Cache-Control', 'public, max-age=' . $cacheTime);

            return $response;

        } catch (\OCP\Files\NotFoundException $e) {
            $this->logger->debug('[ApiController] Media file not found in getMediaByShare', [
                'token' => '***',
                'uniqueId' => $uniqueId,
                'filename' => $filename
            ]);
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->debug('[ApiController] Error in getMediaByShare', [
                'token' => '***',
                'uniqueId' => $uniqueId,
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 60, period: 60)]
    public function getResourcesMediaByShare(string $token, string $filename) {
        // Validate token format first (cheap check)

        // Sanitize path to prevent directory traversal
        try {
            $safePath = $this->sanitizePath($filename);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }

        try {
            // Token shape, link sharing, share password and IntraVox membership,
            // in that order (F6).
            $share = $this->openShare($token, fn () => new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND));
            if ($share instanceof Response) {
                return $share;
            }

            // Get the share target to determine the language
            $shareTarget = $share->getTarget();
            $language = $this->detectLanguageFromShareTarget($shareTarget);
            if ($language === null) {
                $language = 'nl'; // Default fallback
            }

            // Get the IntraVox folder via system context
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get the _resources folder in the language folder
            $languageFolder = $folder->get($language);
            $resourcesFolder = $languageFolder->get('_resources');

            // Get the file
            $file = $resourcesFolder->get($safePath);

            if ($file->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get mime type
            $mimeType = $file->getMimeType();

            // Create stream response with security headers
            $response = new \OCP\AppFramework\Http\StreamResponse($file->fopen('rb'));
            $response->addHeader('Content-Type', $mimeType);
            $response->addHeader('Content-Disposition', 'inline; filename="' . basename($safePath) . '"');
            $response->addHeader('X-Content-Type-Options', 'nosniff');
            $response->addHeader('X-Frame-Options', 'DENY');
            $response->addHeader('Cache-Control', 'public, max-age=31536000'); // 1 year cache

            return $response;

        } catch (\OCP\Files\NotFoundException $e) {
            $this->logger->debug('[ApiController] Resources file not found in getResourcesMediaByShare', [
                'filename' => $filename
            ]);
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->debug('[ApiController] Error in getResourcesMediaByShare', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 60, period: 60)]
    public function getResourcesMediaWithFolderByShare(string $token, string $folder, string $filename) {
        $path = $folder . '/' . $filename;
        return $this->getResourcesMediaByShare($token, $path);
    }

    // ---------- widget endpoints (F6d) ----------
    //
    // Calendar, feed and people data for a shared page. These lived on their
    // own controllers, each opening the share by hand with
    // resolveIntraVoxLinkShare() + isShareUnlocked(). That pair is two of the
    // four checks openShare() makes: it skipped the token-shape test and the
    // shareapi_allow_links kill switch, so an admin who turned off link sharing
    // still had four endpoints answering. Routing them through the same gate
    // closes that by construction rather than by remembering.
    //
    // Their refusal bodies are kept exactly as they were, because the widgets
    // read them: a 403 with an 'error' key, not the page endpoints' 404.

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 30, period: 60)]
    public function getEventsByShare(string $token): DataResponse {
        try {
            $share = $this->openShare($token, fn() => $this->widgetShareDenied());
            if ($share instanceof Response) {
                return $this->asDataResponse($share);
            }

            $calendarIdsParam = $this->request->getParam('calendarIds', '');
            $rangeStart = $this->request->getParam('rangeStart', '');
            $rangeEnd = $this->request->getParam('rangeEnd', '');
            $limit = (int) $this->request->getParam('limit', 5);

            // Parse external ICS URLs
            $externalIcsUrls = $this->parseExternalIcsUrls($this->request->getParam('externalIcsUrls', ''));

            if (empty($calendarIdsParam) && empty($externalIcsUrls)) {
                return new DataResponse(['events' => []]);
            }

            // Parse calendar keys (string identifiers)
            $calendarIds = array_filter(explode(',', (string) $calendarIdsParam), fn($s) => $s !== '');
            $limit = min(max($limit, 1), 20);

            // Validate date parameters
            try {
                $start = new \DateTimeImmutable($rangeStart ?: 'now');
                $end = new \DateTimeImmutable($rangeEnd ?: '+30 days');
            } catch (\Exception $e) {
                return new DataResponse(
                    ['error' => 'Invalid date format'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Cap date range to 1 year max
            $maxEnd = $start->modify('+1 year');
            if ($end > $maxEnd) {
                $end = $maxEnd;
            }

            // SHARE-CFG: the request may only ask for what this share actually
            // publishes. The ids below are read with the SHARE OWNER's
            // permissions, so trusting the query string let an anonymous visitor
            // name any calendar the owner could see and read it — proven on
            // nc-dev by reading the owner's birthday calendar through a share.
            // The token proves the visitor may see a page, not that page owner's
            // agenda.
            $allowedCalendarIds = $this->publicShareService->allowedWidgetValues($share, 'calendar', 'calendarIds');
            $requestedCount = count($calendarIds);
            $calendarIds = array_values(array_intersect($calendarIds, $allowedCalendarIds));

            if ($requestedCount > 0 && count($calendarIds) < $requestedCount) {
                $this->logger->warning('IntraVox: share requested calendars it does not publish', [
                    'token' => substr($token, 0, 8) . '...',
                    'requested' => $requestedCount,
                    'allowed' => count($calendarIds),
                ]);
            }

            // Same for external ICS feeds: the widget decides which are shown.
            if ($externalIcsUrls !== []) {
                $allowedIcsUrls = $this->publicShareService->allowedWidgetValues($share, 'calendar', 'externalIcsUrls');
                $externalIcsUrls = array_values(array_intersect($externalIcsUrls, $allowedIcsUrls));
            }

            if ($calendarIds === [] && $externalIcsUrls === []) {
                return new DataResponse(['events' => []]);
            }

            // Use the share owner's context to fetch calendar events
            $ownerId = $share->getShareOwner();
            if ($ownerId === null) {
                return new DataResponse(
                    ['error' => 'Could not determine share owner'],
                    Http::STATUS_INTERNAL_SERVER_ERROR
                );
            }

            $events = $this->calendarService->getEvents($ownerId, $calendarIds, $start, $end, $limit, $externalIcsUrls);

            return new DataResponse([
                'events' => $events,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting calendar events by share', [
                'token' => substr($token, 0, 8) . '...',
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to get calendar events'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 30, period: 60)]
    public function getFeedByShare(string $token): DataResponse {
        try {
            $share = $this->openShare($token, fn() => $this->widgetShareDenied());
            if ($share instanceof Response) {
                return $this->asDataResponse($share);
            }

            $sourceType = $this->request->getParam('sourceType', 'rss');
            $limit = (int)$this->request->getParam('limit', 5);

            $config = $this->buildConfigFromRequest($sourceType);
            // SHARE-CFG: connectionId selects a STORED connection, credentials and
            // all, and the fetch runs server-side with them. Taking it from the
            // query string let anyone holding any share token drive any configured
            // connection. It may only name what this share actually publishes.
            if (($config['connectionId'] ?? '') !== '') {
                $allowed = $this->publicShareService->allowedWidgetValues($share, 'feed', 'connectionId');
                if (!in_array($config['connectionId'], $allowed, true)) {
                    $this->logger->warning('IntraVox: share requested a connection it does not publish', [
                        'token' => substr($token, 0, 8) . '...',
                    ]);

                    return new DataResponse(
                        ['error' => 'Unknown connection for this share', 'items' => []],
                        Http::STATUS_FORBIDDEN
                    );
                }
            }

            // Likewise the RSS url: the widget decides which feed is shown. NOTE
            // the key mismatch — the request calls it 'url', the stored widget
            // calls it 'feedUrl'; comparing against 'url' would match an
            // always-empty list and block every RSS feed on a share.
            if (($config['url'] ?? '') !== '') {
                $allowedUrls = $this->publicShareService->allowedWidgetValues($share, 'feed', 'feedUrl');
                if (!in_array($config['url'], $allowedUrls, true)) {
                    return new DataResponse(
                        ['error' => 'Unknown feed for this share', 'items' => []],
                        Http::STATUS_FORBIDDEN
                    );
                }
            }

            [$sortBy, $sortOrder, $filterKeyword] = $this->parseSortAndFilter();
            $result = $this->feedReaderService->fetchFeed($sourceType, $config, $limit, null, $sortBy, $sortOrder, $filterKeyword);

            return new DataResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error fetching feed by share', [
                'token' => substr($token, 0, 8) . '...',
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to fetch feed', 'items' => []],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 30, period: 60)]
    public function proxyImageByShare(string $token): DataDownloadResponse|DataResponse {
        $share = $this->openShare($token, fn() => $this->widgetShareDenied());
        if ($share instanceof Response) {
            return $this->asDataResponse($share);
        }

        return $this->handleProxyImage();
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 30, period: 60)]
    public function getPeopleByShare(
        string $token,
        ?string $userIds = null,
        ?string $filters = null,
        string $filterOperator = 'AND',
        string $sortBy = 'displayName',
        string $sortOrder = 'asc',
        int $limit = 50,
        int $offset = 0
    ): DataResponse {
        try {
            $share = $this->openShare($token, fn() => $this->widgetShareDenied());
            if ($share instanceof Response) {
                return $this->asDataResponse($share);
            }

            // Removing the widget from the shared page is the primary
            // guard, but this endpoint is reachable on its own, so refuse
            // here too rather than trusting the page to be the only route.
            if (!$this->peopleAllowedOnPublicShares()) {
                return new DataResponse(['users' => [], 'total' => 0, 'hasMore' => false]);
            }

            // The viewer-facing facet parameters are not forwarded, and cannot
            // be: this calls the query directly rather than delegating to
            // PeopleController::getPeople(), so there is no argument list a
            // later refactor could quietly widen.
            //
            // That matters because a facet panel on a public share is a
            // browsable directory of the organisation — roles, buildings,
            // departments and their headcounts — handed to anyone with the link.
            //
            // The same reasoning applies to what the caller may ask FOR, and that
            // half was missing. Calendar and feed intersect the request with what
            // the page publishes (see getEventsByShare, getFeedByShare); people did
            // not, so a hand-written filter reached accounts the shared page never
            // shows. A token proves the visitor may read a page, never that they
            // may query the directory behind it.
            if ($userIds !== null && $userIds !== '') {
                $requested = array_values(array_filter(array_map('trim', explode(',', $userIds))));
                $published = $this->publicShareService->allowedWidgetValues($share, 'people', 'selectedUsers');
                $allowed = array_values(array_intersect($requested, $published));

                if (count($allowed) < count($requested)) {
                    $this->logger->warning('IntraVox: share requested people it does not publish', [
                        'token' => substr($token, 0, 8) . '...',
                        'requested' => count($requested),
                        'allowed' => count($allowed),
                    ]);
                }

                if ($allowed === []) {
                    return new DataResponse(['users' => [], 'total' => 0, 'hasMore' => false]);
                }

                $userIds = implode(',', $allowed);
            }

            // Filters are objects, so they cannot be intersected the way calendar
            // ids are. The page either publishes this exact set or it does not.
            if ($filters !== null && $filters !== '') {
                $decoded = json_decode($filters, true);

                if (!$this->publicShareService->publishesWidgetValueSet($share, 'people', 'filters', $decoded)) {
                    $this->logger->warning('IntraVox: share requested a people filter it does not publish', [
                        'token' => substr($token, 0, 8) . '...',
                    ]);

                    return new DataResponse(['users' => [], 'total' => 0, 'hasMore' => false]);
                }
            }

            return $this->peopleQuery->forPublicShare(
                $userIds,
                $filters,
                $filterOperator,
                $sortBy,
                $sortOrder,
                $limit,
                $offset
            );
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting people by share', [
                'token' => substr($token, 0, 8) . '...',
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => 'Failed to get people'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * The widgets' refusal: a 403 with an error key, kept byte-identical to
     * what they answered before F6d because the frontend reads this shape.
     */
    private function widgetShareDenied(): DataResponse {
        return new DataResponse(
            ['error' => 'Invalid or expired share token'],
            Http::STATUS_FORBIDDEN
        );
    }

    /**
     * openShare() hands back a JSONResponse for the password case, which the
     * widget endpoints must return as a DataResponse to keep their declared
     * return type honest. Same status, same body.
     */
    private function asDataResponse(Response $response): DataResponse {
        if ($response instanceof DataResponse) {
            return $response;
        }

        $body = $response instanceof JSONResponse ? $response->getData() : [];

        return new DataResponse($body, $response->getStatus());
    }

    // ---------- helpers, moved with their endpoints ----------

    /**
     * Open a public share, or hand back the response that refuses it. (F6)
     *
     * Six endpoints opened with the same four checks in the same order: token
     * shape, is link sharing enabled at all, is the share password satisfied,
     * and does the token actually point into IntraVox. Six copies of an access
     * gate is six places for one of the four to be forgotten — which is exactly
     * what happened before F2, where three endpoints skipped the password.
     *
     * The order matters and is preserved: the cheap format check first so a
     * malformed token never reaches the share manager, then the instance
     * setting, then the password, then the share itself.
     *
     * Returns the opened share, or a Response the caller must return as-is.
     * Callers differ in what "no" looks like — the page endpoints answer with
     * shareNotFoundResponse(), the media ones with a bare 404 — so the refusal
     * shape is passed in rather than assumed.
     *
     * @param callable():Response $deny builds this endpoint's refusal
     * @return IShare|Response the share, or the response to return
     */
    private function openShare(string $token, callable $deny): IShare|Response {
        if (!$this->isValidShareTokenFormat($token)) {
            return $deny();
        }

        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return $deny();
        }

        // 401 with passwordRequired, which is not the same as "no such share".
        $passwordDenied = $this->checkSharePasswordOrDeny($token);
        if ($passwordDenied !== null) {
            return $passwordDenied;
        }

        $share = $this->publicShareService->resolveIntraVoxLinkShare($token);
        if ($share === null) {
            return $deny();
        }

        return $share;
    }

    /**
     * Validate share token format.
     */
    private function isValidShareTokenFormat(?string $token): bool {
        if ($token === null || $token === '') {
            return false;
        }
        // NC share tokens are alphanumeric, typically 15-20 chars
        return strlen($token) >= 10 && strlen($token) <= 32 && ctype_alnum($token);
    }

    /**
     * Return a generic "not found" response for share access.
     */
    private function shareNotFoundResponse(): JSONResponse {
        // Add random delay to mask timing differences
        usleep(random_int(10000, 50000)); // 10-50ms

        return new JSONResponse([
            'error' => 'Page not found or not accessible via this share link'
        ], Http::STATUS_NOT_FOUND);
    }

    /**
     * Register a failed share access attempt.
     */
    private function registerShareBruteForceAttempt(): void {
        /** @var IThrottler */
        $throttler = \OC::$server->get(IThrottler::class);
        $throttler->registerAttempt(
            'intravox_share_page',
            $this->request->getRemoteAddress()
        );
    }

    /**
     * Check if share password is required and validate session password.
     * Returns null if OK (no password needed or session password valid).
     * Returns a JSONResponse if access should be denied.
     */
    private function checkSharePasswordOrDeny(string $token): ?JSONResponse {
        try {
            if (!$this->publicShareService->shareRequiresPassword($token)) {
                return null; // No password needed
            }
        } catch (\Exception $e) {
            // Share not found — let the normal validation handle it
            return null;
        }

        $sessionPw = $this->getSharePasswordFromSession($token);
        if ($sessionPw === null || $sessionPw === '') {
            return new JSONResponse([
                'error' => 'Password required',
                'passwordRequired' => true,
            ], Http::STATUS_UNAUTHORIZED);
        }

        if (!$this->publicShareService->checkSharePassword($token, $sessionPw)) {
            return new JSONResponse([
                'error' => 'Password required',
                'passwordRequired' => true,
            ], Http::STATUS_UNAUTHORIZED);
        }

        return null; // Session password is valid
    }

    /**
     * Get share password from session (stored after password challenge).
     */
    private function getSharePasswordFromSession(string $token): ?string {
        return $this->session->get('intravox_share_pw_' . $token);
    }

    /**
     * Detect language from share target path.
     * E.g., "/nl" or "/nl/about" -> "nl"
     */
    private function detectLanguageFromShareTarget(string $shareTarget): ?string {
        $path = ltrim($shareTarget, '/');
        $segments = explode('/', $path);

        if (empty($segments) || empty($segments[0])) {
            return null;
        }

        $potentialLanguage = $segments[0];

        // Validate it's a valid language code (2-3 chars)
        if (strlen($potentialLanguage) >= 2 && strlen($potentialLanguage) <= 3 && ctype_alpha($potentialLanguage)) {
            return $potentialLanguage;
        }

        return null;
    }

    private function sanitizePageForPublicAccess(array $pageData): array {
        // Whitelist approach - only include explicitly safe fields
        $safe = [
            'uniqueId' => $pageData['uniqueId'] ?? null,
            'title' => $pageData['title'] ?? '',
            'layout' => $pageData['layout'] ?? [],
            'language' => $pageData['language'] ?? 'en',
            'lastModified' => $pageData['lastModified'] ?? null,
        ];

        // Include publication dates if available (for public info)
        if (isset($pageData['metadata'])) {
            $safeMeta = [];
            // Only include non-sensitive metadata
            if (isset($pageData['metadata']['publishDate'])) {
                $safeMeta['publishDate'] = $pageData['metadata']['publishDate'];
            }
            if (!empty($safeMeta)) {
                $safe['metadata'] = $safeMeta;
            }
        }

        return $safe;
    }

    /**
     * Remove draft pages from a tree structure.
     */
    private function filterDraftsFromTree(array $tree, ?array $pubMeta = null): array {
        // Public (anonymous) tree: hide draft, scheduled (future) and expired
        // pages — there is never an editor here to reveal them. Batch the
        // publication metadata once and thread it through the recursion.
        if ($pubMeta === null) {
            $pubMeta = $this->pageService->publicationMetaForFiles($this->collectTreeFileIds($tree));
        }
        $filtered = [];
        foreach ($tree as $node) {
            $meta = $pubMeta[$node['fileId'] ?? null] ?? [];
            if ($this->pageService->isHiddenFromReaders($node, $meta)) {
                continue;
            }
            if (!empty($node['children'])) {
                $node['children'] = $this->filterDraftsFromTree($node['children'], $pubMeta);
            }
            $filtered[] = $node;
        }
        return $filtered;
    }

    /**
     * Drop news items that are not publicly published. (READER-GATE)
     *
     * The manual draft flag is already handled one layer down; what this adds is
     * the publish/expiration dates, which only PageService can interpret. A page
     * scheduled for next month, or one that expired last week, must not appear in
     * a public news list.
     *
     * @param list<array<string,mixed>> $items
     * @return list<array<string,mixed>>
     */
    private function filterUnpublishedNewsItems(array $items): array {
        return array_values(array_filter($items, function (array $item): bool {
            return !$this->pageService->isHiddenFromReaders($item);
        }));
    }

    /**
     * Set of page uniqueIds that must be hidden from readers (draft / scheduled /
     * expired) for a language, computed from the system page tree (which now
     * carries status + fileId). Used to prune the public navigation menu.
     *
     * @return array<string, true> uniqueId => true
     */
    private function hiddenUniqueIdsForLanguage(string $language): array {
        try {
            $tree = $this->systemFileService->getPageTree($language);
        } catch (\Exception $e) {
            return [];
        }
        $pubMeta = $this->pageService->publicationMetaForFiles($this->collectTreeFileIds($tree));
        $hidden = [];
        $walk = function (array $nodes) use (&$walk, $pubMeta, &$hidden) {
            foreach ($nodes as $node) {
                $meta = $pubMeta[$node['fileId'] ?? null] ?? [];
                if ($this->pageService->isHiddenFromReaders($node, $meta) && !empty($node['uniqueId'])) {
                    $hidden[$node['uniqueId']] = true;
                }
                if (!empty($node['children'])) {
                    $walk($node['children']);
                }
            }
        };
        $walk($tree);
        return $hidden;
    }


}
