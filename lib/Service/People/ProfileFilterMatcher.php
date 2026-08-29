<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\People;

use OCA\IntraVox\Service\Filter\FilterSpec;

/**
 * Deciding whether a person matches a filter row.
 *
 * Extracted from UserService (service split, fase 2). Four methods with no
 * collaborators between them -- measured, not assumed -- so the whole thing
 * runs on plain arrays and can be tested without a user backend.
 *
 * NOT merged with the other two filter engines in the app, despite looking
 * like a third copy. The operator sets are genuinely different and the
 * differences are the point:
 *
 *   People      equals, contains, not_contains, in, empty, not_empty,
 *               is_today, within_next_days
 *   News        its own set, keyed to page metadata
 *   PhotoStory  equals, contains, in, year_equals
 *
 * is_today and within_next_days exist for birthdays and compare month-day
 * while ignoring the year; year_equals does the opposite. A single shared
 * engine would have to carry every operator for every caller, and each
 * caller would then silently accept operators that mean nothing for its data.
 *
 * Deliberately free of Nextcloud dependencies, like AccountScopePolicy.
 */
final class ProfileFilterMatcher {
    /**
     * Case-insensitive substring match across the searchable fields.
     *
     * This is a filter over the cohort, not a second search entrance: a term
     * can never surface a user the editor filters exclude.
     */
    public function applyFreeText(array $rows, string $q, array $searchFields): array {
        $needle = mb_strtolower(trim($q));
        if ($needle === '') {
            return $rows;
        }

        $fields = array_map(
            static fn($f): string => FilterSpec::aliasField((string)$f),
            $searchFields
        );
        if ($fields === []) {
            $fields = ['displayName'];
        }
        // Always allow matching on the name, which is what users expect a
        // free-text box next to a people list to do.
        if (!in_array('displayName', $fields, true)) {
            $fields[] = 'displayName';
        }

        return array_values(array_filter($rows, static function (array $row) use ($fields, $needle): bool {
            foreach ($fields as $field) {
                $value = $row[$field] ?? null;
                foreach (is_array($value) ? $value : [$value] as $candidate) {
                    if (is_scalar($candidate) && str_contains(mb_strtolower((string)$candidate), $needle)) {
                        return true;
                    }
                }
            }
            return false;
        }));
    }
    /**
     * Normalize a date string to ISO 8601 (YYYY-MM-DD) format.
     * Handles locale-specific formats like DD-MM-YYYY, DD/MM/YYYY, DD.MM.YYYY.
     *
     * @param string $value The date string to normalize
     * @return string|null ISO date string or null if unparseable
     */
    public function normalizeDateToISO(string $value): ?string {
        // Already in ISO format (YYYY-MM-DD)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // DD-MM-YYYY or DD/MM/YYYY or DD.MM.YYYY (European formats)
        if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $value, $matches)) {
            $date = \DateTime::createFromFormat('d-m-Y', $matches[1] . '-' . $matches[2] . '-' . $matches[3]);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Fallback: let PHP try to parse it
        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
    /**
     * Check if a user profile matches the given filters
     *
     * @param array $profile User profile
     * @param array $filters Array of filters
     * @param string $operator 'AND' or 'OR'
     * @return bool True if matches
     */
    public function matchesFilters(array $profile, array $filters, string $operator): bool {
        if (empty($filters)) {
            return true;
        }

        $results = [];
        foreach ($filters as $filter) {
            $fieldName = $filter['fieldName'];
            $filterOperator = $filter['operator'];
            // Support both 'value' and 'values' from frontend
            // Prefer non-empty 'values' array, fallback to 'value'
            $filterValue = (!empty($filter['values']) && is_array($filter['values']))
                ? $filter['values']
                : ($filter['value'] ?? null);

            // Get the actual value from profile
            $actualValue = $profile[$fieldName] ?? null;

            // Special handling for group filter
            if ($fieldName === 'group') {
                $actualValue = $profile['groups'] ?? [];
            }

            $results[] = $this->matchesSingleFilter($actualValue, $filterOperator, $filterValue);
        }

        if ($operator === 'AND') {
            return !in_array(false, $results, true);
        } else {
            return in_array(true, $results, true);
        }
    }
    /**
     * Check if a value matches a single filter condition
     *
     * @param mixed $actualValue The actual value from the profile
     * @param string $operator The filter operator
     * @param mixed $filterValue The filter value to match against
     * @return bool True if matches
     */
    public function matchesSingleFilter(mixed $actualValue, string $operator, mixed $filterValue): bool {
        switch ($operator) {
            case 'equals':
                // For arrays (like groups), check if filterValue is in the array
                if (is_array($actualValue)) {
                    return in_array($filterValue, $actualValue, true);
                }
                return $actualValue === $filterValue;

            case 'contains':
                if (is_string($actualValue) && is_string($filterValue)) {
                    return stripos($actualValue, $filterValue) !== false;
                }
                return false;

            case 'not_contains':
                if (is_string($actualValue) && is_string($filterValue)) {
                    return stripos($actualValue, $filterValue) === false;
                }
                return true;

            case 'in':
                // Value should be in the filter array
                $filterValues = is_array($filterValue) ? $filterValue : [$filterValue];
                if (is_array($actualValue)) {
                    // Check if any of the actual values are in the filter values
                    return !empty(array_intersect($actualValue, $filterValues));
                }
                return in_array($actualValue, $filterValues, true);

            case 'not_empty':
                if (is_array($actualValue)) {
                    return !empty($actualValue);
                }
                return $actualValue !== null && $actualValue !== '';

            case 'empty':
                if (is_array($actualValue)) {
                    return empty($actualValue);
                }
                return $actualValue === null || $actualValue === '';

            case 'is_today':
                // Compare month and day only (for birthdays)
                if (empty($actualValue)) {
                    return false;
                }
                try {
                    $date = new \DateTime($actualValue);
                    $today = new \DateTime();
                    return $date->format('m-d') === $today->format('m-d');
                } catch (\Exception $e) {
                    return false;
                }

            case 'within_next_days':
                // Check if month-day falls within next X days (for upcoming birthdays)
                if (empty($actualValue) || !is_numeric($filterValue)) {
                    return false;
                }
                try {
                    $date = new \DateTime($actualValue);
                    $today = new \DateTime();
                    $currentYear = (int)$today->format('Y');

                    // Create a date in the current year with the same month-day
                    $birthdayThisYear = new \DateTime($currentYear . '-' . $date->format('m-d'));

                    // If the birthday already passed this year, check next year
                    if ($birthdayThisYear < $today) {
                        $birthdayThisYear = new \DateTime(($currentYear + 1) . '-' . $date->format('m-d'));
                    }

                    $daysUntil = (int)$today->diff($birthdayThisYear)->days;
                    return $daysUntil <= (int)$filterValue;
                } catch (\Exception $e) {
                    return false;
                }

            default:
                return false;
        }
    }
}
