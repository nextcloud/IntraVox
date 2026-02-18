# OpenMetrics / Prometheus Integration for IntraVox

## Executive Summary

Nextcloud 33 (released February 18, 2026) introduces a native `/metrics` endpoint supporting OpenMetrics/Prometheus format. Apps can register custom metrics via the `\OCP\OpenMetrics\IMetricFamily` interface. This document evaluates how IntraVox can leverage this for monitoring and what value it brings to administrators.

---

## Nextcloud 33 OpenMetrics API

### How it works

- NC33 exposes a `/metrics` endpoint (localhost only by default, configurable via `openmetrics_allowed_clients`)
- Apps implement `IMetricFamily` interface and register exporters in `info.xml`
- Metrics appear automatically on the `/metrics` endpoint in standard Prometheus format

### Developer API

```php
<?php

namespace OCA\IntraVox\OpenMetrics;

use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\MetricType;
use OCP\OpenMetrics\Metric;

class PagesMetricFamily implements IMetricFamily {
    public function name(): string { return 'intravox_pages'; }
    public function type(): MetricType { return MetricType::gauge; }
    public function unit(): string { return ''; }
    public function help(): string { return 'Total IntraVox pages per language'; }

    public function metrics(): \Generator {
        // yield new Metric($value, ['label' => 'value']);
        yield new Metric(45, ['language' => 'nl']);
        yield new Metric(32, ['language' => 'en']);
    }
}
```

### Registration in `info.xml`

```xml
<openmetrics>
    <exporter>OCA\IntraVox\OpenMetrics\PagesMetricFamily</exporter>
</openmetrics>
```

### NC32 Compatibility

- NC32 ignores unknown XML elements like `<openmetrics>` — no errors
- The exporter classes are only instantiated by NC33's metrics loader
- As long as `IMetricFamily` is not referenced in autoloaded code (e.g., `Application.php`), NC32 remains unaffected
- **Risk: low**

### References

- Developer docs: https://docs.nextcloud.com/server/latest/developer_manual/digging_deeper/openmetrics.html
- Upgrade guide: https://docs.nextcloud.com/server/latest/developer_manual/app_publishing_maintenance/app_upgrade_guide/upgrade_to_33.html

---

## Available Data in IntraVox

IntraVox already collects extensive data that could be exposed as metrics:

### Content Data (LicenseService)

| Data | Method | Notes |
|------|--------|-------|
| Pages per language | `getPageCountsPerLanguage()` | Returns `['nl' => 45, 'en' => 32, ...]` |
| Total page count | `getTotalPageCount()` | Sum of all languages |
| License status | `getStats()` | Valid/invalid, limits per language |
| Page limit per language | `getStats()` | Free tier: 50 pages/language |

### Analytics Data (AnalyticsService)

| Data | Method | Notes |
|------|--------|-------|
| Total page views | `getDashboardStats($days)` | Requires analytics enabled |
| Unique users | `getDashboardStats($days)` | Hashed user tracking |
| Pages viewed | `getDashboardStats($days)` | Distinct pages |
| Top pages | `getTopPages($limit, $days)` | Ranked by views |

### Engagement Data (CommentService / Nextcloud ICommentsManager)

| Data | Method | Notes |
|------|--------|-------|
| Comment count | `ICommentsManager::getNumberOfCommentsForObject()` | Per page or total |
| Reaction counts | `getPageReactions()` | Emoji distribution |

### System Data (TelemetryService)

| Data | Method | Notes |
|------|--------|-------|
| Total users | `collectData()` | From intravox group |
| Active users (30d) | `collectData()` | Last login check |
| Analytics enabled | `IConfig::getAppValue()` | Boolean config |
| Retention days | `IConfig::getAppValue()` | Default: 90 |

---

## Proposed Metrics

### Tier 1: Content Metrics (lightweight, always available)

| Metric Name | Type | Labels | Source |
|-------------|------|--------|--------|
| `intravox_pages_total` | gauge | `language` | `LicenseService::getPageCountsPerLanguage()` |
| `intravox_pages_license_limit` | gauge | `language` | `LicenseService::getStats()` |
| `intravox_license_valid` | gauge | — | `LicenseService::getStats()` (1 or 0) |

**Value for admins:**
- Monitor content growth per language over time
- Set Grafana alerts when approaching license page limits
- Detect license expiration

### Tier 2: Analytics Metrics (requires analytics to be enabled)

| Metric Name | Type | Labels | Source |
|-------------|------|--------|--------|
| `intravox_page_views_total` | gauge | `period` (7d/30d) | `AnalyticsService::getDashboardStats()` |
| `intravox_pages_viewed_total` | gauge | `period` | `AnalyticsService::getDashboardStats()` |
| `intravox_unique_users_total` | gauge | `period` | `AnalyticsService::getDashboardStats()` |

**Value for admins:**
- Track intranet adoption in Grafana dashboards
- Correlate page views with organizational events (launches, announcements)
- Identify low-traffic periods for maintenance windows

### Tier 3: Engagement Metrics

| Metric Name | Type | Labels | Source |
|-------------|------|--------|--------|
| `intravox_comments_total` | gauge | — | `ICommentsManager` |
| `intravox_analytics_enabled` | gauge | — | config value (1 or 0) |

**Value for admins:**
- Measure user engagement with intranet content
- Track whether engagement features are being used

---

## Implementation Approach

### File structure

```
lib/OpenMetrics/
├── PagesMetricFamily.php        # Tier 1: page counts per language
├── LicenseMetricFamily.php      # Tier 1: license status & limits
└── AnalyticsMetricFamily.php    # Tier 2+3: views, engagement
```

### Key design decisions

1. **Separate classes per metric family** — keeps each exporter focused and testable
2. **Reuse existing services** — no new database queries or API calls needed
3. **Graceful degradation** — analytics metrics return 0 or are omitted when analytics is disabled
4. **No NC32 impact** — `<openmetrics>` section is ignored by NC32

### Performance considerations

- `/metrics` is called by Prometheus at scrape intervals (typically 15s-60s)
- `getPageCountsPerLanguage()` does a filesystem walk — consider caching if scrape interval is low
- `getDashboardStats()` queries the `oc_intravox_page_stats` table — lightweight with proper indexes
- Total overhead per scrape: negligible (existing data, no heavy computation)

---

## Example Prometheus Output

```
# HELP nextcloud_intravox_pages Total IntraVox pages per language
# TYPE nextcloud_intravox_pages gauge
nextcloud_intravox_pages{language="nl"} 45
nextcloud_intravox_pages{language="en"} 32
nextcloud_intravox_pages{language="de"} 28
nextcloud_intravox_pages{language="fr"} 15

# HELP nextcloud_intravox_license_valid Whether IntraVox license is valid
# TYPE nextcloud_intravox_license_valid gauge
nextcloud_intravox_license_valid 1

# HELP nextcloud_intravox_page_views_total Total page views in period
# TYPE nextcloud_intravox_page_views_total gauge
nextcloud_intravox_page_views_total{period="7d"} 1234
nextcloud_intravox_page_views_total{period="30d"} 5678

# HELP nextcloud_intravox_comments_total Total comments across all pages
# TYPE nextcloud_intravox_comments_total gauge
nextcloud_intravox_comments_total 89
```

---

## Grafana Dashboard Ideas

With these metrics, admins could build dashboards showing:

- **Content Growth** — line chart of `intravox_pages_total` over time per language
- **Adoption Rate** — `intravox_unique_users_total` / total Nextcloud users
- **Engagement Rate** — comments per page view
- **License Utilization** — `intravox_pages_total` vs `intravox_pages_license_limit` with threshold alerts
- **Activity Heatmap** — page views over time

---

## Priority Assessment

| Priority | Reasoning |
|----------|-----------|
| **Low-Medium** | Valuable for enterprise deployments with existing Prometheus/Grafana, but not blocking for core functionality. Consider implementing when targeting enterprise customers or after core feature stabilization. |

## Recommendation

Start with **Tier 1 only** (3 metrics: pages total, license limit, license valid). These are:
- Lightweight (reuse `LicenseService` which is already called periodically)
- Universally useful (every admin wants to know page counts and license status)
- Simple to implement (2 small PHP classes + info.xml registration)

Add Tier 2 and 3 later based on user demand.
