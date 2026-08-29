<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\PhotoStory;

use OCP\IL10N;

/**
 * The arithmetic behind a photo story: timelines, highlights, location
 * clusters and a suggested title.
 *
 * Extracted from PhotoStoryService (service split, fase 2), which was 3287
 * lines with no tests at all. This block was the obvious first cut: measured
 * against the source it touches NO instance state -- twelve methods, zero
 * properties between them apart from the translator -- so it is arithmetic on
 * arrays that happened to live inside a class holding a database connection,
 * a filesystem root and a MetaVox client.
 *
 * onThisDay() deliberately stayed behind: it calls listPhotosPaged(), the
 * service's own query engine, so it is orchestration rather than arithmetic.
 *
 * Being free of collaborators is what finally makes this testable without a
 * Nextcloud instance, which is the real reason to move it.
 */
class PhotoTimelineBuilder {
	private const HIGH_VALUE_SUBJECTS = [
		'Architecture', 'Landscape', 'Portrait', 'People',
		'Building', 'Nature', 'Animal', 'Cat', 'Dog', 'Food',
	];

	/**
	 * Translatable month names. Index 1..12. Strings stay in English here so the
	 * gettext extractor picks them up; runtime translation happens via $this->l10n.
	 */
	private const MONTH_KEYS = [
		1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
		5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
		9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
	];

	public function __construct(
		private IL10N $l10n,
	) {
	}
	private function localizedMonth(int $month): string {
		$key = self::MONTH_KEYS[$month] ?? '';
		// IL10N->t() handles the active user-locale lookup via NC's translation
		// chain. Strings without an active translation fall through to English,
		// which matches the App Store expectation for unstaffed locales.
		return $this->l10n->t($key);
	}
	public function computeTimeline(array $photos, string $sortOrder = 'asc', string $granularity = 'day', string $dateField = 'taken_at'): array {
		$resolve = ($dateField === 'mtime')
			? fn(array $p): int => (int)($p['mtime'] ?? 0)
			: fn(array $p): int => $this->resolveTimestamp($p);

		if ($sortOrder === 'desc') {
			usort($photos, fn($a, $b) => $resolve($b) <=> $resolve($a));
		} else {
			usort($photos, fn($a, $b) => $resolve($a) <=> $resolve($b));
		}

		$buckets = [];
		foreach ($photos as $photo) {
			$ts = $resolve($photo);
			$dt = (new \DateTimeImmutable('@' . $ts))->setTimezone(new \DateTimeZone(date_default_timezone_get()));

			// Bucket key + display label depend on the chosen granularity.
			// "day"   → 2026-05-08 / "Donderdag 8 mei 2026"   — natural for photos
			// "month" → 2026-05    / "Mei 2026"              — natural for docs (Q-reports, newsletters)
			// "year"  → 2026       / "2026"                  — broad archive view
			switch ($granularity) {
				case 'month':
					$dateKey = $dt->format('Y-m');
					$label = $this->localizedMonth((int)$dt->format('n')) . ' ' . $dt->format('Y');
					break;
				case 'year':
					$dateKey = $dt->format('Y');
					$label = $dateKey;
					break;
				case 'day':
				default:
					$dateKey = $dt->format('Y-m-d');
					$label = $this->formatDateLong($dt);
					break;
			}

			if (!isset($buckets[$dateKey])) {
				$buckets[$dateKey] = [
					'date' => $dateKey,
					'label' => $label,
					'location_summary' => null,
					'photos' => [],
					'_locations' => [],
				];
			}
			$buckets[$dateKey]['photos'][] = $photo;
			$locForBucket = !empty($photo['location_display'])
				? (string)$photo['location_display']
				: (!empty($photo['location']) ? (string)$photo['location'] : null);
			if ($locForBucket !== null) {
				$buckets[$dateKey]['_locations'][$locForBucket] = ($buckets[$dateKey]['_locations'][$locForBucket] ?? 0) + 1;
			}
		}

		$out = [];
		foreach ($buckets as $bucket) {
			$summary = null;
			if (!empty($bucket['_locations'])) {
				arsort($bucket['_locations']);
				$summary = (string)array_key_first($bucket['_locations']);
			}
			unset($bucket['_locations']);
			$bucket['location_summary'] = $summary;
			$out[] = $bucket;
		}

		return $out;
	}
	public function computeHighlights(array $photos, int $max): array {
		// Stage 1: burst dedup — files within 3s of same camera, keep largest by size
		$deduped = $this->dedupBursts($photos);

		// Stage 2: scoring
		$sizeStats = $this->computeSizeStats($deduped);
		$locationCounts = [];
		foreach ($deduped as $p) {
			if (!empty($p['location'])) {
				$loc = (string)$p['location'];
				$locationCounts[$loc] = ($locationCounts[$loc] ?? 0) + 1;
			}
		}

		$scored = [];
		foreach ($deduped as $p) {
			$score = 1.0;
			$people = is_array($p['people'] ?? null) ? $p['people'] : [];
			$subjects = is_array($p['subjects'] ?? null) ? $p['subjects'] : [];

			$score += 0.3 * count($people);

			foreach ($subjects as $s) {
				if (in_array($s, self::HIGH_VALUE_SUBJECTS, true)) {
					$score += 0.2;
				}
			}

			if (!empty($p['location'])) {
				$loc = (string)$p['location'];
				if (($locationCounts[$loc] ?? 0) === 1) {
					$score += 0.5;
				}
			}

			$size = (int)($p['size'] ?? 0);
			if ($sizeStats['stddev'] > 0) {
				$z = ($size - $sizeStats['mean']) / $sizeStats['stddev'];
				$score += 0.1 * $z;
			}

			$scored[] = ['photo' => $p, 'score' => $score];
		}

		// Sort by score desc
		usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

		// Stage 3: diversity pass — greedy with penalty for repeated (location, top_subject) clusters
		$selected = [];
		$clusterCount = [];
		foreach ($scored as $entry) {
			$photo = $entry['photo'];
			$cluster = $this->clusterKey($photo);
			$current = $clusterCount[$cluster] ?? 0;
			if ($current >= 2) {
				// Skip; too many already in this cluster
				continue;
			}
			$selected[] = $photo;
			$clusterCount[$cluster] = $current + 1;
			if (count($selected) >= $max) {
				break;
			}
		}

		// If we ran out, top-up by ignoring cluster cap
		if (count($selected) < $max) {
			$selectedIds = [];
			foreach ($selected as $s) {
				$selectedIds[(int)$s['file_id']] = true;
			}
			foreach ($scored as $entry) {
				if (count($selected) >= $max) {
					break;
				}
				$photo = $entry['photo'];
				$fid = (int)$photo['file_id'];
				if (isset($selectedIds[$fid])) {
					continue;
				}
				$selected[] = $photo;
				$selectedIds[$fid] = true;
			}
		}

		// Stage 4: temporal spread — try to ensure selection spans total_days / N
		$selected = $this->temporalSpread($selected, $scored, $max);

		// Final sort by taken_at
		usort($selected, function ($a, $b) {
			return $this->resolveTimestamp($a) <=> $this->resolveTimestamp($b);
		});

		return $selected;
	}
	/**
	 * Group photos by location label, or by rounded GPS coords if no label.
	 *
	 * @param array<int, array<string, mixed>> $photos
	 * @return array<int, array{location: string, count: int, gps: ?array{lat: float, lon: float}, photo_ids: array<int, int>}>
	 */
	public function getLocationClusters(array $photos): array {
		$clusters = [];
		foreach ($photos as $p) {
			$label = $p['location'] ?? null;
			$gps = $p['gps'] ?? null;

			if (is_string($label) && $label !== '') {
				$key = 'L:' . $label;
				$displayLabel = $label;
				$gpsForCluster = is_array($gps) ? $gps : null;
			} elseif (is_array($gps) && isset($gps['lat'], $gps['lon'])) {
				// Round to ~1km grid (3 decimals ~ 100m, 2 decimals ~ 1km)
				$latR = round((float)$gps['lat'], 2);
				$lonR = round((float)$gps['lon'], 2);
				$key = sprintf('G:%.2f,%.2f', $latR, $lonR);
				$displayLabel = sprintf('%.4f, %.4f', $latR, $lonR);
				$gpsForCluster = ['lat' => $latR, 'lon' => $lonR];
			} else {
				continue;
			}

			if (!isset($clusters[$key])) {
				$clusters[$key] = [
					'location' => $displayLabel,
					'count' => 0,
					'gps' => $gpsForCluster,
					'photo_ids' => [],
				];
			}
			$clusters[$key]['count']++;
			$clusters[$key]['photo_ids'][] = (int)$p['file_id'];

			// If we get a GPS later for a label-only cluster, fill it in
			if ($clusters[$key]['gps'] === null && is_array($gps) && isset($gps['lat'], $gps['lon'])) {
				$clusters[$key]['gps'] = ['lat' => (float)$gps['lat'], 'lon' => (float)$gps['lon']];
			}
		}

		return array_values($clusters);
	}
	/**
	 * Heuristic title generation: location+month, country+year, or "Best of <year>".
	 *
	 * @param array<int, array<string, mixed>> $photos
	 */
	public function suggestTitle(array $photos, string $folderName = ''): string {
		if (empty($photos)) {
			return $folderName !== '' ? $folderName : 'Photo Story';
		}

		$countries = [];
		$locations = [];
		$months = [];
		$years = [];
		$timestamps = [];

		foreach ($photos as $p) {
			if (!empty($p['country'])) {
				$countries[(string)$p['country']] = true;
			}
			if (!empty($p['location'])) {
				$locations[(string)$p['location']] = ($locations[(string)$p['location']] ?? 0) + 1;
			}
			$ts = $this->resolveTimestamp($p);
			$timestamps[] = $ts;
			$dt = (new \DateTimeImmutable('@' . $ts))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
			$months[$dt->format('Y-m')] = true;
			$years[$dt->format('Y')] = true;
		}

		$countryCount = count($countries);
		$monthCount = count($months);
		$yearCount = count($years);
		$daySpan = 0;
		if (!empty($timestamps)) {
			$daySpan = (int)floor((max($timestamps) - min($timestamps)) / 86400);
		}

		// Single country + single month → "<location> — <month> <year>"
		if ($countryCount === 1 && $monthCount === 1 && !empty($locations)) {
			arsort($locations);
			$topLoc = (string)array_key_first($locations);
			$monthKey = (string)array_key_first($months);
			$dt = \DateTimeImmutable::createFromFormat('Y-m', $monthKey);
			if ($dt !== false) {
				$month = $this->localizedMonth((int)$dt->format('n'));
				return sprintf('%s — %s %s', $topLoc, $month, $dt->format('Y'));
			}
		}

		// Single country, multiple months → "<country> <year>"
		if ($countryCount === 1 && $monthCount > 1) {
			$country = (string)array_key_first($countries);
			if ($yearCount === 1) {
				return sprintf('%s %s', $country, (string)array_key_first($years));
			}
			return $country;
		}

		// Span > 30 days and multiple countries → "Best of <year>"
		if ($daySpan > 30 && $countryCount > 1 && $yearCount === 1) {
			return 'Best of ' . (string)array_key_first($years);
		}

		// Fallback to foldername (or generic)
		return $folderName !== '' ? $folderName : 'Photo Story';
	}
	/**
	 * @param array<int, array<string, mixed>> $photos
	 * @return array<int, array<string, mixed>>
	 */
	private function dedupBursts(array $photos): array {
		// Sort by (camera, taken_at)
		$sortable = $photos;
		usort($sortable, function ($a, $b) {
			$ca = (string)($a['camera'] ?? '');
			$cb = (string)($b['camera'] ?? '');
			$cmp = strcmp($ca, $cb);
			if ($cmp !== 0) {
				return $cmp;
			}
			return $this->resolveTimestamp($a) <=> $this->resolveTimestamp($b);
		});

		$kept = [];
		$burstWinner = null;
		$burstCamera = null;
		$burstAnchor = null;

		foreach ($sortable as $p) {
			$cam = (string)($p['camera'] ?? '');
			$ts = $this->resolveTimestamp($p);

			if ($burstWinner === null) {
				$burstWinner = $p;
				$burstCamera = $cam;
				$burstAnchor = $ts;
				continue;
			}

			if ($cam === $burstCamera && abs($ts - $burstAnchor) <= 3) {
				// Same burst — keep largest
				if ((int)($p['size'] ?? 0) > (int)($burstWinner['size'] ?? 0)) {
					$burstWinner = $p;
				}
				// Note: don't advance anchor on every entry — anchor stays so a 3s window holds
			} else {
				$kept[] = $burstWinner;
				$burstWinner = $p;
				$burstCamera = $cam;
				$burstAnchor = $ts;
			}
		}
		if ($burstWinner !== null) {
			$kept[] = $burstWinner;
		}
		return $kept;
	}
	/**
	 * @param array<int, array<string, mixed>> $photos
	 * @return array{mean: float, stddev: float}
	 */
	private function computeSizeStats(array $photos): array {
		$sizes = array_map(fn($p) => (float)($p['size'] ?? 0), $photos);
		$n = count($sizes);
		if ($n === 0) {
			return ['mean' => 0.0, 'stddev' => 0.0];
		}
		$mean = array_sum($sizes) / $n;
		$variance = 0.0;
		foreach ($sizes as $s) {
			$variance += ($s - $mean) ** 2;
		}
		$variance /= $n;
		return ['mean' => $mean, 'stddev' => sqrt($variance)];
	}
	/**
	 * @param array<string, mixed> $photo
	 */
	private function clusterKey(array $photo): string {
		$loc = (string)($photo['location'] ?? '');
		$subjects = is_array($photo['subjects'] ?? null) ? $photo['subjects'] : [];
		$topSubject = !empty($subjects) ? (string)$subjects[0] : '';
		if ($loc === '' && $topSubject === '') {
			return '__none__:' . (int)($photo['file_id'] ?? 0);
		}
		return $loc . '|' . $topSubject;
	}
	/**
	 * Ensure the selection spans at least total_days / N days; swap closely-grouped
	 * photos out for under-represented days when possible.
	 *
	 * @param array<int, array<string, mixed>> $selected
	 * @param array<int, array{photo: array<string, mixed>, score: float}> $allScored
	 * @return array<int, array<string, mixed>>
	 */
	private function temporalSpread(array $selected, array $allScored, int $max): array {
		if (count($selected) < 2) {
			return $selected;
		}

		// Compute day buckets in current selection
		$selectedByDay = [];
		$selectedIds = [];
		foreach ($selected as $p) {
			$day = $this->dayKey($p);
			$selectedByDay[$day][] = $p;
			$selectedIds[(int)$p['file_id']] = true;
		}

		// Compute day buckets across all candidates
		$allByDay = [];
		foreach ($allScored as $entry) {
			$day = $this->dayKey($entry['photo']);
			$allByDay[$day][] = $entry;
		}

		$allDays = array_keys($allByDay);
		if (count($allDays) <= 1) {
			return $selected;
		}

		$totalDays = count($allDays);
		$targetDays = (int)max(1, floor($totalDays / max($max, 1)) * $max);
		// If selection already covers enough distinct days, done
		if (count($selectedByDay) >= min($totalDays, $max)) {
			return $selected;
		}

		// Find over-represented and under-represented days
		sort($allDays);
		foreach ($allDays as $day) {
			if (isset($selectedByDay[$day])) {
				continue;
			}
			// Find the highest-scoring photo from this day not yet selected
			$candidate = null;
			foreach ($allByDay[$day] as $entry) {
				$fid = (int)$entry['photo']['file_id'];
				if (!isset($selectedIds[$fid])) {
					$candidate = $entry['photo'];
					break;
				}
			}
			if ($candidate === null) {
				continue;
			}

			// Find an over-represented day to swap from (3+ in same day, take lowest-scoring)
			$swapDay = null;
			$swapCount = 0;
			foreach ($selectedByDay as $d => $list) {
				if (count($list) > $swapCount && count($list) >= 3) {
					$swapDay = $d;
					$swapCount = count($list);
				}
			}
			if ($swapDay === null) {
				continue;
			}

			// Remove the last (lowest-priority since selection was score-sorted earlier) from $swapDay
			$victim = array_pop($selectedByDay[$swapDay]);
			if ($victim !== null) {
				unset($selectedIds[(int)$victim['file_id']]);
			}
			$selectedByDay[$day] = [$candidate];
			$selectedIds[(int)$candidate['file_id']] = true;
		}

		// Flatten
		$out = [];
		foreach ($selectedByDay as $list) {
			foreach ($list as $p) {
				$out[] = $p;
			}
		}
		return $out;
	}
	/**
	 * @param array<string, mixed> $photo
	 */
	private function dayKey(array $photo): string {
		$ts = $this->resolveTimestamp($photo);
		return (new \DateTimeImmutable('@' . $ts))
			->setTimezone(new \DateTimeZone(date_default_timezone_get()))
			->format('Y-m-d');
	}
	/**
	 * @param array<string, mixed> $photo
	 */
	public function resolveTimestamp(array $photo): int {
		if (!empty($photo['taken_at'])) {
			try {
				return (new \DateTimeImmutable((string)$photo['taken_at']))->getTimestamp();
			} catch (\Throwable $e) {
				// fall through
			}
		}
		return (int)($photo['mtime'] ?? 0);
	}
	private function formatDateLong(\DateTimeImmutable $dt): string {
		$day = (int)$dt->format('j');
		$month = $this->localizedMonth((int)$dt->format('n'));
		$year = $dt->format('Y');
		return sprintf('%d %s %s', $day, $month, $year);
	}
}
