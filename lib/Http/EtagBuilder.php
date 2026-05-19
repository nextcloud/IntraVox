<?php
declare(strict_types=1);

namespace OCA\IntraVox\Http;

/**
 * Builds ETag values for cacheable API responses.
 *
 * The ETag is derived from a resource identifier plus a version token
 * (typically an mtime or content hash) and optionally a per-user context
 * hash. Including the per-user context ensures responses that vary by
 * permission can never leak between users via a shared cache.
 */
final class EtagBuilder {
    /**
     * Build a strong ETag from a resource identifier and a version token.
     *
     * @param string $resource Stable identifier for the resource (e.g. page uniqueId, "navigation:nl")
     * @param int|string $version Monotonic version token: mtime, sequence number, or content hash
     * @param string|null $userContext Optional per-user discriminator (e.g. groupHash). When
     *                                 set, the ETag varies per user-permission scope.
     */
    public static function build(string $resource, int|string $version, ?string $userContext = null): string {
        $material = $resource . ':' . (string)$version;
        if ($userContext !== null && $userContext !== '') {
            $material .= ':' . $userContext;
        }
        return '"' . md5($material) . '"';
    }

    /**
     * Hash a set of group memberships into a stable per-user context string.
     * Order-independent so the same group set always yields the same hash.
     *
     * @param array<int, string> $groupIds
     */
    public static function userContextFromGroups(array $groupIds): string {
        $copy = $groupIds;
        sort($copy);
        return substr(md5(implode('|', $copy)), 0, 12);
    }
}
