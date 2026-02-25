<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

/**
 * Service for managing per-user RSS feed tokens.
 *
 * Each user can have one feed token that grants unauthenticated
 * read-only access to their personalized RSS feed.
 */
class FeedTokenService {
    private const TABLE = 'intravox_feed_tokens';
    private const TOKEN_LENGTH = 64;

    public function __construct(
        private IDBConnection $db,
        private ISecureRandom $secureRandom,
        private LoggerInterface $logger
    ) {}

    /**
     * Get the existing feed token for a user.
     *
     * @return array|null {token, config, created_at, last_accessed} or null
     */
    public function getTokenForUser(string $userId): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('token', 'config', 'created_at', 'last_accessed')
            ->from(self::TABLE)
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        if (!$row) {
            return null;
        }

        return [
            'token' => $row['token'],
            'config' => json_decode($row['config'] ?? '{}', true) ?: [],
            'created_at' => $row['created_at'],
            'last_accessed' => $row['last_accessed'],
        ];
    }

    /**
     * Generate a new feed token for a user.
     *
     * If the user already has a token, it is replaced.
     *
     * @return string The new token
     */
    public function generateToken(string $userId, array $config = []): string {
        $token = $this->secureRandom->generate(
            self::TOKEN_LENGTH,
            ISecureRandom::CHAR_ALPHANUMERIC
        );
        $now = new \DateTime();
        $configJson = json_encode($config ?: ['scope' => 'all', 'limit' => 20]);

        // Delete existing token for this user
        $this->revokeToken($userId);

        // Insert new token
        $qb = $this->db->getQueryBuilder();
        $qb->insert(self::TABLE)
            ->values([
                'user_id' => $qb->createNamedParameter($userId),
                'token' => $qb->createNamedParameter($token),
                'config' => $qb->createNamedParameter($configJson),
                'created_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATETIME_MUTABLE),
            ]);
        $qb->executeStatement();

        $this->logger->info('[FeedTokenService] Token generated', [
            'userId' => $userId,
        ]);

        return $token;
    }

    /**
     * Regenerate a user's token, preserving their config.
     *
     * @return string The new token
     */
    public function regenerateToken(string $userId): string {
        $existing = $this->getTokenForUser($userId);
        $config = $existing['config'] ?? ['scope' => 'all', 'limit' => 20];
        return $this->generateToken($userId, $config);
    }

    /**
     * Revoke (delete) a user's feed token.
     */
    public function revokeToken(string $userId): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->delete(self::TABLE)
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $deleted = $qb->executeStatement();
        if ($deleted > 0) {
            $this->logger->info('[FeedTokenService] Token revoked', [
                'userId' => $userId,
            ]);
        }
        return $deleted > 0;
    }

    /**
     * Validate a feed token and return the associated user info.
     *
     * Also updates the last_accessed timestamp.
     *
     * @return array|null {user_id, config} or null if invalid
     */
    public function validateToken(string $token): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('user_id', 'config')
            ->from(self::TABLE)
            ->where($qb->expr()->eq('token', $qb->createNamedParameter($token)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        if (!$row) {
            return null;
        }

        // Update last_accessed
        $qbUpdate = $this->db->getQueryBuilder();
        $qbUpdate->update(self::TABLE)
            ->set('last_accessed', $qbUpdate->createNamedParameter(new \DateTime(), IQueryBuilder::PARAM_DATETIME_MUTABLE))
            ->where($qbUpdate->expr()->eq('token', $qbUpdate->createNamedParameter($token)));
        $qbUpdate->executeStatement();

        return [
            'user_id' => $row['user_id'],
            'config' => json_decode($row['config'] ?? '{}', true) ?: [],
        ];
    }

    /**
     * Update the feed configuration for an existing token.
     */
    public function updateConfig(string $userId, array $config): bool {
        $configJson = json_encode($config);

        $qb = $this->db->getQueryBuilder();
        $qb->update(self::TABLE)
            ->set('config', $qb->createNamedParameter($configJson))
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $qb->executeStatement() > 0;
    }
}
