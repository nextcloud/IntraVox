<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\PhotoStory;

use Psr\Log\LoggerInterface;

/**
 * EXIF reader with two strategies: lsolesen/pel composer library or exiftool binary.
 * Returns a normalized array; defensive — any failure yields an empty array.
 */
class ExifReader {
	private ?bool $pelAvailable = null;
	private ?bool $exiftoolAvailable = null;

	public function __construct(
		private LoggerInterface $logger,
	) {}

	/**
	 * Read EXIF data from a local file path.
	 *
	 * Strategy order (memory-safe first):
	 * 1. PHP native exif_read_data — streams from disk, ~few MB working set even on 20MB JPEGs.
	 * 2. exiftool binary — separate process, no PHP heap impact.
	 * 3. PEL — loads entire JPEG into PHP memory; only used when nothing else works.
	 *    Skipped when the file size would risk OOM (>40 MB).
	 *
	 * @return array{taken_at?: ?\DateTimeImmutable, gps_lat?: ?float, gps_lon?: ?float, camera?: ?string, mime?: string}
	 */
	public function read(?string $localPath): array {
		if ($localPath === null || $localPath === '' || !is_readable($localPath)) {
			return [];
		}

		// 1. Native PHP exif_read_data (cheap, JPEG/TIFF only)
		$result = $this->readWithNativeExif($localPath);
		if ($this->isUsable($result)) {
			return $result;
		}

		// 2. exiftool binary (no PHP-memory cost, supports HEIC/RAW/video)
		if ($this->isExiftoolAvailable()) {
			$exifResult = $this->readWithExiftool($localPath);
			if ($this->isUsable($exifResult)) {
				return $exifResult;
			}
		}

		// 3. PEL (last resort — high PHP-memory cost). Skip on files >40 MB.
		if ($this->isPelAvailable()) {
			$size = @filesize($localPath);
			if ($size !== false && $size < 40 * 1024 * 1024) {
				$pelResult = $this->readWithPel($localPath);
				if ($this->isUsable($pelResult)) {
					return $pelResult;
				}
			} else {
				$this->logger->debug('ExifReader: skipping PEL fallback for large file: ' . $localPath . ' (' . $size . ' bytes)');
			}
		}

		// Return whatever the native pass returned (may include a mime field even with no EXIF body)
		return $result;
	}

	/**
	 * Considered usable when we got at least one meaningful field, not just an empty array or a bare mime.
	 */
	private function isUsable(array $r): bool {
		if (empty($r)) {
			return false;
		}
		foreach (['taken_at', 'gps_lat', 'gps_lon', 'camera'] as $k) {
			if (!empty($r[$k])) {
				return true;
			}
		}
		return false;
	}

	private function isPelAvailable(): bool {
		if ($this->pelAvailable === null) {
			$this->pelAvailable = class_exists('\lsolesen\pel\PelJpeg')
				|| class_exists('\PEL\PelJpeg')
				|| class_exists('PelJpeg');
		}
		return $this->pelAvailable;
	}

	private function isExiftoolAvailable(): bool {
		if ($this->exiftoolAvailable === null) {
			$out = @shell_exec('command -v exiftool 2>/dev/null');
			$this->exiftoolAvailable = is_string($out) && trim($out) !== '';
		}
		return $this->exiftoolAvailable;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function readWithPel(string $localPath): array {
		try {
			// PEL only handles JPEG and TIFF reliably
			$mime = mime_content_type($localPath) ?: '';
			if (!in_array($mime, ['image/jpeg', 'image/tiff'], true)) {
				return [];
			}

			$pelJpegClass = class_exists('\lsolesen\pel\PelJpeg') ? '\lsolesen\pel\PelJpeg' : 'PelJpeg';
			$pelTagClass = class_exists('\lsolesen\pel\PelTag') ? '\lsolesen\pel\PelTag' : 'PelTag';

			if (!class_exists($pelJpegClass)) {
				return [];
			}

			$jpeg = new $pelJpegClass($localPath);
			$exif = $jpeg->getExif();
			if ($exif === null) {
				return ['mime' => $mime];
			}
			$tiff = $exif->getTiff();
			if ($tiff === null) {
				return ['mime' => $mime];
			}
			$ifd0 = $tiff->getIfd();
			if ($ifd0 === null) {
				return ['mime' => $mime];
			}

			$result = ['mime' => $mime];

			// Camera Make / Model
			$make = null;
			$model = null;
			$makeEntry = $ifd0->getEntry(constant($pelTagClass . '::MAKE'));
			if ($makeEntry !== null) {
				$make = trim((string)$makeEntry->getValue());
			}
			$modelEntry = $ifd0->getEntry(constant($pelTagClass . '::MODEL'));
			if ($modelEntry !== null) {
				$model = trim((string)$modelEntry->getValue());
			}
			if ($make !== null || $model !== null) {
				$result['camera'] = trim(($make ?? '') . ' ' . ($model ?? ''));
			}

			// Date taken from sub-IFD (Exif IFD)
			$exifIfd = $ifd0->getSubIfd(constant($pelTagClass . '::EXIF_IFD_POINTER'));
			if ($exifIfd !== null) {
				$dtoEntry = $exifIfd->getEntry(constant($pelTagClass . '::DATE_TIME_ORIGINAL'));
				if ($dtoEntry !== null) {
					$raw = $dtoEntry->getValue();
					$dt = $this->parseExifDate(is_string($raw) ? $raw : '');
					if ($dt !== null) {
						$result['taken_at'] = $dt;
					}
				}
			}

			// GPS from GPS IFD
			$gpsIfd = $ifd0->getSubIfd(constant($pelTagClass . '::GPS_INFO_IFD_POINTER'));
			if ($gpsIfd !== null) {
				[$lat, $lon] = $this->extractPelGps($gpsIfd, $pelTagClass);
				if ($lat !== null && $lon !== null) {
					$result['gps_lat'] = $lat;
					$result['gps_lon'] = $lon;
				}
			}

			return $result;
		} catch (\Throwable $e) {
			$this->logger->debug('ExifReader: PEL read failed', [
				'path' => basename($localPath),
				'error' => $e->getMessage(),
			]);
			return [];
		}
	}

	/**
	 * @return array{0: ?float, 1: ?float}
	 */
	private function extractPelGps(object $gpsIfd, string $pelTagClass): array {
		try {
			$latEntry = $gpsIfd->getEntry(constant($pelTagClass . '::GPS_LATITUDE'));
			$lonEntry = $gpsIfd->getEntry(constant($pelTagClass . '::GPS_LONGITUDE'));
			$latRefEntry = $gpsIfd->getEntry(constant($pelTagClass . '::GPS_LATITUDE_REF'));
			$lonRefEntry = $gpsIfd->getEntry(constant($pelTagClass . '::GPS_LONGITUDE_REF'));

			if ($latEntry === null || $lonEntry === null) {
				return [null, null];
			}

			$lat = $this->dmsToDecimal($latEntry->getValue());
			$lon = $this->dmsToDecimal($lonEntry->getValue());

			if ($latRefEntry !== null && strtoupper(trim((string)$latRefEntry->getValue())) === 'S') {
				$lat = -$lat;
			}
			if ($lonRefEntry !== null && strtoupper(trim((string)$lonRefEntry->getValue())) === 'W') {
				$lon = -$lon;
			}

			return [$lat, $lon];
		} catch (\Throwable $e) {
			return [null, null];
		}
	}

	/**
	 * Convert PEL DMS tuple to decimal degrees.
	 */
	private function dmsToDecimal(mixed $value): float {
		if (!is_array($value) || count($value) < 3) {
			return 0.0;
		}
		$deg = $this->pelRationalToFloat($value[0]);
		$min = $this->pelRationalToFloat($value[1]);
		$sec = $this->pelRationalToFloat($value[2]);
		return $deg + ($min / 60.0) + ($sec / 3600.0);
	}

	private function pelRationalToFloat(mixed $rational): float {
		if (is_array($rational) && count($rational) >= 2) {
			$num = (float)$rational[0];
			$den = (float)$rational[1];
			return $den != 0.0 ? $num / $den : 0.0;
		}
		// Native PHP exif_read_data returns rationals as strings like "41/1".
		if (is_string($rational) && strpos($rational, '/') !== false) {
			[$num, $den] = explode('/', $rational, 2);
			$num = (float)$num;
			$den = (float)$den;
			return $den != 0.0 ? $num / $den : 0.0;
		}
		if (is_numeric($rational)) {
			return (float)$rational;
		}
		return 0.0;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function readWithExiftool(string $localPath): array {
		try {
			$escaped = escapeshellarg($localPath);
			$cmd = "exiftool -j -n -DateTimeOriginal -CreateDate -GPSLatitude -GPSLongitude -Make -Model -MIMEType $escaped 2>/dev/null";
			$raw = @shell_exec($cmd);
			if (!is_string($raw) || trim($raw) === '') {
				return [];
			}
			$decoded = json_decode($raw, true);
			if (!is_array($decoded) || !isset($decoded[0]) || !is_array($decoded[0])) {
				return [];
			}
			$row = $decoded[0];
			$result = [];

			$mime = $row['MIMEType'] ?? null;
			if (is_string($mime) && $mime !== '') {
				$result['mime'] = $mime;
			} else {
				$result['mime'] = mime_content_type($localPath) ?: 'application/octet-stream';
			}

			$dtRaw = $row['DateTimeOriginal'] ?? $row['CreateDate'] ?? null;
			if (is_string($dtRaw)) {
				$dt = $this->parseExifDate($dtRaw);
				if ($dt !== null) {
					$result['taken_at'] = $dt;
				}
			}

			$lat = $row['GPSLatitude'] ?? null;
			$lon = $row['GPSLongitude'] ?? null;
			if (is_numeric($lat) && is_numeric($lon)) {
				$result['gps_lat'] = (float)$lat;
				$result['gps_lon'] = (float)$lon;
			}

			$make = isset($row['Make']) ? trim((string)$row['Make']) : '';
			$model = isset($row['Model']) ? trim((string)$row['Model']) : '';
			if ($make !== '' || $model !== '') {
				$result['camera'] = trim($make . ' ' . $model);
			}

			return $result;
		} catch (\Throwable $e) {
			$this->logger->debug('ExifReader: exiftool read failed', [
				'path' => basename($localPath),
				'error' => $e->getMessage(),
			]);
			return [];
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function readWithNativeExif(string $localPath): array {
		try {
			if (!function_exists('exif_read_data')) {
				return [];
			}
			$mime = mime_content_type($localPath) ?: '';
			// exif_read_data only handles JPEG/TIFF reliably
			if (!in_array($mime, ['image/jpeg', 'image/tiff'], true)) {
				return ['mime' => $mime];
			}
			$data = @exif_read_data($localPath, 'IFD0,EXIF,GPS', true);
			if (!is_array($data)) {
				return ['mime' => $mime];
			}
			$result = ['mime' => $mime];

			$dtRaw = $data['EXIF']['DateTimeOriginal']
				?? $data['IFD0']['DateTime']
				?? null;
			if (is_string($dtRaw)) {
				$dt = $this->parseExifDate($dtRaw);
				if ($dt !== null) {
					$result['taken_at'] = $dt;
				}
			}

			$make = isset($data['IFD0']['Make']) ? trim((string)$data['IFD0']['Make']) : '';
			$model = isset($data['IFD0']['Model']) ? trim((string)$data['IFD0']['Model']) : '';
			if ($make !== '' || $model !== '') {
				$result['camera'] = trim($make . ' ' . $model);
			}

			if (isset($data['GPS']['GPSLatitude'], $data['GPS']['GPSLongitude'])) {
				$lat = $this->dmsToDecimal($data['GPS']['GPSLatitude']);
				$lon = $this->dmsToDecimal($data['GPS']['GPSLongitude']);
				if (isset($data['GPS']['GPSLatitudeRef']) && strtoupper(trim((string)$data['GPS']['GPSLatitudeRef'])) === 'S') {
					$lat = -$lat;
				}
				if (isset($data['GPS']['GPSLongitudeRef']) && strtoupper(trim((string)$data['GPS']['GPSLongitudeRef'])) === 'W') {
					$lon = -$lon;
				}
				$result['gps_lat'] = $lat;
				$result['gps_lon'] = $lon;
			}

			return $result;
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * Parse EXIF-style date strings ("YYYY:MM:DD HH:MM:SS" or ISO8601).
	 */
	private function parseExifDate(string $raw): ?\DateTimeImmutable {
		$raw = trim($raw);
		if ($raw === '' || $raw === '0000:00:00 00:00:00') {
			return null;
		}
		// EXIF default
		$dt = \DateTimeImmutable::createFromFormat('Y:m:d H:i:s', $raw);
		if ($dt !== false) {
			return $dt;
		}
		// Strip subseconds/timezone-suffix variants
		try {
			return new \DateTimeImmutable($raw);
		} catch (\Throwable $e) {
			return null;
		}
	}
}
