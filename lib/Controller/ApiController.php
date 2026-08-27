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
    use ChecksAdminAccess;
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


















    // =========================================================================
    // TEMPLATE ENDPOINTS
    // =========================================================================





}
