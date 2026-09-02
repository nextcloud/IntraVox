<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Filter;

/**
 * Computes facet values and counts over an already-materialised collection.
 *
 * Two rules govern everything in here, and both are load-bearing.
 *
 * 1. NARROWING — viewer refinements can only ever shrink the result set.
 *    The editor decides what a widget shows; a viewer decides how much of
 *    that they look at. Editor filters are therefore applied unconditionally
 *    and are never part of the "exclude" step below. Formally:
 *
 *        result(refine) is a subset of result([]) for every refine
 *
 * 2. DISJUNCTIVE FACETING — when counting facet F, exclude F's own *viewer*
 *    refinements, but nothing else. Without this, picking one value in a
 *    facet drops every sibling value to zero and multi-select within a facet
 *    becomes impossible ("building Noord OR Zuid" could never be expressed).
 *    This is the standard behaviour in Solr, Elasticsearch and Algolia, so
 *    users arrive pre-calibrated.
 *
 *    The rule is uniform: a facet with no active refinement automatically
 *    counts over the fully-filtered set, because it has nothing of its own
 *    to exclude. There is no special case.
 *
 * Deliberately free of Nextcloud dependencies so the rules can be unit-tested
 * directly. Callers hand in plain rows; how those rows were produced (a user
 * cohort snapshot, MetaVox rows for a file id set) is not this class's
 * concern.
 */
final class FacetCalculator {
	/** Default number of values returned per facet. */
	public const DEFAULT_FACET_LIMIT = 20;

	/**
	 * Apply a set of filter rows to a collection.
	 *
	 * @param array<int, array<string, mixed>> $rows
	 * @param array<int, array{field: string, op: string, value: mixed}> $filters
	 * @return array<int, array<string, mixed>> the surviving rows, reindexed
	 */
	public static function applyFilters(array $rows, array $filters, string $operator = 'AND'): array {
		if ($filters === []) {
			return array_values($rows);
		}

		$out = [];
		foreach ($rows as $row) {
			if (self::rowMatches($row, $filters, $operator)) {
				$out[] = $row;
			}
		}

		return $out;
	}

	/**
	 * Compute facets over a collection.
	 *
	 * @param array<int, array<string, mixed>> $rows
	 *        The candidate set. MUST already have the editor filters applied,
	 *        or narrowing rule (1) is not enforced.
	 * @param array<int, string> $facetFields fields to compute facets for
	 * @param array<int, array{field: string, op: string, value: mixed}> $refinements
	 *        Viewer refinements only. Never pass editor filters here.
	 * @param array<string, string> $labels optional field => label overrides
	 * @return array<int, array<string, mixed>>
	 */
	public static function compute(
		array $rows,
		array $facetFields,
		array $refinements = [],
		array $labels = [],
		int $facetLimit = self::DEFAULT_FACET_LIMIT
	): array {
		$facets = [];

		foreach ($facetFields as $field) {
			$field = FilterSpec::aliasField((string)$field);
			if ($field === '') {
				continue;
			}

			// Disjunctive rule: count over everything except this facet's own
			// refinements, so its sibling values stay selectable.
			$others = array_values(array_filter(
				$refinements,
				static fn(array $r): bool => ($r['field'] ?? '') !== $field
			));

			$scoped = self::applyFilters($rows, $others);
			$counts = self::countValues($scoped, $field);

			// Values the viewer has already selected must always be present,
			// even at count 0, or the checkbox would clear itself and the
			// chip would become unremovable.
			$selected = self::selectedValuesFor($refinements, $field);
			foreach ($selected as $value) {
				if (!array_key_exists($value, $counts)) {
					$counts[$value] = 0;
				}
			}

			$facets[] = self::buildFacet($field, $counts, $selected, $labels[$field] ?? null, $facetLimit);
		}

		return $facets;
	}

	/**
	 * Tally the values of one field across a collection.
	 *
	 * Multi-valued fields (a user's groups, a MetaVox multiselect) contribute
	 * one count per distinct value, so a facet's counts may legitimately sum
	 * to more than the row count. Never render a sum of facet counts as a
	 * total.
	 *
	 * @param array<int, array<string, mixed>> $rows
	 * @return array<string, int>
	 */
	private static function countValues(array $rows, string $field): array {
		$counts = [];

		foreach ($rows as $row) {
			$raw = self::extractField($row, $field);

			if ($raw === null) {
				continue;
			}

			$values = is_array($raw) ? $raw : [$raw];
			$seen = [];
			foreach ($values as $value) {
				if (!is_scalar($value)) {
					continue;
				}
				$key = trim((string)$value);
				if ($key === '' || isset($seen[$key])) {
					continue;
				}
				$seen[$key] = true;
				$counts[$key] = ($counts[$key] ?? 0) + 1;
			}
		}

		return $counts;
	}

	/**
	 * Order and truncate a facet's values.
	 *
	 * Selected values are pinned to the top so they never scroll out of view
	 * behind a "show more". The rest go by count desc, then label asc.
	 *
	 * @param array<string, int> $counts
	 * @param array<int, string> $selected
	 */
	private static function buildFacet(
		string $field,
		array $counts,
		array $selected,
		?string $label,
		int $facetLimit
	): array {
		$selectedMap = array_flip($selected);

		$entries = [];
		foreach ($counts as $value => $count) {
			$entries[] = [
				'value' => (string)$value,
				'label' => (string)$value,
				'count' => $count,
				'selected' => isset($selectedMap[(string)$value]),
			];
		}

		usort($entries, static function (array $a, array $b): int {
			if ($a['selected'] !== $b['selected']) {
				return $a['selected'] ? -1 : 1;
			}
			if ($a['count'] !== $b['count']) {
				return $b['count'] <=> $a['count'];
			}
			return strcasecmp($a['label'], $b['label']);
		});

		$limit = max(1, $facetLimit);
		$truncated = count($entries) > $limit;

		return [
			'field' => $field,
			'label' => $label ?? $field,
			'values' => array_slice($entries, 0, $limit),
			'truncated' => $truncated,
			'countsAvailable' => true,
		];
	}

	/**
	 * The values a viewer has selected within one field.
	 *
	 * @param array<int, array{field: string, op: string, value: mixed}> $refinements
	 * @return array<int, string>
	 */
	private static function selectedValuesFor(array $refinements, string $field): array {
		$values = [];

		foreach ($refinements as $refinement) {
			if (($refinement['field'] ?? '') !== $field) {
				continue;
			}
			$raw = $refinement['value'] ?? null;
			foreach (is_array($raw) ? $raw : [$raw] as $value) {
				if (is_scalar($value)) {
					$values[] = trim((string)$value);
				}
			}
		}

		return array_values(array_unique(array_filter($values, static fn(string $v): bool => $v !== '')));
	}

	/**
	 * Read a field from a row, tolerating the alias spellings.
	 *
	 * @param array<string, mixed> $row
	 */
	private static function extractField(array $row, string $field): mixed {
		if (array_key_exists($field, $row)) {
			return $row[$field];
		}

		// `group` is the filter-facing name for what a profile stores as
		// `groups`; the People filter engine already special-cases this.
		if ($field === 'group' && array_key_exists('groups', $row)) {
			return $row['groups'];
		}

		$aliased = FilterSpec::aliasField($field);
		if ($aliased !== $field && array_key_exists($aliased, $row)) {
			return $row[$aliased];
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $row
	 * @param array<int, array{field: string, op: string, value: mixed}> $filters
	 */
	private static function rowMatches(array $row, array $filters, string $operator): bool {
		$results = [];

		foreach ($filters as $filter) {
			$field = (string)($filter['field'] ?? '');
			if ($field === '') {
				continue;
			}
			$results[] = self::valueMatches(
				self::extractField($row, $field),
				(string)($filter['op'] ?? 'equals'),
				$filter['value'] ?? null
			);
		}

		if ($results === []) {
			return true;
		}

		return $operator === 'OR'
			? in_array(true, $results, true)
			: !in_array(false, $results, true);
	}

	/**
	 * Match one value against one operator.
	 *
	 * Mirrors the operator semantics of UserService::matchesSingleFilter() so
	 * a viewer refinement and an editor filter behave identically.
	 */
	private static function valueMatches(mixed $actual, string $op, mixed $expected): bool {
		$expectedList = is_array($expected) ? array_map('strval', $expected) : null;
		$actualList = is_array($actual)
			? array_values(array_filter(array_map(
				static fn($v): ?string => is_scalar($v) ? (string)$v : null,
				$actual
			), static fn(?string $v): bool => $v !== null))
			: null;

		switch ($op) {
			case 'equals':
				if ($actualList !== null) {
					return $expectedList !== null
						? array_intersect($actualList, $expectedList) !== []
						: in_array((string)$expected, $actualList, true);
				}
				if ($expectedList !== null) {
					return in_array((string)$actual, $expectedList, true);
				}
				return (string)$actual === (string)$expected;

			case 'in':
				$needles = $expectedList ?? [(string)$expected];
				if ($actualList !== null) {
					return array_intersect($actualList, $needles) !== [];
				}
				return in_array((string)$actual, $needles, true);

			case 'not_equals':
				// Mirror of 'equals'. For a list-valued field (groups) this
				// means "none of the values match", which is what excluding a
				// group has to mean — not "at least one differs".
				return !self::valueMatches($actual, 'equals', $expected);

			case 'not_in':
				return !self::valueMatches($actual, 'in', $expected);

			case 'contains':
				if (!is_string($actual) || !is_string($expected) || $expected === '') {
					return false;
				}
				return stripos($actual, $expected) !== false;

			case 'not_contains':
				if (!is_string($actual) || !is_string($expected) || $expected === '') {
					return true;
				}
				return stripos($actual, $expected) === false;

			case 'empty':
				return $actual === null || $actual === '' || $actual === [];

			case 'not_empty':
				return !($actual === null || $actual === '' || $actual === []);

			default:
				// Unknown operators must not silently widen the result set.
				return false;
		}
	}
}
