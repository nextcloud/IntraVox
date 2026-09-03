<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Publication;

use OCP\App\IAppManager;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * The MetaVox side of publication support: availability, the raw field data read
 * from the metavox_file_gf_meta table, field labels, per-field view permissions,
 * and the search subline. Extracted verbatim from PageService (cluster U, Phase
 * 3) so the scheduling logic in PublicationStateService and the search path in
 * PageService can share one owner of the MetaVox DB access.
 *
 * Owns the three request-scoped memos that used to live on PageService:
 * field labels, per-field view results, and the file -> groupfolder map that
 * getMetaVoxDataForFiles() populates and PageService::searchPages() reads back
 * (via groupfolderIdForFile()). Keeping all three here means a single instance
 * per request preserves the populate-then-read semantics byte-for-byte.
 */
class MetaVoxGateway {

    /** @var array<string, string>|null Request-lifetime cache of field_name => label. */
    private ?array $metaVoxFieldLabelsCache = null;

    /** @var array<string, bool> Request cache of "cacheKey => may view". */
    private array $metaVoxFieldViewCache = [];

    /** @var array<int, int> file id => owning groupfolder id, filled on fetch. */
    private array $metaVoxGroupfolderByFile = [];

    public function __construct(
        private IDBConnection $db,
        private IAppManager $appManager,
        private ?string $userId,
        private LoggerInterface $logger,
    ) {
    }

    public function isMetaVoxAvailable(): bool {
        try {
            return $this->appManager->isInstalled('metavox') && $this->appManager->isEnabledForUser('metavox');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get MetaVox metadata for multiple files.
     *
     * @param int[] $fileIds
     * @return array<int, array<string, string>> fileId => [fieldName => value]
     */
    public function getMetaVoxDataForFiles(array $fileIds): array {
        if (empty($fileIds) || !$this->isMetaVoxAvailable()) {
            return [];
        }

        try {
            // Query the metavox_file_gf_meta table directly
            $qb = $this->db->getQueryBuilder();
            $qb->select('file_id', 'field_name', 'field_value', 'groupfolder_id')
                ->from('metavox_file_gf_meta')
                ->where($qb->expr()->in('file_id', $qb->createNamedParameter($fileIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));

            $result = $qb->executeQuery();
            $rows = $result->fetchAll();
            $result->closeCursor();

            // Organize by file ID. The shape stays field_name => value (callers
            // like applyMetaVoxFilters rely on it); the owning groupfolder is
            // recorded separately so per-field view permissions can be scoped.
            $metaData = [];
            foreach ($rows as $row) {
                $fileId = (int)$row['file_id'];
                $fieldName = $row['field_name'];
                $fieldValue = $row['field_value'];

                if (!isset($metaData[$fileId])) {
                    $metaData[$fileId] = [];
                }
                $metaData[$fileId][$fieldName] = $fieldValue;
                $this->metaVoxGroupfolderByFile[$fileId] = (int)$row['groupfolder_id'];
            }

            return $metaData;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get MetaVox data', [
                'error' => $e->getMessage(),
                'fileIds' => $fileIds
            ]);
            return [];
        }
    }

    /**
     * The owning groupfolder id for a file, from the map getMetaVoxDataForFiles()
     * populated earlier in the request; null when that file was not fetched.
     */
    public function groupfolderIdForFile(int $fileId): ?int {
        return $this->metaVoxGroupfolderByFile[$fileId] ?? null;
    }

    /**
     * Map field_name => field_label for MetaVox fields, so search sublines show
     * the human label ("Stad") rather than the raw column name ("stad").
     * Cached for the request; falls back to an empty map when MetaVox is absent,
     * in which case callers use the raw field name.
     *
     * @return array<string, string>
     */
    public function getMetaVoxFieldLabels(): array {
        if ($this->metaVoxFieldLabelsCache !== null) {
            return $this->metaVoxFieldLabelsCache;
        }

        $this->metaVoxFieldLabelsCache = [];

        if (!$this->isMetaVoxAvailable()) {
            return $this->metaVoxFieldLabelsCache;
        }

        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('field_name', 'field_label')
                ->from('metavox_gf_fields');

            $result = $qb->executeQuery();
            while ($row = $result->fetch()) {
                $this->metaVoxFieldLabelsCache[$row['field_name']] = $row['field_label'];
            }
            $result->closeCursor();
        } catch (\Exception $e) {
            $this->logger->warning('Failed to load MetaVox field labels', [
                'error' => $e->getMessage(),
            ]);
        }

        return $this->metaVoxFieldLabelsCache;
    }

    /**
     * Search a page's MetaVox metadata for the query and build a subline.
     *
     * The subline format deliberately mirrors MetaVox's own search provider
     * (MetadataSearchProvider::formatMetadataSubline): "Label: value" parts
     * joined with " • ", the matching field first, capped at three fields — so
     * the same document reads identically in both providers' results.
     *
     * Fields the user may not view are skipped, so a restricted MetaVox field
     * cannot leak through an IntraVox search result.
     *
     * @param array<string, mixed> $meta   field_name => value for one file
     * @param string $query                lowercased search term
     * @param array<string, string> $labels field_name => field_label
     * @param int|null $groupfolderId      folder owning the file, for permission scoping
     * @return array{subline: string}|null null when nothing matched
     */
    public function searchMetaVoxValues(array $meta, string $query, array $labels, ?int $groupfolderId = null): ?array {
        if (empty($meta) || $query === '') {
            return null;
        }

        $matching = [];
        $other = [];
        $found = false;

        foreach ($meta as $fieldName => $value) {
            // Multiselect values are stored JSON-encoded; flatten to a string so
            // both the match test and the subline read naturally.
            if (is_string($value) && str_starts_with($value, '[')) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = implode(', ', array_filter($decoded, 'is_scalar'));
                }
            }
            if (!is_scalar($value)) {
                continue;
            }
            $value = (string)$value;
            if ($value === '') {
                continue;
            }
            if (!$this->canViewMetaVoxField($fieldName, $groupfolderId)) {
                continue;
            }

            $part = ($labels[$fieldName] ?? $fieldName) . ': ' . $value;
            if (mb_stripos($value, $query) !== false) {
                $matching[] = $part;
                $found = true;
            } else {
                $other[] = $part;
            }
        }

        if (!$found) {
            return null;
        }

        $parts = array_merge($matching, $other);
        return ['subline' => implode(' • ', array_slice($parts, 0, 3))];
    }

    /**
     * Whether the current user may view a MetaVox field. Delegates to MetaVox's
     * own PermissionService (resolved lazily — MetaVox is an optional app).
     *
     * On any failure we return false: hiding a field costs a subline entry,
     * showing one the user may not see would leak metadata.
     */
    public function canViewMetaVoxField(string $fieldName, ?int $groupfolderId = null): bool {
        $cacheKey = $fieldName . ':' . ($groupfolderId ?? 'null');
        if (isset($this->metaVoxFieldViewCache[$cacheKey])) {
            return $this->metaVoxFieldViewCache[$cacheKey];
        }

        $allowed = false;
        try {
            if ($this->userId !== '') {
                $permissionService = \OC::$server->get(\OCA\MetaVox\Service\PermissionService::class);
                $allowed = $permissionService->hasPermission(
                    $this->userId,
                    \OCA\MetaVox\Service\PermissionService::PERM_VIEW_METADATA,
                    $groupfolderId,
                    $fieldName
                );
            }
        } catch (\Throwable $e) {
            $allowed = false;
        }

        $this->metaVoxFieldViewCache[$cacheKey] = $allowed;
        return $allowed;
    }
}
