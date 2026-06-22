<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCP\IConfig;
use OCP\L10N\IFactory as IL10NFactory;
use Psr\Log\LoggerInterface;

/**
 * Central language registry for IntraVox.
 *
 * Two distinct sets of languages:
 *
 *   1. Discovered — every language IntraVox ships a translation file for in
 *      `l10n/<code>.json`. Grows automatically as Transifex delivers new
 *      translations.
 *
 *   2. Enabled — the subset of discovered languages the admin has activated
 *      for use in IntraVox menus, navigation and content folders. Stored as a
 *      JSON array in `oc_appconfig.intravox.enabled_languages`.
 *
 * `'en'` is non-removable from the enabled set — it is the guaranteed
 * fallback for users whose locale has no IntraVox content folder.
 *
 * The legacy hardcoded SUPPORTED_LANGUAGES array (`['nl','en','de','fr']`) is
 * the default for installs that upgrade from <1.6.0 — see Version10600 migration.
 */
class LanguageService {
    private const APP_ID = 'intravox';
    private const CONFIG_KEY_ENABLED = 'enabled_languages';
    private const CONFIG_KEY_PRIMARY = 'primary_language';
    private const DEFAULT_ENABLED_LANGUAGES = ['nl', 'en', 'de', 'fr'];
    private const FALLBACK_LANGUAGE = 'en';

    private IConfig $config;
    private IL10NFactory $l10nFactory;
    private LoggerInterface $logger;

    /** @var ?PageService Set via setter — injected lazily to avoid a constructor cycle (PageService → LanguageService → PageService). */
    private ?PageService $pageService = null;

    /** Request-scoped cache. Recomputing the l10n scan on every call inside one request is wasteful. */
    private ?array $discoveredCache = null;
    private ?array $enabledCache = null;
    private ?array $coverageCache = null;

    public function __construct(
        IConfig $config,
        IL10NFactory $l10nFactory,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->l10nFactory = $l10nFactory;
        $this->logger = $logger;
    }

    /**
     * Late-bind PageService so setEnabledLanguages() can flush caches.
     * Called by Application::register().
     */
    public function setPageService(PageService $pageService): void {
        $this->pageService = $pageService;
    }

    /**
     * Scan `l10n/*.json` and return every translated language code, deduped on
     * the base two-letter code (en_US -> en). English is always present even if
     * the file is missing — every UI code path falls back to it.
     *
     * @return string[] Sorted, base codes only.
     */
    public function getDiscoveredLanguages(): array {
        if ($this->discoveredCache !== null) {
            return $this->discoveredCache;
        }

        $l10nPath = __DIR__ . '/../../l10n';
        $discovered = [];

        if (is_dir($l10nPath)) {
            $files = scandir($l10nPath);
            if ($files !== false) {
                foreach ($files as $file) {
                    // Base codes are 2–3 letters (en, nl, ast); regional variants
                    // add a suffix (en_GB, es_419). Capture the base part.
                    if (preg_match('/^([a-z]{2,3})(_[A-Za-z0-9]+)?\.json$/', $file, $matches)) {
                        $baseLang = $matches[1];
                        if (!in_array($baseLang, $discovered, true)) {
                            $discovered[] = $baseLang;
                        }
                    }
                }
            }
        }

        if (!in_array(self::FALLBACK_LANGUAGE, $discovered, true)) {
            $discovered[] = self::FALLBACK_LANGUAGE;
        }

        sort($discovered);
        $this->discoveredCache = $discovered;
        return $discovered;
    }

    /**
     * How completely the IntraVox UI is translated per base language code, as a
     * percentage 0–100. Powers the admin "translation coverage" indicator so an
     * admin can see how much of the interface a content language's users will
     * actually see in their own language (vs. English fallback).
     *
     * Numerator: the number of translated strings in `l10n/<code>*.json` (the
     * largest regional variant wins, e.g. `de` ← `de_DE.json`).
     * Denominator: the source-string total from `l10n/.source-count.json` (a
     * committed artifact written by scripts/extract-en-json.js). If that file is
     * missing, fall back to the largest l10n file as a best-effort denominator.
     *
     * @return array<string,int> base code => percentage (0–100)
     */
    public function getTranslationCoverage(): array {
        if ($this->coverageCache !== null) {
            return $this->coverageCache;
        }

        $l10nPath = __DIR__ . '/../../l10n';
        $perBase = []; // base code => max translated-string count across its variants

        if (is_dir($l10nPath)) {
            $files = scandir($l10nPath) ?: [];
            foreach ($files as $file) {
                if (!preg_match('/^([a-z]{2,3})(_[A-Za-z0-9]+)?\.json$/', $file, $m)) {
                    continue;
                }
                $base = $m[1];
                $count = $this->countTranslations($l10nPath . '/' . $file);
                if (!isset($perBase[$base]) || $count > $perBase[$base]) {
                    $perBase[$base] = $count;
                }
            }
        }

        // Denominator: committed source-string total, else largest l10n file.
        $denominator = 0;
        $countFile = $l10nPath . '/.source-count.json';
        if (is_file($countFile)) {
            $data = json_decode((string)@file_get_contents($countFile), true);
            if (is_array($data) && isset($data['sourceStrings']) && is_int($data['sourceStrings'])) {
                $denominator = $data['sourceStrings'];
            }
        }
        if ($denominator <= 0) {
            $denominator = $perBase ? max($perBase) : 0;
        }

        $coverage = [];
        if ($denominator > 0) {
            foreach ($perBase as $base => $count) {
                $coverage[$base] = (int)min(100, round(100 * $count / $denominator));
            }
        }
        // English is the source language: always 100%.
        $coverage[self::FALLBACK_LANGUAGE] = 100;

        $this->coverageCache = $coverage;
        return $coverage;
    }

    /**
     * Count the translated strings in an l10n JSON file (the `translations`
     * object), tolerating a missing/corrupt file.
     */
    private function countTranslations(string $path): int {
        $data = json_decode((string)@file_get_contents($path), true);
        if (is_array($data) && isset($data['translations']) && is_array($data['translations'])) {
            return count($data['translations']);
        }
        return 0;
    }

    /**
     * @deprecated VoxCloud language model: a language is "active" when it has
     * content, not because it sits in this opt-in list. New code derives the
     * active set from content (see PageService::getLanguageContentStatus) and
     * offers ALL NC languages via getAvailableLanguages(). This method is kept
     * only so existing callers and downgrade paths keep working; the config key
     * is no longer written by the admin UI.
     *
     * Languages currently active in IntraVox.
     *
     * Falls back to the legacy 1.5.x hardcoded set if the config key is missing
     * — this is the upgrade-safety contract: a fresh upgrade must see exactly
     * the four languages the install had before. The Version10600 migration
     * persists the same set on first upgrade so subsequent reads are explicit.
     *
     * @return string[] Sorted, base codes only. 'en' is always present.
     */
    public function getEnabledLanguages(): array {
        if ($this->enabledCache !== null) {
            return $this->enabledCache;
        }

        $raw = $this->config->getAppValue(self::APP_ID, self::CONFIG_KEY_ENABLED, '');
        $enabled = self::DEFAULT_ENABLED_LANGUAGES;

        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $enabled = array_values(array_filter($decoded, 'is_string'));
            } else {
                $this->logger->warning('[LanguageService] enabled_languages config is not a JSON array, falling back to defaults');
            }
        }

        // English is non-negotiable.
        if (!in_array(self::FALLBACK_LANGUAGE, $enabled, true)) {
            $enabled[] = self::FALLBACK_LANGUAGE;
        }

        sort($enabled);
        $this->enabledCache = $enabled;
        return $enabled;
    }

    /**
     * Update the admin-chosen enabled set.
     *
     * Validates that every code corresponds to a discovered (= shipped)
     * translation, forces English in, and flushes the per-language page-tree
     * cache so the UI sees the new state immediately.
     *
     * @param string[] $codes
     */
    public function setEnabledLanguages(array $codes): void {
        $discovered = $this->getDiscoveredLanguages();

        $valid = [];
        foreach ($codes as $code) {
            if (is_string($code) && in_array($code, $discovered, true) && !in_array($code, $valid, true)) {
                $valid[] = $code;
            }
        }

        if (!in_array(self::FALLBACK_LANGUAGE, $valid, true)) {
            $valid[] = self::FALLBACK_LANGUAGE;
        }
        sort($valid);

        $this->config->setAppValue(self::APP_ID, self::CONFIG_KEY_ENABLED, json_encode($valid));
        $this->enabledCache = $valid;

        // Cache key pattern is `page_tree_{groupHash}_{lang}` — without an
        // invalidation, disabled languages keep serving from cache until TTL.
        if ($this->pageService !== null) {
            try {
                $this->pageService->invalidateAllCaches();
            } catch (\Throwable $e) {
                $this->logger->warning('[LanguageService] Cache invalidation after enabled_languages change failed: ' . $e->getMessage());
            }
        }
    }

    public function isLanguageEnabled(string $code): bool {
        return in_array($code, $this->getEnabledLanguages(), true);
    }

    /**
     * Every language Nextcloud knows about — common + other — deduped on the
     * base two-letter code and sorted by display name. This is the canonical
     * "available languages" set for the VoxCloud language model: admins pick
     * from ALL NC languages, not only the subset IntraVox happens to ship a
     * translation file for.
     *
     * Modelled on IntroVox's getAvailableLanguagesWithMetadata().
     *
     * @return array<int, array{code: string, name: string}> Sorted by name.
     */
    public function getAvailableLanguages(): array {
        $ncLanguages = $this->l10nFactory->getLanguages();
        $seen = [];
        $result = [];
        foreach (array_merge($ncLanguages['commonLanguages'] ?? [], $ncLanguages['otherLanguages'] ?? []) as $entry) {
            if (!isset($entry['code'], $entry['name'])) {
                continue;
            }
            // Base code = the part before any region suffix (nl_NL -> nl,
            // es_419 -> es). Do NOT truncate to 2 chars: some NC base codes are
            // 3 letters (e.g. 'ast' Asturianu, 'kab' Kabyle) and would otherwise
            // become an invalid 'as'/'ka' folder.
            $code = explode('_', $entry['code'])[0];
            // First entry per base code wins (commonLanguages is iterated first
            // and holds the localised default).
            if (!preg_match('/^[a-z]{2,3}$/', $code) || isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;
            $result[] = ['code' => $code, 'name' => $entry['name']];
        }

        // English is guaranteed present even if NC's list somehow omits it.
        if (!isset($seen[self::FALLBACK_LANGUAGE])) {
            $result[] = ['code' => self::FALLBACK_LANGUAGE, 'name' => 'English'];
        }

        usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $result;
    }

    /**
     * Is this a valid, NC-known base language code? Used to validate admin
     * input (primary language, "add language") against the available set
     * rather than the deprecated enabled set. Also the correct check for "is
     * this path segment a language folder?" — covers any language an admin can
     * add, not just the ones IntraVox ships a translation for.
     */
    public function isLanguageAvailable(string $code): bool {
        foreach ($this->getAvailableLanguages() as $lang) {
            if ($lang['code'] === $code) {
                return true;
            }
        }
        return false;
    }

    /**
     * The admin-chosen "primary" language: the recommended fallback shown first
     * when a user's own language has no content (see LanguageFallbackNotice).
     * Defaults to English. Validated against the available set on read so a
     * stale/invalid value degrades gracefully.
     */
    public function getPrimaryLanguage(): string {
        $code = $this->config->getAppValue(self::APP_ID, self::CONFIG_KEY_PRIMARY, '');
        if ($code === '' || !$this->isLanguageAvailable($code)) {
            return self::FALLBACK_LANGUAGE;
        }
        return $code;
    }

    /**
     * Persist the admin's primary-language choice. Must be a valid NC-known
     * base code; otherwise an InvalidArgumentException is thrown so the caller
     * can return a 400.
     */
    public function setPrimaryLanguage(string $code): void {
        if (!$this->isLanguageAvailable($code)) {
            throw new \InvalidArgumentException("Unknown language code: {$code}");
        }
        $this->config->setAppValue(self::APP_ID, self::CONFIG_KEY_PRIMARY, $code);
    }

    /**
     * The universal fallback language. English by design — the source language
     * for Transifex, and the assumed-known language for everyone reading the
     * Nextcloud admin docs.
     */
    public function getDefaultLanguage(): string {
        return self::FALLBACK_LANGUAGE;
    }

    /**
     * Admin-UI payload: every discovered language with its NC display name and
     * enabled status. Used by AdminSettings.vue to render the checkbox grid.
     *
     * @return array<int, array{code: string, name: string, isEnabled: bool, isDefault: bool}>
     */
    public function getLanguagesWithMetadata(): array {
        $discovered = $this->getDiscoveredLanguages();
        $enabled = $this->getEnabledLanguages();

        $ncLanguages = $this->l10nFactory->getLanguages();
        $nameByCode = [];
        foreach (array_merge($ncLanguages['commonLanguages'] ?? [], $ncLanguages['otherLanguages'] ?? []) as $entry) {
            if (!isset($entry['code']) || !isset($entry['name'])) {
                continue;
            }
            $baseCode = substr($entry['code'], 0, 2);
            // First entry wins — commonLanguages comes first and is the localised default.
            if (!isset($nameByCode[$baseCode])) {
                $nameByCode[$baseCode] = $entry['name'];
            }
        }

        $result = [];
        foreach ($discovered as $code) {
            $result[] = [
                'code' => $code,
                'name' => $nameByCode[$code] ?? strtoupper($code),
                'isEnabled' => in_array($code, $enabled, true),
                'isDefault' => $code === self::FALLBACK_LANGUAGE,
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $result;
    }
}
