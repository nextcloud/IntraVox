<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\PhotoStory;

use OCP\FilesMetadata\IFilesMetadataManager;
use Psr\Log\LoggerInterface;

/**
 * Read Nextcloud core file-metadata (EXIF, GPS, original-date-time, image-size)
 * via the public IFilesMetadataManager API (NC 28+).
 *
 * This is the PRIMARY source-of-truth for photo EXIF in IntraVox: NC's own
 * file-scanner indexes every uploaded photo into `oc_files_metadata` with
 * well-known keys ("photos-original_date_time", "photos-gps", "photos-ifd0",
 * "photos-size"). No app-specific fields to map, no external Python script
 * needed for new uploads.
 *
 * MetaVox custom fields (people, subjects, custom location-labels) are a
 * complementary secondary source — they ADD to what NC core provides.
 */
class NCFilesMetadataReader {
	public function __construct(
		private IFilesMetadataManager $manager,
		private LoggerInterface $logger,
	) {}

	/**
	 * Whether this reader can be used. Returns false if the manager service
	 * isn't available (very old NC, shouldn't happen on 28+).
	 */
	public function isAvailable(): bool {
		return true;  // Constructor injection means we have it if PHP class-loads it.
	}

	/**
	 * Batch-read NC core metadata for a list of file ids.
	 *
	 * @param int[] $fileIds
	 * @return array<int, array{
	 *     taken_at?: ?\DateTimeImmutable,
	 *     gps?: ?array{lat: float, lon: float, alt?: ?float},
	 *     camera?: ?string,
	 *     width?: ?int,
	 *     height?: ?int
	 * }>
	 */
	public function read(array $fileIds): array {
		if (empty($fileIds)) {
			return [];
		}
		// NC QueryBuilder rejects > 1000 expressions in an IN-list (Oracle portability check).
		// Chunk to 500 to stay well under the limit even after potential cross-table joins.
		$unique = array_values(array_unique(array_map('intval', $fileIds)));
		$results = [];
		foreach (array_chunk($unique, 500) as $chunk) {
			try {
				$batch = $this->manager->getMetadataForFiles($chunk);
				foreach ($batch as $fid => $meta) {
					$results[$fid] = $meta;
				}
			} catch (\Throwable $e) {
				$this->logger->warning('NCFilesMetadataReader: getMetadataForFiles chunk failed: ' . $e->getMessage());
				// Continue: skipping a chunk leaves those photos sparse, not the whole request 500.
			}
		}

		$out = [];
		foreach ($results as $fileId => $meta) {
			$row = [];

			// Original date/time — stored as unix-timestamp int, no parsing needed.
			try {
				$ts = $meta->getInt('photos-original_date_time');
				if ($ts > 0) {
					$row['taken_at'] = (new \DateTimeImmutable())->setTimestamp($ts);
				}
			} catch (\Throwable $e) {
				// Key not present → field stays unset
			}

			// GPS — stored as {latitude, longitude, altitude} array.
			try {
				$gps = $meta->getArray('photos-gps');
				if (!empty($gps) && isset($gps['latitude'], $gps['longitude'])) {
					$lat = (float)$gps['latitude'];
					$lon = (float)$gps['longitude'];
					if ($this->validGps($lat, $lon)) {
						$row['gps'] = ['lat' => $lat, 'lon' => $lon];
						if (isset($gps['altitude']) && is_numeric($gps['altitude'])) {
							$row['gps']['alt'] = (float)$gps['altitude'];
						}
					}
				}
			} catch (\Throwable $e) {
				// no gps
			}

			// Camera (Make + Model) — from IFD0 dict.
			try {
				$ifd0 = $meta->getArray('photos-ifd0');
				$make = isset($ifd0['Make']) ? trim((string)$ifd0['Make']) : '';
				$model = isset($ifd0['Model']) ? trim((string)$ifd0['Model']) : '';
				if ($model !== '') {
					$row['camera'] = $make !== '' && !str_starts_with(strtolower($model), strtolower($make))
						? trim($make . ' ' . $model)
						: $model;
				} elseif ($make !== '') {
					$row['camera'] = $make;
				}
			} catch (\Throwable $e) {
				// no camera
			}

			// Image size — width/height for aspect-ratio rendering.
			try {
				$size = $meta->getArray('photos-size');
				if (isset($size['width'])) {
					$row['width'] = (int)$size['width'];
				}
				if (isset($size['height'])) {
					$row['height'] = (int)$size['height'];
				}
			} catch (\Throwable $e) {
				// no size
			}

			if (!empty($row)) {
				$out[(int)$fileId] = $row;
			}
		}
		return $out;
	}

	/**
	 * Validate GPS coordinates. NC's extractor stores invalid values (Null Island,
	 * out-of-range) raw; we filter them here so they never reach the UI.
	 */
	private function validGps(float $lat, float $lon): bool {
		if (!is_finite($lat) || !is_finite($lon)) {
			return false;
		}
		if ($lat < -90.0 || $lat > 90.0 || $lon < -180.0 || $lon > 180.0) {
			return false;
		}
		// Null Island sentinel — corrupt EXIF.
		if (abs($lat) < 0.0001 && abs($lon) < 0.0001) {
			return false;
		}
		return true;
	}

	/**
	 * Single-file lookup. Used by the lightbox lazy EXIF endpoint to fetch
	 * camera/lens details for one photo at a time.
	 *
	 * @return array<string, mixed>
	 */
	public function readOne(int $fileId): array {
		$all = $this->read([$fileId]);
		return $all[$fileId] ?? [];
	}
}
