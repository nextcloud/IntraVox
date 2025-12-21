<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;

/**
 * Service for handling MetaVox metadata during import operations
 * Provides version compatibility checking and field validation
 */
class MetaVoxImportService {
	private ?bool $metaVoxAvailable = null;
	private ?string $metaVoxVersion = null;
	private $fieldService = null;

	/** @var array|null Cached field definitions (request-level cache) */
	private ?array $cachedFields = null;

	/** @var array|null Cached fields indexed by name */
	private ?array $cachedFieldsByName = null;

	public function __construct(
		private IAppManager $appManager,
		private LoggerInterface $logger
	) {}

	/**
	 * Check if MetaVox is available on this system
	 *
	 * @return bool True if MetaVox is installed and enabled
	 */
	public function isAvailable(): bool {
		if ($this->metaVoxAvailable === null) {
			try {
				$this->metaVoxAvailable =
					$this->appManager->isInstalled('metavox') &&
					$this->appManager->isEnabledForUser('metavox');

				if ($this->metaVoxAvailable) {
					$this->metaVoxVersion = $this->appManager->getAppVersion('metavox');

					// Dynamically load MetaVox FieldService
					try {
						$this->fieldService = \OC::$server->get(\OCA\MetaVox\Service\FieldService::class);
					} catch (\Exception $e) {
						$this->logger->warning('MetaVox FieldService not available: ' . $e->getMessage());
						$this->metaVoxAvailable = false;
						return false;
					}

					$this->logger->info('MetaVox import service initialized', [
						'version' => $this->metaVoxVersion
					]);
				}
			} catch (\Exception $e) {
				$this->logger->warning('MetaVox not available for import: ' . $e->getMessage());
				$this->metaVoxAvailable = false;
			}
		}

		return $this->metaVoxAvailable;
	}

	/**
	 * Get current MetaVox version
	 *
	 * @return string|null Version string or null if not available
	 */
	public function getVersion(): ?string {
		$this->isAvailable(); // Ensure version is loaded
		return $this->metaVoxVersion;
	}

	/**
	 * Validate export compatibility with current MetaVox installation
	 *
	 * @param array $exportMetavoxData MetaVox section from export
	 * @return array {compatible: bool, warnings: string[], canImport: bool, reason: string}
	 */
	public function validateCompatibility(array $exportMetavoxData): array {
		if (!$this->isAvailable()) {
			return [
				'compatible' => false,
				'warnings' => ['MetaVox is not installed on this system. Metadata will be skipped.'],
				'canImport' => true,  // Can still import pages
				'reason' => 'not_installed'
			];
		}

		$exportVersion = $exportMetavoxData['version'] ?? '0.0.0';
		$currentVersion = $this->metaVoxVersion;

		$result = [
			'compatible' => true,
			'warnings' => [],
			'canImport' => true,
			'exportVersion' => $exportVersion,
			'currentVersion' => $currentVersion
		];

		// Version comparison
		$versionCompare = version_compare($exportVersion, $currentVersion);

		if ($versionCompare > 0) {
			// Export from newer MetaVox version
			$result['warnings'][] = sprintf(
				'Export created with newer MetaVox version (%s). Current version: %s. Some fields may not import correctly.',
				$exportVersion,
				$currentVersion
			);
		} elseif ($versionCompare < 0) {
			// Export from older MetaVox version
			$result['warnings'][] = sprintf(
				'Export created with older MetaVox version (%s). Current version: %s. All fields should import successfully.',
				$exportVersion,
				$currentVersion
			);
		}

		return $result;
	}

	/**
	 * Get all fields with request-level caching
	 * Prevents N+1 queries when importing multiple pages
	 *
	 * @return array All field definitions
	 */
	private function getCachedFields(): array {
		if ($this->cachedFields === null) {
			$this->cachedFields = $this->fieldService->getAllFields();
		}
		return $this->cachedFields;
	}

	/**
	 * Get fields indexed by name with request-level caching
	 *
	 * @return array Fields indexed by field_name
	 */
	private function getCachedFieldsByName(): array {
		if ($this->cachedFieldsByName === null) {
			$this->cachedFieldsByName = [];
			foreach ($this->getCachedFields() as $field) {
				$this->cachedFieldsByName[$field['field_name']] = $field;
			}
		}
		return $this->cachedFieldsByName;
	}

	/**
	 * Clear the field cache (call after creating/updating fields)
	 */
	public function clearFieldCache(): void {
		$this->cachedFields = null;
		$this->cachedFieldsByName = null;
	}

	/**
	 * Validate and prepare field definitions for import
	 *
	 * @param array $exportFieldDefinitions Field definitions from export
	 * @return array {fieldsToCreate: array, fieldsToUpdate: array, fieldsToSkip: array}
	 */
	public function validateFieldDefinitions(array $exportFieldDefinitions): array {
		if (!$this->isAvailable()) {
			return [
				'fieldsToCreate' => [],
				'fieldsToUpdate' => [],
				'fieldsToSkip' => $exportFieldDefinitions
			];
		}

		try {
			// Get current field definitions (cached)
			$currentFieldsByName = $this->getCachedFieldsByName();

			$result = [
				'fieldsToCreate' => [],
				'fieldsToUpdate' => [],
				'fieldsToSkip' => []
			];

			foreach ($exportFieldDefinitions as $exportField) {
				$fieldName = $exportField['field_name'];

				if (!isset($currentFieldsByName[$fieldName])) {
					// Field doesn't exist - mark for creation
					$result['fieldsToCreate'][] = $exportField;
				} else {
					// Field exists - check if update needed
					$currentField = $currentFieldsByName[$fieldName];

					// Check for differences
					$needsUpdate =
						$currentField['field_label'] !== $exportField['field_label'] ||
						$currentField['field_type'] !== $exportField['field_type'] ||
						json_encode($currentField['field_options'] ?? []) !== json_encode($exportField['field_options'] ?? []);

					if ($needsUpdate) {
						$result['fieldsToUpdate'][] = array_merge($exportField, [
							'id' => $currentField['id'],
							'changes' => $this->detectFieldChanges($currentField, $exportField)
						]);
					}
				}
			}

			return $result;

		} catch (\Exception $e) {
			$this->logger->error('Failed to validate field definitions: ' . $e->getMessage());
			return [
				'fieldsToCreate' => [],
				'fieldsToUpdate' => [],
				'fieldsToSkip' => $exportFieldDefinitions
			];
		}
	}

	/**
	 * Create missing field definitions
	 *
	 * @param array $fields Fields to create
	 * @param bool $autoCreate Whether to auto-create or prompt user
	 * @return array {created: array, failed: array}
	 */
	public function createFieldDefinitions(array $fields, bool $autoCreate = false): array {
		if (!$this->isAvailable() || empty($fields)) {
			return ['created' => [], 'failed' => $fields];
		}

		$result = ['created' => [], 'failed' => []];

		foreach ($fields as $field) {
			try {
				$fieldId = $this->fieldService->createField($field);
				$result['created'][] = array_merge($field, ['id' => $fieldId]);

				$this->logger->info('Created MetaVox field during import', [
					'field_name' => $field['field_name'],
					'field_id' => $fieldId
				]);
			} catch (\Exception $e) {
				$this->logger->error('Failed to create field: ' . $field['field_name'], [
					'error' => $e->getMessage()
				]);
				$result['failed'][] = $field;
			}
		}

		// Clear cache after creating new fields
		if (!empty($result['created'])) {
			$this->clearFieldCache();
		}

		return $result;
	}

	/**
	 * Import metadata values for a page
	 *
	 * @param int $fileId File ID of the imported page
	 * @param int $groupfolderId Groupfolder ID
	 * @param array $metadata Metadata from export
	 * @return array {imported: int, skipped: int, failed: int}
	 */
	public function importPageMetadata(int $fileId, int $groupfolderId, array $metadata): array {
		if (!$this->isAvailable() || empty($metadata)) {
			return ['imported' => 0, 'skipped' => count($metadata), 'failed' => 0];
		}

		$stats = ['imported' => 0, 'skipped' => 0, 'failed' => 0];

		try {
			// Get current field definitions (cached)
			$fieldsByName = $this->getCachedFieldsByName();

			foreach ($metadata as $metadataItem) {
				$fieldName = $metadataItem['field_name'];
				$value = $metadataItem['value'] ?? '';

				if (!isset($fieldsByName[$fieldName])) {
					$stats['skipped']++;
					$this->logger->debug('Skipping unknown field during import', [
						'field_name' => $fieldName
					]);
					continue;
				}

				try {
					$field = $fieldsByName[$fieldName];
					$success = $this->fieldService->saveGroupfolderFileFieldValue(
						$groupfolderId,
						$fileId,
						$field['id'],
						$value
					);

					if ($success) {
						$stats['imported']++;
					} else {
						$stats['failed']++;
					}
				} catch (\Exception $e) {
					$stats['failed']++;
					$this->logger->error('Failed to import metadata value', [
						'field_name' => $fieldName,
						'file_id' => $fileId,
						'error' => $e->getMessage()
					]);
				}
			}
		} catch (\Exception $e) {
			$this->logger->error('Failed to import page metadata', [
				'file_id' => $fileId,
				'error' => $e->getMessage()
			]);
			$stats['failed'] = count($metadata);
		}

		return $stats;
	}

	/**
	 * Import a single metadata field value (v1.3 format helper)
	 *
	 * @param int $fileId Target file ID (after rescan)
	 * @param int $groupfolderId Target groupfolder ID
	 * @param string $fieldName Field name (e.g., "file_gf_Status")
	 * @param mixed $value Field value
	 * @return bool True if import succeeded, false otherwise
	 */
	public function importFieldValue(int $fileId, int $groupfolderId, string $fieldName, $value): bool {
		if (!$this->isAvailable()) {
			return false;
		}

		try {
			// Get field definition (cached - prevents N+1 queries)
			$fieldsByName = $this->getCachedFieldsByName();
			$field = $fieldsByName[$fieldName] ?? null;

			if (!$field) {
				$this->logger->warning('Field not found during import', [
					'field_name' => $fieldName,
					'fileId' => $fileId
				]);
				return false;
			}

			// Save value using MetaVox FieldService
			$success = $this->fieldService->saveGroupfolderFileFieldValue(
				$groupfolderId,
				$fileId,
				$field['id'],
				$value
			);

			if ($success) {
				// Log field import without exposing potentially sensitive values
				$this->logger->debug('Imported field value', [
					'field_name' => $fieldName,
					'file_id' => $fileId,
					'value_length' => is_string($value) ? strlen($value) : (is_array($value) ? count($value) : 1)
				]);
			}

			return $success;

		} catch (\Exception $e) {
			$this->logger->error('Failed to import field value', [
				'field_name' => $fieldName,
				'file_id' => $fileId,
				'error' => $e->getMessage()
			]);
			return false;
		}
	}

	/**
	 * Detect changes between current and export field definitions
	 *
	 * @param array $current Current field definition
	 * @param array $export Export field definition
	 * @return array List of changed attributes
	 */
	private function detectFieldChanges(array $current, array $export): array {
		$changes = [];

		if ($current['field_label'] !== $export['field_label']) {
			$changes[] = 'label';
		}
		if ($current['field_type'] !== $export['field_type']) {
			$changes[] = 'type';
		}
		if (json_encode($current['field_options'] ?? []) !== json_encode($export['field_options'] ?? [])) {
			$changes[] = 'options';
		}

		return $changes;
	}
}
