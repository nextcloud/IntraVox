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
                    if (preg_match('/^([a-z]{2}(_[A-Z]{2})?)\.json$/', $file, $matches)) {
                        $baseLang = substr($matches[1], 0, 2);
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
