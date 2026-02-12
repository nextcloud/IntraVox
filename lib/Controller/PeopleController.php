<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PublicShareService;
use OCA\IntraVox\Service\UserService;
use OCP\Activity\IManager as IActivityManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

/**
 * Controller for the People widget API
 *
 * Provides endpoints for:
 * - Searching users for manual selection
 * - Getting user profiles
 * - Filtering users by groups/attributes
 * - Getting available groups and fields
 */
class PeopleController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private UserService $userService,
        private PublicShareService $publicShareService,
        private LoggerInterface $logger,
        private ?IActivityManager $activityManager = null,
        private ?IURLGenerator $urlGenerator = null
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Search users by name or email
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $query Search query
     * @param int $limit Maximum results (default 20)
     * @return DataResponse
     */
    public function searchUsers(string $query = '', int $limit = 20): DataResponse {
        try {
            if (strlen($query) < 2) {
                return new DataResponse([
                    'users' => [],
                    'message' => 'Query must be at least 2 characters'
                ]);
            }

            $users = $this->userService->searchUsers($query, min($limit, 50));

            return new DataResponse([
                'users' => $users
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error searching users', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => 'Failed to search users'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get user profiles by IDs
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param array $userIds Array of user IDs
     * @return DataResponse
     */
    public function getUsers(array $userIds = []): DataResponse {
        try {
            if (empty($userIds)) {
                return new DataResponse([
                    'users' => []
                ]);
            }

            // Limit to prevent abuse
            $userIds = array_slice($userIds, 0, 100);
            $users = $this->userService->getUserProfiles($userIds);

            return new DataResponse([
                'users' => $users
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting users', [
                'userIds' => $userIds,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => 'Failed to get users'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get available groups for filtering
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getGroups(): DataResponse {
        try {
            $groups = $this->userService->getGroups();

            return new DataResponse([
                'groups' => $groups
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting groups', [
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => 'Failed to get groups'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get available user profile fields for filtering/display configuration
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getUserFields(): DataResponse {
        try {
            $fields = $this->userService->getAvailableFields();

            return new DataResponse([
                'fields' => $fields
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting user fields', [
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => 'Failed to get user fields'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get people for widget display (supports both manual and filter modes)
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string|null $userIds Comma-separated user IDs for manual mode
     * @param string|null $filters JSON-encoded filters for filter mode
     * @param string $filterOperator 'AND' or 'OR'
     * @param string $sortBy Field to sort by
     * @param string $sortOrder 'asc' or 'desc'
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return DataResponse
     */
    public function getPeople(
        ?string $userIds = null,
        ?string $filters = null,
        string $filterOperator = 'AND',
        string $sortBy = 'displayName',
        string $sortOrder = 'asc',
        int $limit = 50,
        int $offset = 0
    ): DataResponse {
        try {
            $users = [];
            $total = 0;
            $hasMore = false;

            // Manual mode: get specific users
            if ($userIds !== null && $userIds !== '') {
                $ids = array_filter(explode(',', $userIds));
                $allUsers = $this->userService->getUserProfiles($ids);
                $total = count($allUsers);

                // Apply sorting for manual mode
                usort($allUsers, function ($a, $b) use ($sortBy, $sortOrder) {
                    $valueA = $a[$sortBy] ?? '';
                    $valueB = $b[$sortBy] ?? '';
                    $result = strcasecmp($valueA, $valueB);
                    return $sortOrder === 'desc' ? -$result : $result;
                });

                // Apply offset and limit
                $users = array_slice($allUsers, $offset, $limit);
                $hasMore = ($offset + count($users)) < $total;
            }
            // Filter mode: get users matching filters
            elseif ($filters !== null && $filters !== '') {
                $filterArray = json_decode($filters, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return new DataResponse(
                        ['error' => 'Invalid filters JSON'],
                        Http::STATUS_BAD_REQUEST
                    );
                }

                $result = $this->userService->getUsersByFilters(
                    $filterArray,
                    $filterOperator,
                    min($limit, 100),
                    $sortBy,
                    $sortOrder,
                    $offset,
                    true // returnTotal
                );

                $users = $result['users'];
                $total = $result['total'];
                $hasMore = ($offset + count($users)) < $total;
            }

            return new DataResponse([
                'users' => $users,
                'total' => $total,
                'hasMore' => $hasMore,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting people', [
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => 'Failed to get people'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get people for widget display via public share link
     *
     * @PublicPage
     * @NoCSRFRequired
     *
     * @param string $token Share token
     * @param string|null $userIds Comma-separated user IDs for manual mode
     * @param string|null $filters JSON-encoded filters for filter mode
     * @param string $filterOperator 'AND' or 'OR'
     * @param string $sortBy Field to sort by
     * @param string $sortOrder 'asc' or 'desc'
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return DataResponse
     */
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
            // Validate share token
            $shareInfo = $this->publicShareService->validateShareToken($token);
            if ($shareInfo === null) {
                return new DataResponse(
                    ['error' => 'Invalid or expired share token'],
                    Http::STATUS_FORBIDDEN
                );
            }

            // Use the same logic as getPeople
            return $this->getPeople($userIds, $filters, $filterOperator, $sortBy, $sortOrder, $limit, $offset);
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

}
