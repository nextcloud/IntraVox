<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Constants;
use OCA\IntraVox\Exception\CrossLanguageMoveException;
use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageConflictException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Http\EtagBuilder;
use OCA\IntraVox\Service\EngagementSettingsService;
use OCA\IntraVox\Service\ImportService;
use OCA\IntraVox\Service\PublicationSettingsService;
use OCA\IntraVox\Service\PublicShareService;
use OCA\IntraVox\Service\TelemetryService;
use OCA\IntraVox\Service\VideoDomainPolicy;
use OCA\IntraVox\Service\Import\ConfluenceHtmlImportOrchestrator;
use OCA\IntraVox\Service\Import\ZipUploadValidator;
use OCA\IntraVox\Service\PageLockService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Share\ShareScope;
use OCA\IntraVox\Service\SetupService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\Share\IShare;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\Security\Bruteforce\IThrottler;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;

/**
 * API Controller for IntraVox pages
 *
 * All permission checks use Nextcloud's native filesystem permissions
 * which automatically respect GroupFolder ACL rules.
 */
class ApiController extends Controller {
    /**
     * Ceiling on GET /api/pages.
     *
     * Far above any real instance on purpose: the paid tiers stop at 1000 pages
     * per language, so this bounds a worst case without shaping ordinary use. It
     * is not a page size — there is no cursor here yet, because listPages() has no
     * ORDER BY and cursor paging over an unordered set silently skips and repeats
     * rows. A stable sort has to land first.
     */
    private const MAX_PAGES_IN_LISTING = 2000;

    /** Ceiling on the media listing; the picker shows far fewer than this. */
    private const MAX_MEDIA_IN_LISTING = 1000;

    /** Page size when a caller asks to paginate without saying how big. */
    private const DEFAULT_PAGE_SIZE = 100;

    /** Ceiling on an explicit page size, so paging cannot be used to bypass the cap. */
    private const MAX_PAGE_SIZE = 500;
    use ApiErrorTrait;
    use RequiresPagePermission;
    use \OCA\IntraVox\Controller\Shared\SharePathTrait;
    use HasConditionalResponse;

    private PageService $pageService;
    private SetupService $setupService;
    private EngagementSettingsService $engagementSettings;
    private PublicationSettingsService $publicationSettings;
    private PublicShareService $publicShareService;
    private TelemetryService $telemetryService;
    private ImportService $importService;
    private VideoDomainPolicy $videoDomains;
    private ZipUploadValidator $zipUploads;
    private ConfluenceHtmlImportOrchestrator $confluenceImport;
    private LoggerInterface $logger;
    private IConfig $config;
    private IGroupManager $groupManager;
    private IUserSession $userSession;
    private PageLockService $pageLockService;
    private IAppManager $appManager;

    public function __construct(
        string $appName,
        IRequest $request,
        PageService $pageService,
        SetupService $setupService,
        EngagementSettingsService $engagementSettings,
        PublicationSettingsService $publicationSettings,
        PublicShareService $publicShareService,
        TelemetryService $telemetryService,
        ImportService $importService,
        VideoDomainPolicy $videoDomains,
        ZipUploadValidator $zipUploads,
        ConfluenceHtmlImportOrchestrator $confluenceImport,
        LoggerInterface $logger,
        IConfig $config,
        IGroupManager $groupManager,
        IUserSession $userSession,
        PageLockService $pageLockService,
        IAppManager $appManager
    ) {
        parent::__construct($appName, $request);
        $this->pageService = $pageService;
        $this->setupService = $setupService;
        $this->engagementSettings = $engagementSettings;
        $this->publicationSettings = $publicationSettings;
        $this->publicShareService = $publicShareService;
        $this->telemetryService = $telemetryService;
        $this->importService = $importService;
        $this->videoDomains = $videoDomains;
        $this->zipUploads = $zipUploads;
        $this->confluenceImport = $confluenceImport;
        $this->logger = $logger;
        $this->config = $config;
        $this->groupManager = $groupManager;
        $this->userSession = $userSession;
        $this->pageLockService = $pageLockService;
        $this->appManager = $appManager;
    }

    /**
     * Get the logger instance for ApiErrorTrait.
     */
    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }

    /**
     * Get the page service for RequiresPagePermission.
     */
    protected function getPageService(): PageService {
        return $this->pageService;
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
     * Validate parentPageId parameter for import operations
     *
     * Security: Prevents IDOR attacks by validating:
     * 1. Parent page exists
     * 2. User has write permission on parent
     * 3. Parent is in the same language (groupfolder)
     *
     * @param string $parentPageId The parent page unique ID
     * @param string $targetLanguage The target language for import
     * @return array{valid: bool, error?: string, page?: array} Validation result
     */
    private function validateParentPageId(string $parentPageId, string $targetLanguage): array {
        try {
            $parentPage = $this->pageService->getPage($parentPageId);
        } catch (\Exception $e) {
            $this->logger->warning('[ApiController] Parent page validation failed: page not found', [
                'parentPageId' => $parentPageId,
                'error' => $e->getMessage()
            ]);
            return [
                'valid' => false,
                'error' => 'Parent page not found'
            ];
        }

        // Check write permission
        if (!($parentPage['permissions']['canWrite'] ?? false)) {
            $this->logger->warning('[ApiController] Parent page validation failed: no write permission', [
                'parentPageId' => $parentPageId,
                'targetLanguage' => $targetLanguage
            ]);
            return [
                'valid' => false,
                'error' => 'No write permission for parent page'
            ];
        }

        // Check same language (prevents cross-groupfolder imports)
        $parentLanguage = $parentPage['language'] ?? null;
        if ($parentLanguage !== $targetLanguage) {
            $this->logger->warning('[ApiController] Parent page validation failed: language mismatch', [
                'parentPageId' => $parentPageId,
                'parentLanguage' => $parentLanguage,
                'targetLanguage' => $targetLanguage
            ]);
            return [
                'valid' => false,
                'error' => 'Parent page must be in the same language as import target'
            ];
        }

        return [
            'valid' => true,
            'page' => $parentPage
        ];
    }

    /**
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listPages(?int $limit = null, ?string $cursor = null): DataResponse {
        try {
            $pages = $this->pageService->listPages();

            // PageService already includes permissions from Nextcloud's filesystem.
            // Filter to pages the user can read. Draft/scheduled/expired pages are
            // only visible to users with write permission. Batch the publication
            // metadata once to avoid an N+1 lookup.
            $pubMeta = $this->pageService->publicationMetaForFiles(array_column($pages, 'fileId'));
            $filteredPages = [];
            foreach ($pages as $page) {
                if (!($page['permissions']['canRead'] ?? false)) {
                    continue;
                }
                $meta = $pubMeta[$page['fileId'] ?? null] ?? [];
                if ($this->pageService->isHiddenFromReaders($page, $meta) && !($page['permissions']['canWrite'] ?? false)) {
                    continue;
                }
                $filteredPages[] = $page;
            }

            // The response was unbounded: every page in the language, however
            // many that is. The shape stays a bare array — the frontend treats
            // this as a complete index of page ids, so wrapping it in an envelope
            // would be a breaking change for a MINOR release — but it is now
            // bounded, and a truncated answer says so instead of pretending to be
            // complete. The ceiling is deliberately far above any real instance
            // (the paid tiers top out at 1000 pages per language), so this caps a
            // worst case rather than shaping ordinary use.
            // Explicit paging is opt-in, and only then does the shape change. A
            // caller that sends neither limit nor cursor keeps the bare array it
            // has always had (D4): App.vue reads this as a complete index of page
            // ids in nine places, so an unconditional envelope would be a breaking
            // change dressed up as a MINOR.
            if ($limit !== null || $cursor !== null) {
                return $this->pagedListing($filteredPages, $limit, $cursor);
            }

            $total = count($filteredPages);
            $response = new DataResponse(array_slice($filteredPages, 0, self::MAX_PAGES_IN_LISTING));
            $response->addHeader('X-IntraVox-Cap', (string)self::MAX_PAGES_IN_LISTING);
            $response->addHeader('X-IntraVox-Truncated', $total > self::MAX_PAGES_IN_LISTING ? 'true' : 'false');

            if ($total > self::MAX_PAGES_IN_LISTING) {
                $this->logger->warning('IntraVox: page listing truncated', [
                    'total' => $total,
                    'cap' => self::MAX_PAGES_IN_LISTING,
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            // If IntraVox folder doesn't exist, return empty array
            // This allows the WelcomeScreen to be shown instead of an error
            if (strpos($e->getMessage(), 'IntraVox folder not found') !== false) {
                return new DataResponse([]);
            }
            $this->logger->error('IntraVox: listing pages failed', ['error' => $e->getMessage()]);
            return new DataResponse(['error' => 'Could not list pages'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * One page of the listing, keyed on where the previous one stopped.
     *
     * Keyset, never OFFSET. An offset counts rows, so a page created or deleted
     * between two requests shifts everything after it: the caller silently skips
     * a row or sees one twice, and nothing in the response says so. A migration
     * walking a large intranet is exactly the caller that would hit it and the
     * least likely to notice. plan-multisite-uitvoering.md §4.15 settled this for
     * the multi-site work; using the same shape here keeps one answer in the API
     * rather than two.
     *
     * The cursor is the sort key of the last row served -- (title, uniqueId), the
     * order listPages() now guarantees -- and the next page is everything strictly
     * greater than it. Deleting the row a cursor points at is therefore harmless:
     * the comparison is on values, not on a position that has moved.
     *
     * Opaque on purpose. It is base64 of a JSON pair, which is trivially readable,
     * and that is fine: the point is not secrecy but that clients must not build
     * or arithmetic on it, because the key can change.
     *
     * @param list<array<string,mixed>> $pages already filtered and in sort order
     */
    private function pagedListing(array $pages, ?int $limit, ?string $cursor): DataResponse {
        $pageSize = max(1, min($limit ?? self::DEFAULT_PAGE_SIZE, self::MAX_PAGE_SIZE));

        if ($cursor !== null && $cursor !== '') {
            $after = $this->decodeCursor($cursor);
            if ($after === null) {
                return new DataResponse(['error' => 'Invalid cursor'], Http::STATUS_BAD_REQUEST);
            }

            $pages = array_values(array_filter(
                $pages,
                static fn (array $p): bool => [(string)($p['title'] ?? ''), (string)($p['uniqueId'] ?? '')] > $after
            ));
        }

        $slice = array_slice($pages, 0, $pageSize);
        $hasMore = count($pages) > $pageSize;
        $last = $slice === [] ? null : $slice[count($slice) - 1];

        return new DataResponse([
            'items' => $slice,
            'hasMore' => $hasMore,
            // Absent rather than null when the walk is done, so a client looping
            // 'while nextCursor' terminates without a special case.
            'nextCursor' => $hasMore && $last !== null
                ? $this->encodeCursor([(string)($last['title'] ?? ''), (string)($last['uniqueId'] ?? '')])
                : null,
        ]);
    }

    /** @param array{0:string,1:string} $key */
    private function encodeCursor(array $key): string {
        return rtrim(strtr(base64_encode(json_encode($key)), '+/', '-_'), '=');
    }

    /** @return array{0:string,1:string}|null null when the cursor is not one we made */
    private function decodeCursor(string $cursor): ?array {
        $raw = base64_decode(strtr($cursor, '-_', '+/'), true);
        if ($raw === false) {
            return null;
        }

        $key = json_decode($raw, true);
        if (!is_array($key) || count($key) !== 2 || !is_string($key[0]) || !is_string($key[1])) {
            return null;
        }

        return [$key[0], $key[1]];
    }
    /**
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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

            // Draft / scheduled (future) / expired pages are only accessible to
            // users with write permission.
            if ($this->pageService->isHiddenFromReaders($page) && !($page['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Page not found'],
                    Http::STATUS_NOT_FOUND
                );
            }

            // Expose the effective publication state so the editor UI can show a
            // "Scheduled"/"Expired" indicator (only meaningful for canWrite users),
            // plus whether a publish/expiration date is governing publication (so
            // the edit-mode toggle can explain that it defers to the date).
            $page['effectivePublishState'] = $this->pageService->effectivePublishState($page);
            $page['publicationDateActive'] = $this->pageService->hasPublicationDate($page);

            // Add breadcrumb to page response
            try {
                $page['breadcrumb'] = $this->pageService->getBreadcrumb($id);
            } catch (\Exception $e) {
                // Breadcrumb failed, but page is still valid
                $page['breadcrumb'] = [];
            }

            // Conditional response: derive an ETag from the page payload + the
            // user's group context. Including the group context keeps responses
            // safe to revalidate per-user — a user removed from a group will
            // get a fresh ETag and bypass cache automatically.
            $resourceKey = $page['uniqueId'] ?? $id;
            $version = md5(json_encode($page));
            $etag = EtagBuilder::build($resourceKey, $version, $this->getUserGroupContext());

            if ($notModified = $this->respondNotModifiedIfMatches($etag)) {
                return $notModified;
            }

            return $this->withCacheHeaders(new DataResponse($page), $etag);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        }
    }

    /**
     * Per-user discriminator for the conditional response trait. Returns null
     * when the user is unauthenticated so the ETag falls back to a purely
     * resource-keyed value.
     */
    private function getUserGroupContext(): ?string {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return null;
        }
        $groupIds = $this->groupManager->getUserGroupIds($user);
        return EtagBuilder::userContextFromGroups($groupIds);
    }

    /**
     */
    #[UserRateLimit(limit: 10, period: 60)]
    #[NoAdminRequired]
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
        } catch (ForbiddenException $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_FORBIDDEN
            );
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
     */
    #[NoAdminRequired]
    public function updatePage(string $id): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->requireWritablePage($id, 'cannot edit this page');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
            }

            // Check page lock — prevent saving if locked by another user
            $user = $this->userSession->getUser();
            if ($user !== null) {
                $lockByOther = $this->pageLockService->isLockedByOther($id, $user->getUID());
                if ($lockByOther !== null) {
                    return new DataResponse(
                        ['error' => 'Page is locked by ' . $lockByOther['displayName']],
                        Http::STATUS_CONFLICT
                    );
                }
            }

            $data = $this->request->getParams();
            $page = $this->pageService->updatePage($id, $data);
            return new DataResponse($page);
        } catch (ForbiddenException $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_FORBIDDEN
            );
        } catch (PageConflictException $e) {
            // 409, matching the page-lock conflict above: the editor's copy is
            // out of date and they can recover by reloading. A silent overwrite
            // is what this replaces.
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_CONFLICT
            );
        } catch (PageNotFoundException $e) {
            $this->logger->warning('[updatePage] PageNotFoundException: ' . $e->getMessage(), [
                'pageId' => $id,
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('[updatePage] InvalidArgumentException: ' . $e->getMessage(), [
                'pageId' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $this->logger->error('[updatePage] Exception: ' . $e->getMessage(), [
                'pageId' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     */
    #[UserRateLimit(limit: 10, period: 60)]
    #[NoAdminRequired]
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
        } catch (PageNotFoundException $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        } catch (\InvalidArgumentException $e) {
            // Client-preventable conditions (home page / configured homepage).
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
     * Reorder sibling pages within a parent (issue #69).
     *
     */
    #[UserRateLimit(limit: 20, period: 60)]
    #[NoAdminRequired]
    public function reorderPages(?string $parentId = null, array $orderedIds = []): DataResponse {
        try {
            if (!is_array($orderedIds) || count($orderedIds) === 0) {
                throw new \InvalidArgumentException('orderedIds must be a non-empty array');
            }
            foreach ($orderedIds as $childId) {
                if (!is_string($childId) || $childId === '') {
                    throw new \InvalidArgumentException('orderedIds must contain non-empty strings');
                }
            }

            // Write permission is checked on the PARENT (root or the parent page),
            // respecting GroupFolder ACLs so e.g. a department editor can reorder
            // within their own department but not elsewhere.
            $relPath = '';
            if ($parentId !== null && $parentId !== '') {
                $parentPage = $this->pageService->getPage($parentId);
                $relPath = $parentPage['path'] ?? '';
            }
            if (!($this->pageService->getFolderPermissions($relPath)['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot reorder pages here'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $this->pageService->reorderSiblings(($parentId !== '' ? $parentId : null), $orderedIds);
            return new DataResponse(['success' => true]);
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
     * Upload media (image or video) for a page
     * Unified endpoint that stores all media in a single 'media' folder
     */
    #[NoAdminRequired]
    public function uploadMedia(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->requireWritablePage($pageId, 'cannot upload media to this page');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
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
        } catch (PageNotFoundException $e) {
            $this->logger->warning('[uploadMedia] PageNotFoundException: ' . $e->getMessage(), [
                'pageId' => $pageId,
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
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
     * Check if media file with given name already exists
     */
    #[NoAdminRequired]
    public function checkMediaDuplicate(string $pageId): DataResponse {
        try {
            $filename = $this->request->getParam('filename');
            $target = $this->request->getParam('target', 'page');

            if (!$filename) {
                return new DataResponse(
                    ['error' => 'Filename parameter required'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Ask about the name the upload will actually write. The client
            // sends the name as the user picked it, while
            // uploadMediaWithOriginalName() sanitizes before writing, so
            // checking the raw name answered a question nobody asked: two
            // different names that sanitize to the same one were reported as
            // "no duplicate" and then collided on write.
            $exists = $this->pageService->checkMediaExists(
                $pageId,
                $this->pageService->sanitizeFilename($filename),
                $target
            );

            return new DataResponse(['exists' => $exists]);
        } catch (\InvalidArgumentException $e) {
            // A rejected extension is the caller's input, not a server fault —
            // the same 400 the upload itself answers with.
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
     * Upload media with original filename
     */
    #[NoAdminRequired]
    public function uploadMediaWithName(string $pageId): DataResponse {
        try {
            // Check write permission
            $existingPage = $this->requireWritablePage($pageId, 'cannot upload media to this page');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
            }

            // Get uploaded file
            $file = $this->request->getUploadedFile('media');
            if (!$file) {
                $file = $this->request->getUploadedFile('file');
            }

            if (!$file || empty($file['tmp_name'])) {
                return new DataResponse(
                    ['error' => 'No file uploaded'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Get parameters
            $target = $this->request->getParam('target', 'page');
            $overwrite = $this->request->getParam('overwrite', '0') === '1';

            // Upload file
            $result = $this->pageService->uploadMediaWithOriginalName($pageId, $file, $target, $overwrite);

            return new DataResponse($result, Http::STATUS_CREATED);

        } catch (PageNotFoundException $e) {
            // Was an unlogged 500 with a bare "Page not found" body, which is
            // why issue #92 came in with no Nextcloud log entries at all.
            $this->logger->warning('[uploadMediaWithName] PageNotFoundException: ' . $e->getMessage(), [
                'pageId' => $pageId,
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            // Check if it's a "File already exists" error
            if ($e->getMessage() === 'File already exists') {
                return new DataResponse(
                    ['error' => $e->getMessage()],
                    Http::STATUS_CONFLICT
                );
            }

            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get list of media files for a page or resources folder
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listMedia(string $pageId): DataResponse {
        try {
            $folder = $this->request->getParam('folder', 'page');
            $path = $this->request->getParam('path', ''); // NEW: for subfolder navigation

            // Security: Sanitize path for resources folder
            if ($folder === 'resources' && !empty($path)) {
                try {
                    $path = $this->sanitizePath($path);
                } catch (\InvalidArgumentException $e) {
                    return new DataResponse(
                        ['error' => 'Invalid path: ' . $e->getMessage()],
                        Http::STATUS_BAD_REQUEST
                    );
                }
            }

            $mediaList = $this->pageService->getMediaList($pageId, $folder, $path);

            // Naturally bounded by one folder's contents, which is not the same as
            // bounded. The shared resources folder in particular grows with the
            // instance rather than with the page. Same contract as the page
            // listing: the shape does not change, and a truncated answer says so.
            $total = count($mediaList);
            $response = new DataResponse(['media' => array_slice($mediaList, 0, self::MAX_MEDIA_IN_LISTING)]);
            $response->addHeader('X-IntraVox-Cap', (string)self::MAX_MEDIA_IN_LISTING);
            $response->addHeader('X-IntraVox-Truncated', $total > self::MAX_MEDIA_IN_LISTING ? 'true' : 'false');

            return $response;
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get media file from resources folder with separate folder and filename
     * Handles URLs like: /api/resources/media/backgrounds/header.svg
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getResourcesMediaWithFolder(string $folder, string $filename) {
        $path = $folder . '/' . $filename;
        return $this->getResourcesMedia($path);
    }

    /**
     * Get media file from resources folder (globally readable)
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getResourcesMedia(string $filename) {
        try {
            // Security: Validate path (prevent directory traversal)
            try {
                $safePath = $this->sanitizePath($filename);
            } catch (\InvalidArgumentException $e) {
                return new DataResponse(
                    ['error' => 'Invalid path: ' . $e->getMessage()],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $file = $this->pageService->getResourcesMediaFile($safePath);

            // Set appropriate content type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($file->getContent());
            finfo_close($finfo);

            // Get just the filename for Content-Disposition (not the full path)
            $displayName = basename($safePath);

            $response = new StreamResponse($file->fopen('rb'));
            $response->addHeader('Content-Type', $mimeType);
            $response->addHeader('Content-Disposition', 'inline; filename="' . $displayName . '"');
            $response->addHeader('Cache-Control', 'public, max-age=31536000'); // 1 year cache

            return $response;
        } catch (NotFoundException $e) {
            return new DataResponse(
                ['error' => 'Media file not found'],
                Http::STATUS_NOT_FOUND
            );
        } catch (\Exception $e) {
            $this->logger->error('Error serving resources media: ' . $e->getMessage());
            return new DataResponse(
                ['error' => 'Internal server error'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get server upload limit
     * Returns the effective upload limit in bytes
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getPageVersions(string $pageId): DataResponse {
        $this->logger->info('[ApiController::getPageVersions] Called with pageId: ' . $pageId);

        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $this->logger->info('[ApiController::getPageVersions] Getting page...');
            $existingPage = $this->pageService->getPage($pageId);
            $this->logger->info('[ApiController::getPageVersions] Got page, checking permissions...');

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                $this->logger->warning('[ApiController::getPageVersions] Access denied for pageId: ' . $pageId);
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $this->logger->info('[ApiController::getPageVersions] Calling pageService->getPageVersions...');
            $versions = $this->pageService->getPageVersions($pageId);
            $this->logger->info('[ApiController::getPageVersions] Got ' . count($versions) . ' versions');
            return new DataResponse($versions);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController::getPageVersions] Error: ' . $e->getMessage());
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     */
    #[NoAdminRequired]
    public function restorePageVersion(string $pageId, string $timestamp): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->requireWritablePage($pageId, 'cannot restore this page');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
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
     */
    #[NoAdminRequired]
    public function updateVersionLabel(string $pageId, string $timestamp): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->requireWritablePage($pageId, '');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     */
    #[NoAdminRequired]
    public function updatePageMetadata(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->requireWritablePage($pageId, '');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getMetavoxStatus(): DataResponse {
        try {
            $appManager = $this->appManager;
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
     * Get MetaVox fields for the IntraVox groupfolder
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getMetavoxFields(): DataResponse {
        try {
            $appManager = $this->appManager;
            if (!$appManager->isInstalled('metavox') || !$appManager->isEnabledForUser('metavox')) {
                return new DataResponse(['fields' => [], 'error' => 'MetaVox not available']);
            }

            // Get the IntraVox groupfolder ID
            $setupService = \OC::$server->get(\OCA\IntraVox\Service\SetupService::class);
            $groupfolderId = $setupService->getGroupFolderId();

            if ($groupfolderId <= 0) {
                return new DataResponse(['fields' => [], 'error' => 'IntraVox groupfolder not found']);
            }

            // Get MetaVox FieldService
            $fieldService = \OC::$server->get(\OCA\MetaVox\Service\FieldService::class);

            // Get fields assigned to this groupfolder (with full field data)
            $allFields = $fieldService->getAssignedFieldsWithDataForGroupfolder($groupfolderId);

            // Format fields for the frontend
            $fields = array_map(function($field) {
                return [
                    'field_name' => $field['field_name'] ?? '',
                    'field_label' => $field['field_label'] ?? $field['field_name'] ?? '',
                    'field_type' => $field['field_type'] ?? 'text',
                    'options' => $field['field_options'] ?? [],
                ];
            }, $allFields);

            return new DataResponse([
                'fields' => array_values($fields),
                'groupfolderId' => $groupfolderId
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: MetaVox fields unavailable', ['error' => $e->getMessage()]);
            return new DataResponse(['fields' => [], 'error' => 'MetaVox fields are unavailable'], Http::STATUS_OK);
        }
    }

    /**
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     * Get news pages for the News widget
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getNews(): DataResponse {
        try {
            $sourcePath = $this->request->getParam('sourcePath', '');
            $sourcePageId = $this->request->getParam('sourcePageId', '');
            $filtersJson = $this->request->getParam('filters', '[]');
            $filterOperator = $this->request->getParam('filterOperator', 'AND');
            $limit = (int) $this->request->getParam('limit', 5);
            $sortBy = $this->request->getParam('sortBy', 'modified');
            $sortOrder = $this->request->getParam('sortOrder', 'desc');
            $filterPublished = $this->request->getParam('filterPublished', 'false') === 'true';

            // Parse filters JSON
            $filters = json_decode($filtersJson, true) ?? [];

            // Validate limit
            $limit = max(1, min($limit, 50));

            // Validate sortBy
            if (!in_array($sortBy, ['modified', 'title'])) {
                $sortBy = 'modified';
            }

            // Validate sortOrder
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            // Validate filterOperator
            if (!in_array($filterOperator, ['AND', 'OR'])) {
                $filterOperator = 'AND';
            }

            $result = $this->pageService->getNewsPages(
                $sourcePath,
                $filters,
                $filterOperator,
                $limit,
                $sortBy,
                $sortOrder,
                !empty($sourcePageId) ? $sourcePageId : null,
                $filterPublished
            );

            return new DataResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('News widget error: ' . $e->getMessage());
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     */
    // Every search reads and json_decodes every page file in the language --
    // searchPages() calls listPagesWithContent() and scores the lot, then keeps
    // the top 20. The RESULTS were capped; the WORK was not, and this was the one
    // anonymous-adjacent amplifier left: any logged-in user could drive a full
    // content scan as fast as they could send requests.
    //
    // A scan cap would be the wrong instrument. listPages() has no ORDER BY, so
    // stopping halfway means the best match is missed at random rather than
    // reported as partial -- worse than slow. Throttling bounds the abuse and
    // leaves the answer correct.
    //
    // Safe to add: nothing in src/ calls this endpoint, and Nextcloud's own
    // search bar reaches PageService directly through PageSearchProvider, which
    // has its own indexed path and limit.
    #[UserRateLimit(limit: 30, period: 60)]
    #[NoAdminRequired]
    #[NoCSRFRequired]
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

            // Filter results based on Nextcloud's permissions (already in the results).
            // Draft/scheduled/expired pages are only visible to users with write
            // permission. Batch publication metadata once (N+1 avoidance).
            $pubMeta = $this->pageService->publicationMetaForFiles(array_column($results, 'fileId'));
            $filteredResults = [];
            foreach ($results as $result) {
                if (!($result['permissions']['canRead'] ?? false)) {
                    continue;
                }
                $meta = $pubMeta[$result['fileId'] ?? null] ?? [];
                if ($this->pageService->isHiddenFromReaders($result, $meta) && !($result['permissions']['canWrite'] ?? false)) {
                    continue;
                }
                $filteredResults[] = $result;
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     * Used for the "View structure" modal to show all accessible pages.
     *
     * Optional `rootPageId` narrows the response to the subtree rooted at
     * the page with that uniqueId — useful for third-party apps (e.g. a
     * teamhub) that want only the pages below a specific anchor. See
     * GitHub issue #45.
     *
     * Set a root-level page as the homepage for the current language
     * (issue: configurable homepage).
     *
     */
    #[NoAdminRequired]
    public function setHomepage(?string $pageUniqueId = null): DataResponse {
        try {
            if (!is_string($pageUniqueId) || $pageUniqueId === '') {
                return new DataResponse(['error' => 'pageUniqueId is required'], Http::STATUS_BAD_REQUEST);
            }

            // Write permission on the language root (mirror NavigationController::save).
            $permissions = $this->pageService->getFolderPermissions('');
            if (!($permissions['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot set the homepage'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $this->pageService->setHomepage($pageUniqueId);
            return new DataResponse(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Link a page to another language version of itself.
     *
     * Both pages end up in one translation group. Symmetric: neither becomes
     * the "source", so removing one language later shrinks the group instead
     * of orphaning the other.
     *
     */
    #[NoAdminRequired]
    public function linkTranslation(string $pageId, ?string $targetUniqueId = null): DataResponse {
        try {
            if (!is_string($targetUniqueId) || $targetUniqueId === '') {
                return new DataResponse(
                    ['error' => 'targetUniqueId is required'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $group = $this->pageService->linkTranslation($pageId, $targetUniqueId);
            return new DataResponse([
                'success' => true,
                'translationGroup' => $group,
                'translations' => $this->pageService->getPage($pageId)['translations'] ?? [],
            ]);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Detach a page from its translation group.
     *
     * Acts on this page only — the other language versions stay linked to each
     * other. Nothing is inferred or re-linked afterwards.
     *
     */
    #[NoAdminRequired]
    public function unlinkTranslation(string $pageId): DataResponse {
        try {
            $this->pageService->unlinkTranslation($pageId);
            return new DataResponse(['success' => true, 'translations' => []]);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Pages in OTHER languages that this page could be linked to.
     *
     * Powers the editor's "add translation" picker. Excludes the page's own
     * language — a group holds one page per language — and pages already in a
     * group with something else, so linking cannot silently steal a page out of
     * an existing set.
     *
     */
    #[NoAdminRequired]
    public function getTranslationCandidates(string $pageId, ?string $language = null): DataResponse {
        try {
            $candidates = $this->pageService->getTranslationCandidates($pageId, $language);
            return new DataResponse(['candidates' => $candidates]);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create this page in another language, as a linked draft.
     *
     * The entry point editors actually reach for — "make this page in German" —
     * rather than creating a blank page elsewhere and linking it afterwards.
     *
     */
    #[NoAdminRequired]
    public function createTranslation(
        string $pageId,
        ?string $language = null,
        ?string $title = null
    ): DataResponse {
        try {
            if (!is_string($language) || $language === '') {
                return new DataResponse(['error' => 'language is required'], Http::STATUS_BAD_REQUEST);
            }

            $created = $this->pageService->createTranslation($pageId, $language, $title);
            return new DataResponse([
                'success' => true,
                'page' => $created,
                'translations' => $this->pageService->getPage($pageId)['translations'] ?? [],
            ], Http::STATUS_CREATED);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Languages this page could still be created in.
     *
     * Excludes the page's own language and any language that already holds a
     * version of it, so the "add translation" control only ever offers a
     * choice that will succeed.
     *
     */
    #[NoAdminRequired]
    public function getTranslatableLanguages(string $pageId): DataResponse {
        try {
            return new DataResponse([
                'languages' => $this->pageService->getTranslatableLanguages($pageId),
            ]);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Copy a page into a new draft (issue: copy page).
     *
     */
    #[NoAdminRequired]
    public function copyPage(?string $sourceId = null, ?string $targetParentId = null, ?string $title = null): DataResponse {
        try {
            if (!is_string($sourceId) || $sourceId === '') {
                return new DataResponse(['error' => 'sourceId is required'], Http::STATUS_BAD_REQUEST);
            }

            // Need read on the source page…
            $source = $this->pageService->getPage($sourceId);
            if (!($source['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot read the source page'],
                    Http::STATUS_FORBIDDEN
                );
            }

            // …and create permission on the destination parent (root = '').
            $parentRelPath = '';
            if (is_string($targetParentId) && $targetParentId !== '') {
                $parentRelPath = $this->pageService->getPage($targetParentId)['path'] ?? '';
            }
            if (!($this->pageService->getFolderPermissions($parentRelPath)['canCreate'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot create a page here'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $page = $this->pageService->copyPage($sourceId, $targetParentId, $title);
            return new DataResponse(['success' => true, 'page' => $page], Http::STATUS_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Move a page (with its subtree) under a different parent (issue #69).
     * Per-folder permission (canWrite on source + canCreate on target parent),
     * so a department editor can move within their own rights — unlike the
     * admin-only bulk endpoint.
     *
     */
    #[UserRateLimit(limit: 20, period: 60)]
    #[NoAdminRequired]
    public function movePage(?string $pageId = null, ?string $targetParentId = null): DataResponse {
        try {
            if (!is_string($pageId) || $pageId === '') {
                return new DataResponse(['error' => 'pageId is required'], Http::STATUS_BAD_REQUEST);
            }

            // Write permission on the page being moved.
            $source = $this->requireWritablePage($pageId, 'cannot move this page');
            if ($source instanceof DataResponse) {
                return $source;
            }

            // Create permission on the destination parent (root = '').
            $parentRelPath = '';
            if (is_string($targetParentId) && $targetParentId !== '') {
                $parentRelPath = $this->pageService->getPage($targetParentId)['path'] ?? '';
            }
            if (!($this->pageService->getFolderPermissions($parentRelPath)['canCreate'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot move a page here'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $this->pageService->movePage($pageId, is_string($targetParentId) ? $targetParentId : '');
            return new DataResponse(['success' => true]);
        } catch (CrossLanguageMoveException $e) {
            // A refusal the user can act on, not a server fault: 409 Conflict
            // carries the explanatory message straight to the toast.
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_CONFLICT);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getPageTree(?string $currentPageId = null, ?string $language = null, ?string $rootPageId = null): DataResponse {
        try {
            $tree = $this->pageService->getPageTree($currentPageId, $language, $rootPageId);

            // Filter tree to only include pages user can read
            // PageService already includes Nextcloud permissions in each page
            $filteredTree = $this->filterTreeByPermissions($tree);

            // Resolve which page is the homepage so the UI can badge it and
            // offer "set as homepage" only on root pages (configurable homepage).
            $homepageUniqueId = $this->pageService->resolveHomepageNodeUniqueId($language, $filteredTree);

            // Root-folder permissions so the tree UI can gate actions that target
            // the language root — a sibling copy of a top-level page lands there,
            // so the Copy button on root-level items needs root canCreate (#86).
            $rootPermissions = $this->pageService->getFolderPermissions('');

            return new DataResponse([
                'tree' => $filteredTree,
                'homepageUniqueId' => $homepageUniqueId,
                'rootPermissions' => $rootPermissions,
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Filter page tree to only include pages the user can read. Draft/scheduled/
     * expired pages are hidden unless the user has write permission.
     *
     * @param array      $tree
     * @param array|null $pubMeta Publication metadata keyed by fileId, batched
     *                            once at the top level and threaded through the
     *                            recursion to avoid N+1 lookups.
     */
    private function filterTreeByPermissions(array $tree, ?array $pubMeta = null): array {
        if ($pubMeta === null) {
            $pubMeta = $this->pageService->publicationMetaForFiles($this->collectTreeFileIds($tree));
        }
        $filtered = [];
        foreach ($tree as $item) {
            if (!($item['permissions']['canRead'] ?? false)) {
                continue;
            }
            $meta = $pubMeta[$item['fileId'] ?? null] ?? [];
            if ($this->pageService->isHiddenFromReaders($item, $meta) && !($item['permissions']['canWrite'] ?? false)) {
                continue;
            }
            if (!empty($item['children'])) {
                $item['children'] = $this->filterTreeByPermissions($item['children'], $pubMeta);
            }
            $filtered[] = $item;
        }
        return $filtered;
    }

    /**
     * Get current user's permissions for IntraVox
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getPermissions(?string $path = null): DataResponse {
        try {
            $checkPath = $path ?? '';
            // Use Nextcloud's native filesystem permissions
            $permissions = $this->pageService->getFolderPermissions($checkPath);

            $response = [
                'path' => $checkPath,
                'permissions' => $permissions
            ];

            return new DataResponse($response);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Health check endpoint for monitoring and orchestration.
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 60, period: 60)]
    public function health(): DataResponse {
        return new DataResponse([
            'status' => 'ok',
            'app' => Application::APP_ID,
            'version' => $this->appManager->getAppVersion(Application::APP_ID),
        ]);
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

            // Run _resources folder migration
            $this->logger->info('[ApiController] Running _resources migration');
            $migrationResult = $this->setupService->migrateResourcesFolders();
            $this->logger->info('[ApiController] Migration result: ' . ($migrationResult ? 'success' : 'failed'));

            return new DataResponse([
                'success' => true,
                'message' => 'Setup completed successfully',
                'result' => $result,
                'migration' => $migrationResult,
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     * Set video domain whitelist
     * Warning-based system: all domains allowed, but with category warnings
     * Admin only
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
            $category = $this->videoDomains->categorise($host);

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

    /**
     * Get engagement settings (reactions & comments)
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getEngagementSettings(): DataResponse {
        return new DataResponse($this->engagementSettings->getAll());
    }

    /**
     * Update engagement settings
     * Admin only
     */
    public function setEngagementSettings(): DataResponse {
        // Only admins can change engagement settings
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change engagement settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get settings from request body
        $body = file_get_contents('php://input');
        $settings = json_decode($body, true) ?? [];

        try {
            $updated = $this->engagementSettings->updateAll($settings);

            $this->logger->info('IntraVox Audit: Engagement settings updated by admin', $settings);

            return new DataResponse([
                'success' => true,
                'settings' => $updated,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to update engagement settings: ' . $e->getMessage());
            return new DataResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get publication settings (MetaVox field names for date filtering)
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getPublicationSettings(): DataResponse {
        return new DataResponse($this->publicationSettings->getAll());
    }

    /**
     * Update publication settings
     * Admin only
     */
    /**
     * Whether People widgets may render on public share links.
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getPublicSharePeopleSetting(): DataResponse {
        return new DataResponse([
            'allowPeopleOnPublicShares' => $this->peopleAllowedOnPublicShares(),
        ]);
    }

    /**
     * Allow or forbid People widgets on public share links.
     *
     * Off by default. A public share is normally created to hand someone
     * documents; if the page also carries a People widget, that act publishes
     * a staff directory to whoever holds the link. Turning this on is a
     * deliberate decision an administrator takes, not a side effect of
     * sharing a folder.
     */
    public function setPublicSharePeopleSetting(): DataResponse {
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change this setting',
            ], Http::STATUS_FORBIDDEN);
        }

        $body = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $allow = ($body['allowPeopleOnPublicShares'] ?? false) === true;

        $this->config->setAppValue('intravox', 'public_share_allow_people', $allow ? 'yes' : 'no');

        $this->logger->info('[ApiController] People-on-public-shares setting changed', [
            'allowed' => $allow,
        ]);

        return new DataResponse([
            'success' => true,
            'allowPeopleOnPublicShares' => $allow,
        ]);
    }

    public function setPublicationSettings(): DataResponse {
        // Only admins can change publication settings
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change publication settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get settings from request body
        $body = file_get_contents('php://input');
        $settings = json_decode($body, true) ?? [];

        try {
            $updated = $this->publicationSettings->updateAll($settings);

            $this->logger->info('[ApiController] Publication settings updated', $settings);

            return new DataResponse([
                'success' => true,
                'settings' => $updated,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to update publication settings: ' . $e->getMessage());
            return new DataResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Set telemetry settings (enable/disable anonymous usage statistics)
     * Admin only
     *
     * @return DataResponse
     */
    public function setTelemetrySettings(): DataResponse {
        // Only admins can change telemetry settings
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change telemetry settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get settings from request body
        $body = file_get_contents('php://input');
        $settings = json_decode($body, true) ?? [];

        try {
            $enabled = isset($settings['enabled']) ? (bool)$settings['enabled'] : false;
            $this->telemetryService->setEnabled($enabled);

            $this->logger->info('[ApiController] Telemetry settings updated', ['enabled' => $enabled]);

            return new DataResponse([
                'success' => true,
                'enabled' => $enabled,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to update telemetry settings: ' . $e->getMessage());
            return new DataResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Import from uploaded ZIP file
     * Admin only
     *
     * @return JSONResponse
     */
    public function importZip(): JSONResponse {
        // Security: Only admins can import
        if (!$this->isAdmin()) {
            return new JSONResponse(
                ['error' => 'Admin access required'],
                Http::STATUS_FORBIDDEN
            );
        }

        try {
            $file = $this->request->getUploadedFile('file');

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
                ];
                $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
                $errorMessage = $errorMessages[$errorCode] ?? 'Unknown upload error';
                return new JSONResponse(['error' => $errorMessage], Http::STATUS_BAD_REQUEST);
            }

            $this->zipUploads->assertIsZip($file);

            $importComments = $this->request->getParam('importComments', '1') === '1';
            $overwrite = $this->request->getParam('overwrite', '0') === '1';
            $autoCreateMetaVoxFields = $this->request->getParam('autoCreateMetaVoxFields', '0') === '1';

            $zipContent = file_get_contents($file['tmp_name']);
            $stats = $this->importService->importFromZip($zipContent, $importComments, $overwrite, null, $autoCreateMetaVoxFields);

            return new JSONResponse([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\OCA\IntraVox\Exception\InvalidImportException $e) {
            // Validation errors are safe to forward to the client verbatim —
            // they describe the upload, never internal paths or IDs. See #52.
            // The frontend maps `errorCode` to a translated string; `error` is
            // an English fallback for non-UI consumers (curl, scripts).
            $this->logger->info('IntraVox Import: ZIP rejected during validation', [
                'errorCode' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ]);
            return new JSONResponse(
                [
                    'error' => $e->getMessage(),
                    'errorCode' => $e->getErrorCode(),
                    'params' => $e->getParams(),
                ],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $errorId = uniqid('err_');
            $this->logger->error('IntraVox Import: ZIP import failed', [
                'errorId' => $errorId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return new JSONResponse(
                ['error' => 'Import failed. Please check the ZIP file format and try again.', 'errorId' => $errorId],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Import from Confluence HTML export ZIP file
     * Admin only
     *
     * @return JSONResponse
     */
    public function importConfluenceHtml(): JSONResponse {
        // Security: Only admins can import
        if (!$this->isAdmin()) {
            return new JSONResponse(
                ['error' => 'Admin access required'],
                Http::STATUS_FORBIDDEN
            );
        }

        $this->logger->info('Confluence HTML import endpoint called');

        try {
            $file = $this->request->getUploadedFile('file');
            $language = $this->request->getParam('language', 'nl');
            $parentPageId = $this->request->getParam('parentPageId', null);

            $this->logger->info('Import request received', [
                'file' => $file ? $file['name'] : 'no file',
                'language' => $language,
                'parentPageId' => $parentPageId,
            ]);

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $this->logger->warning('File upload error', ['error' => $file['error'] ?? 'no file']);
                return new JSONResponse(
                    ['error' => 'No file uploaded or upload error'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $this->zipUploads->assertIsZip($file);

            // Security: Validate parentPageId before import (IDOR prevention)
            if ($parentPageId) {
                $validation = $this->validateParentPageId($parentPageId, $language);
                if (!$validation['valid']) {
                    return new JSONResponse(
                        ['error' => $validation['error']],
                        Http::STATUS_BAD_REQUEST
                    );
                }
            }

            $this->logger->info('Starting Confluence HTML import', [
                'filename' => $file['name'],
                'size' => $file['size'],
                'language' => $language,
            ]);

            $result = $this->confluenceImport->importFromUploadedZip(
                $file['tmp_name'],
                $language,
                $parentPageId
            );

            return new JSONResponse([
                'success' => true,
                'stats' => $result['stats'],
                'pages' => $result['pages'],
                'message' => 'Confluence HTML export imported successfully',
            ]);
        } catch (\OCA\IntraVox\Exception\InvalidImportException $e) {
            $this->logger->info('Confluence HTML import: validation failed', [
                'errorCode' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ]);
            return new JSONResponse(
                [
                    'error' => $e->getMessage(),
                    'errorCode' => $e->getErrorCode(),
                    'params' => $e->getParams(),
                ],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $errorId = uniqid('err_');
            $this->logger->error('Confluence HTML import failed', [
                'errorId' => $errorId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return new JSONResponse(
                ['error' => 'Confluence import failed. Please check the export format and try again.', 'errorId' => $errorId],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }




    /**
     * Get share info for a page (NC Files share detection).
     *
     * Checks if an NC Files share link exists for the page or its parent folder.
     * Used by the ShareButton component to determine if the share button should be shown.
     *
     * @param string $uniqueId The page's unique ID
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getShareInfo(string $uniqueId): JSONResponse {
        try {
            // Get the page to verify it exists and get its language
            $page = $this->pageService->getPage($uniqueId);

            // Check read permission
            if (!($page['permissions']['canRead'] ?? false)) {
                return new JSONResponse([
                    'error' => 'Access denied'
                ], Http::STATUS_FORBIDDEN);
            }

            $language = $page['language'] ?? 'en';
            $user = $this->userSession->getUser();
            $userId = $user ? $user->getUID() : null;

            // Get share info from PublicShareService
            $shareInfo = $this->publicShareService->getShareInfoForPage($uniqueId, $language, $userId);

            return new JSONResponse($shareInfo);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to get share info', [
                'uniqueId' => $uniqueId,
                'error' => $e->getMessage()
            ]);
            return new JSONResponse([
                'hasShare' => false,
                'reason' => 'error',
                'error' => $e->getMessage()
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all active public share links on IntraVox content.
     * Admin-only endpoint for the sharing overview tab.
     */
    public function getActiveShares(): JSONResponse {
        if (!$this->isAdmin()) {
            return new JSONResponse(['error' => 'Admin access required'], Http::STATUS_FORBIDDEN);
        }

        try {
            $shares = $this->publicShareService->getActiveShares();
            return new JSONResponse($shares);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to get active shares', [
                'error' => $e->getMessage()
            ]);
            return new JSONResponse(['error' => 'Failed to retrieve shares'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // TEMPLATE ENDPOINTS
    // =========================================================================

    /**
     * List all available page templates
     *
     */
    #[NoAdminRequired]
    public function listTemplates(): DataResponse {
        try {
            $templates = $this->pageService->listTemplates();
            $canCreate = $this->pageService->canCreateTemplates();

            return new DataResponse([
                'templates' => $templates,
                'canCreate' => $canCreate,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to list templates: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a specific template by ID
     *
     */
    #[NoAdminRequired]
    public function getTemplate(string $id): DataResponse {
        try {
            $template = $this->pageService->getTemplate($id);

            if ($template === null) {
                return new DataResponse([
                    'error' => 'Template not found',
                ], Http::STATUS_NOT_FOUND);
            }

            return new DataResponse($template);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Save a page as a template
     *
     */
    #[NoAdminRequired]
    public function saveAsTemplate(): DataResponse {
        try {
            $pageUniqueId = $this->request->getParam('pageUniqueId');
            $templateTitle = $this->request->getParam('templateTitle');
            $templateDescription = $this->request->getParam('templateDescription');

            if (!$pageUniqueId || !$templateTitle) {
                return new DataResponse([
                    'error' => 'Missing required parameters: pageUniqueId, templateTitle',
                ], Http::STATUS_BAD_REQUEST);
            }

            // Check if user can create templates
            if (!$this->pageService->canCreateTemplates()) {
                return new DataResponse([
                    'error' => 'You do not have permission to create templates',
                ], Http::STATUS_FORBIDDEN);
            }

            $result = $this->pageService->saveAsTemplate($pageUniqueId, $templateTitle, $templateDescription);

            if (!$result['success']) {
                return new DataResponse([
                    'error' => $result['error'] ?? 'Failed to save template',
                ], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse($result, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save as template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a template
     *
     */
    #[NoAdminRequired]
    public function deleteTemplate(string $id): DataResponse {
        try {
            $result = $this->pageService->deleteTemplate($id);

            if (!$result['success']) {
                return new DataResponse([
                    'error' => $result['error'] ?? 'Failed to delete template',
                ], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new page from a template
     *
     */
    #[NoAdminRequired]
    public function createPageFromTemplate(): DataResponse {
        try {
            $templateId = $this->request->getParam('templateId');
            $pageTitle = $this->request->getParam('pageTitle');
            $parentPath = $this->request->getParam('parentPath');

            if (!$templateId || !$pageTitle) {
                return new DataResponse([
                    'error' => 'Missing required parameters: templateId, pageTitle',
                ], Http::STATUS_BAD_REQUEST);
            }

            $result = $this->pageService->createPageFromTemplate($templateId, $pageTitle, $parentPath);

            if (!$result['success']) {
                return new DataResponse([
                    'error' => $result['error'] ?? 'Failed to create page from template',
                ], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse($result, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create page from template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
