<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Service for tracking and retrieving page analytics
 *
 * Integrates with Nextcloud's Activity system while maintaining
 * aggregated statistics in dedicated tables for dashboard queries.
 *
 * Privacy considerations:
 * - User IDs are hashed before storage
 * - Only daily aggregates are kept, not individual views
 * - Data is automatically cleaned up based on retention settings
 */
class AnalyticsService {
    private const APP_ID = 'intravox';
    private const DEFAULT_RETENTION_DAYS = 90;

    public function __construct(
        private IDBConnection $db,
        private IUserSession $userSession,
        private IConfig $config,
        private LoggerInterface $logger
    ) {}

    /**
     * Check if analytics tracking is enabled
     */
    public function isEnabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'analytics_enabled', 'true') === 'true';
    }

    /**
     * Track a page view
     *
     * This method is called when a user views a page. It:
     * 1. Increments the daily view count
     * 2. Tracks unique users (using hashed user ID)
     *
     * @param string $pageUniqueId The unique ID of the page
     * @return bool True if tracked successfully
     */
    public function trackPageView(string $pageUniqueId): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        $user = $this->userSession->getUser();
        if ($user === null) {
            return false;
        }

        $userId = $user->getUID();
        $userHash = $this->hashUserId($userId);
        $today = new \DateTime('today');
        $now = new \DateTime();

        try {
            // Start transaction
            $this->db->beginTransaction();

            // Track unique user view
            $isNewUserView = $this->trackUserView($pageUniqueId, $userHash, $today);

            // Update or insert page stats
            $this->updatePageStats($pageUniqueId, $today, $now, $isNewUserView);

            $this->db->commit();

            $this->logger->debug('IntraVox Analytics: Tracked view', [
                'pageId' => $pageUniqueId,
                'isNewUser' => $isNewUserView
            ]);

            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logger->error('IntraVox Analytics: Failed to track view', [
                'pageId' => $pageUniqueId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get statistics for a specific page
     *
     * @param string $pageUniqueId The unique ID of the page
     * @param int $days Number of days to include (default 30)
     * @return array Statistics array
     */
    public function getPageStats(string $pageUniqueId, int $days = 30): array {
        $startDate = new \DateTime("-{$days} days");

        $qb = $this->db->getQueryBuilder();
        $qb->select('stat_date', 'view_count', 'unique_users')
            ->from('intravox_page_stats')
            ->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($pageUniqueId)))
            ->andWhere($qb->expr()->gte('stat_date', $qb->createNamedParameter($startDate, 'date')))
            ->orderBy('stat_date', 'ASC');

        $result = $qb->executeQuery();
        $dailyStats = $result->fetchAll();
        $result->closeCursor();

        // Calculate totals
        $totalViews = 0;
        $totalUniqueUsers = 0;
        foreach ($dailyStats as $day) {
            $totalViews += (int)$day['view_count'];
            $totalUniqueUsers += (int)$day['unique_users'];
        }

        return [
            'pageId' => $pageUniqueId,
            'period' => $days,
            'totalViews' => $totalViews,
            'totalUniqueUsers' => $totalUniqueUsers,
            'dailyStats' => array_map(function ($day) {
                return [
                    'date' => $day['stat_date'],
                    'views' => (int)$day['view_count'],
                    'uniqueUsers' => (int)$day['unique_users']
                ];
            }, $dailyStats)
        ];
    }

    /**
     * Get top pages by view count
     *
     * @param int $limit Maximum number of pages to return
     * @param int $days Number of days to consider
     * @return array Array of pages with view counts
     */
    public function getTopPages(int $limit = 10, int $days = 30): array {
        $startDate = new \DateTime("-{$days} days");

        $qb = $this->db->getQueryBuilder();
        $qb->select('page_unique_id')
            ->selectAlias($qb->func()->sum('view_count'), 'total_views')
            ->selectAlias($qb->func()->sum('unique_users'), 'total_unique')
            ->from('intravox_page_stats')
            ->where($qb->expr()->gte('stat_date', $qb->createNamedParameter($startDate, 'date')))
            ->groupBy('page_unique_id')
            ->orderBy('total_views', 'DESC')
            ->setMaxResults($limit);

        $result = $qb->executeQuery();
        $pages = $result->fetchAll();
        $result->closeCursor();

        return array_map(function ($page) {
            return [
                'pageId' => $page['page_unique_id'],
                'totalViews' => (int)$page['total_views'],
                'totalUniqueUsers' => (int)$page['total_unique']
            ];
        }, $pages);
    }

    /**
     * Get dashboard statistics
     *
     * @param int $days Number of days to include
     * @return array Dashboard statistics
     */
    public function getDashboardStats(int $days = 30): array {
        $startDate = new \DateTime("-{$days} days");

        // Get daily totals
        $qb = $this->db->getQueryBuilder();
        $qb->select('stat_date')
            ->selectAlias($qb->func()->sum('view_count'), 'total_views')
            ->selectAlias($qb->func()->sum('unique_users'), 'total_unique')
            ->selectAlias($qb->func()->count('page_unique_id'), 'pages_viewed')
            ->from('intravox_page_stats')
            ->where($qb->expr()->gte('stat_date', $qb->createNamedParameter($startDate, 'date')))
            ->groupBy('stat_date')
            ->orderBy('stat_date', 'ASC');

        $result = $qb->executeQuery();
        $dailyStats = $result->fetchAll();
        $result->closeCursor();

        // Calculate totals
        $totalViews = 0;
        $totalPagesViewed = 0;
        foreach ($dailyStats as $day) {
            $totalViews += (int)$day['total_views'];
            $totalPagesViewed = max($totalPagesViewed, (int)$day['pages_viewed']);
        }

        return [
            'period' => $days,
            'totalViews' => $totalViews,
            'totalPagesViewed' => $totalPagesViewed,
            'dailyStats' => array_map(function ($day) {
                return [
                    'date' => $day['stat_date'],
                    'views' => (int)$day['total_views'],
                    'uniqueUsers' => (int)$day['total_unique'],
                    'pagesViewed' => (int)$day['pages_viewed']
                ];
            }, $dailyStats),
            'topPages' => $this->getTopPages(5, $days)
        ];
    }

    /**
     * Clean up old analytics data
     *
     * @param int|null $retentionDays Days to keep (null = use config)
     * @return int Number of rows deleted
     */
    public function cleanupOldData(?int $retentionDays = null): int {
        if ($retentionDays === null) {
            $retentionDays = (int)$this->config->getAppValue(
                self::APP_ID,
                'analytics_retention_days',
                (string)self::DEFAULT_RETENTION_DAYS
            );
        }

        $cutoffDate = new \DateTime("-{$retentionDays} days");
        $totalDeleted = 0;

        // Delete old page stats
        $qb = $this->db->getQueryBuilder();
        $qb->delete('intravox_page_stats')
            ->where($qb->expr()->lt('stat_date', $qb->createNamedParameter($cutoffDate, 'date')));
        $totalDeleted += $qb->executeStatement();

        // Delete old user views
        $qb = $this->db->getQueryBuilder();
        $qb->delete('intravox_uv')
            ->where($qb->expr()->lt('view_date', $qb->createNamedParameter($cutoffDate, 'date')));
        $totalDeleted += $qb->executeStatement();

        $this->logger->info('IntraVox Analytics: Cleaned up old data', [
            'retentionDays' => $retentionDays,
            'rowsDeleted' => $totalDeleted
        ]);

        return $totalDeleted;
    }

    /**
     * Delete all analytics data for a specific page
     * Called when a page is deleted
     *
     * @param string $pageUniqueId The unique ID of the page
     * @return int Number of rows deleted
     */
    public function deletePageData(string $pageUniqueId): int {
        $totalDeleted = 0;

        // Delete page stats
        $qb = $this->db->getQueryBuilder();
        $qb->delete('intravox_page_stats')
            ->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($pageUniqueId)));
        $totalDeleted += $qb->executeStatement();

        // Delete user views
        $qb = $this->db->getQueryBuilder();
        $qb->delete('intravox_uv')
            ->where($qb->expr()->eq('page_id', $qb->createNamedParameter($pageUniqueId)));
        $totalDeleted += $qb->executeStatement();

        return $totalDeleted;
    }

    /**
     * Hash a user ID for privacy
     *
     * @param string $userId The user ID to hash
     * @return string The hashed user ID
     */
    private function hashUserId(string $userId): string {
        $salt = $this->config->getSystemValue('secret', '');
        return hash('sha256', $userId . $salt);
    }

    /**
     * Track a user view and return whether it's a new unique view
     */
    private function trackUserView(string $pageUniqueId, string $userHash, \DateTime $date): bool {
        $qb = $this->db->getQueryBuilder();

        // Try to insert new user view
        try {
            $qb->insert('intravox_uv')
                ->values([
                    'page_id' => $qb->createNamedParameter($pageUniqueId),
                    'user_hash' => $qb->createNamedParameter($userHash),
                    'view_date' => $qb->createNamedParameter($date, 'date')
                ]);
            $qb->executeStatement();
            return true; // New unique user for this day
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            // Unique constraint violation = user already viewed today (works for MySQL and PostgreSQL)
            return false;
        } catch (\Exception $e) {
            // Fallback: check error message for duplicate key indicators (MySQL: 1062, PostgreSQL: 23505)
            $message = $e->getMessage();
            if (strpos($message, 'Duplicate entry') !== false
                || strpos($message, '1062') !== false
                || strpos($message, '23505') !== false
                || strpos($message, 'duplicate key') !== false
                || strpos($message, 'UNIQUE constraint') !== false) {
                return false;
            }
            // Re-throw unexpected exceptions
            throw $e;
        }
    }

    /**
     * Update page statistics
     */
    private function updatePageStats(
        string $pageUniqueId,
        \DateTime $date,
        \DateTime $now,
        bool $isNewUserView
    ): void {
        // Try to update existing row
        $qb = $this->db->getQueryBuilder();
        $qb->update('intravox_page_stats')
            ->set('view_count', $qb->createFunction('view_count + 1'))
            ->set('updated_at', $qb->createNamedParameter($now, 'datetime'));

        if ($isNewUserView) {
            $qb->set('unique_users', $qb->createFunction('unique_users + 1'));
        }

        $qb->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($pageUniqueId)))
            ->andWhere($qb->expr()->eq('stat_date', $qb->createNamedParameter($date, 'date')));

        $updated = $qb->executeStatement();

        // If no row was updated, insert new one
        if ($updated === 0) {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('intravox_page_stats')
                ->values([
                    'page_unique_id' => $qb->createNamedParameter($pageUniqueId),
                    'stat_date' => $qb->createNamedParameter($date, 'date'),
                    'view_count' => $qb->createNamedParameter(1),
                    'unique_users' => $qb->createNamedParameter($isNewUserView ? 1 : 0),
                    'created_at' => $qb->createNamedParameter($now, 'datetime'),
                    'updated_at' => $qb->createNamedParameter($now, 'datetime')
                ]);
            $qb->executeStatement();
        }
    }
}
