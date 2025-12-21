# IntraVox v0.8 Implementation Plan

## Overview

This document outlines the remaining tasks for IntraVox v0.8 production readiness. All security fixes implemented in December 2024 are complete. This plan focuses on the outstanding items.

---

## Status Summary

### Completed (December 2024)
- [x] SQL Injection (QueryBuilder)
- [x] XSS Prevention (DOMPurify)
- [x] CSRF Protection (Nextcloud framework)
- [x] ZIP Slip Prevention (safeExtractZip)
- [x] Temp File Security (random_bytes + 0700)
- [x] Import Permission Checks
- [x] Comment IDOR Prevention
- [x] iframe Sandboxing
- [x] Sensitive Log Masking
- [x] Memory Leak Fixes (beforeUnmount)
- [x] Strict Types
- [x] Bundle Build
- [x] N+1 Query Fixes
- [x] Request-level Caching
- [x] SVG Sanitization (enshrined/svg-sanitize)

### Remaining Tasks
| Priority | Task | Severity | Effort |
|----------|------|----------|--------|
| P0 | @PublicPage validation | HIGH | 2h |
| P0 | parentPageId validation | CRITICAL | 2h |
| P1 | Extended path sanitization | MEDIUM | 1h |
| P2 | IDOR in version restoration | LOW | 1h |
| P3 | Unit/Integration Tests | - | 16h+ |
| P3 | API Documentation | - | 8h |
| P4 | WCAG Accessibility Audit | - | 8h |
| P4 | Performance profiling | - | 4h |

---

## Phase 1: Security Fixes (Priority P0-P1)

### 1.1 @PublicPage Validation

**File:** `lib/Controller/PageController.php:118-148`

**Problem:** The `showByUniqueId()` method has `@PublicPage` annotation but doesn't validate if the page is actually meant to be public.

**Current Code:**
```php
/**
 * @NoAdminRequired
 * @NoCSRFRequired
 * @PublicPage
 */
public function showByUniqueId(string $uniqueId): TemplateResponse {
    $pageData = $this->pageService->getPage($uniqueId);
    // No validation if page is public
}
```

**Solution Options:**

**Option A: Remove @PublicPage (Recommended for v0.8)**
- Remove `@PublicPage` annotation entirely
- All pages require Nextcloud authentication
- Simplest and most secure approach

**Option B: Implement public sharing feature (Future)**
- Add `isPublic` boolean to page metadata
- Add share management UI
- Validate in controller before serving

**Implementation (Option A):**
```php
/**
 * @NoAdminRequired
 * @NoCSRFRequired
 * // @PublicPage REMOVED - all pages require authentication
 */
public function showByUniqueId(string $uniqueId): TemplateResponse {
    // Existing code works - Nextcloud enforces auth
}
```

**Files to modify:**
- `lib/Controller/PageController.php` - Remove @PublicPage annotation

---

### 1.2 parentPageId Validation (CRITICAL)

**Files:**
- `lib/Controller/ApiController.php:1303-1343` (Confluence import)
- `lib/Service/ImportService.php:182-186`

**Problem:** No validation that parentPageId:
1. Actually exists
2. User has write permission
3. Is in the same language/groupfolder

**Current Code (ApiController.php):**
```php
if ($parentPageId) {
    foreach ($export['pages'] as &$page) {
        if (empty($page['parentUniqueId'])) {
            $page['parentUniqueId'] = $parentPageId;
        }
    }
}
// No validation!
```

**Solution:**

Add validation helper method to ApiController:
```php
/**
 * Validate parentPageId parameter
 *
 * @param string $parentPageId The parent page unique ID
 * @param string $targetLanguage The target language for import
 * @return array|null Parent page data if valid, null with error response if invalid
 */
private function validateParentPageId(string $parentPageId, string $targetLanguage): ?array {
    try {
        $parentPage = $this->pageService->getPage($parentPageId);
    } catch (\Exception $e) {
        return null; // Parent not found
    }

    // Check write permission
    if (!($parentPage['permissions']['canWrite'] ?? false)) {
        return null; // No permission
    }

    // Check same language
    $parentLanguage = $parentPage['language'] ?? null;
    if ($parentLanguage !== $targetLanguage) {
        return null; // Different language/groupfolder
    }

    return $parentPage;
}
```

Update Confluence import endpoint:
```php
public function importFromConfluenceHtml(): JSONResponse {
    // ... existing code ...

    $parentPageId = $this->request->getParam('parentPageId', null);
    $language = $this->request->getParam('language', 'nl');

    if ($parentPageId) {
        $parentPage = $this->validateParentPageId($parentPageId, $language);
        if ($parentPage === null) {
            return new JSONResponse([
                'error' => 'Invalid parent page: not found, no permission, or different language'
            ], Http::STATUS_BAD_REQUEST);
        }
    }

    // ... continue with import ...
}
```

**Files to modify:**
- `lib/Controller/ApiController.php` - Add validateParentPageId(), update import methods

---

### 1.3 Extended Path Sanitization

**Files:**
- `lib/Controller/ApiController.php:1439-1457`
- `lib/Service/PageService.php:2277-2283`

**Problem:** Current sanitizePath() is incomplete:
- No null byte check
- No Unicode normalization
- No hidden file check
- No executable extension check

**Current Code:**
```php
private function sanitizePath(string $path): string {
    $path = trim($path, '/');
    if (strpos($path, '..') !== false || strpos($path, '\\') !== false) {
        throw new \InvalidArgumentException('Invalid path');
    }
    // Missing checks...
    return $path;
}
```

**Solution:**
```php
private function sanitizePath(string $path): string {
    // 1. Null byte check
    if (strpos($path, "\0") !== false) {
        throw new \InvalidArgumentException('Null bytes not allowed in path');
    }

    // 2. Unicode normalization (prevent NFD/NFC attacks)
    if (class_exists('Normalizer')) {
        $normalized = \Normalizer::normalize($path, \Normalizer::FORM_C);
        if ($normalized === false) {
            throw new \InvalidArgumentException('Invalid unicode sequence in path');
        }
        $path = $normalized;
    }

    // 3. Trim slashes
    $path = trim($path, '/');

    // 4. Directory traversal check
    if (strpos($path, '..') !== false || strpos($path, '\\') !== false) {
        throw new \InvalidArgumentException('Path traversal not allowed');
    }

    // 5. Validate each segment
    $segments = explode('/', $path);
    foreach ($segments as $segment) {
        // Empty segments
        if (empty($segment) || $segment === '.' || $segment === '..') {
            throw new \InvalidArgumentException('Invalid path segment');
        }

        // Hidden files (starting with dot)
        if (substr($segment, 0, 1) === '.') {
            throw new \InvalidArgumentException('Hidden files not allowed');
        }

        // Executable extensions
        if (preg_match('/\.(php|phtml|php[345]|phar|phps|pht)$/i', $segment)) {
            throw new \InvalidArgumentException('Executable files not allowed');
        }
    }

    return $path;
}
```

**Files to modify:**
- `lib/Controller/ApiController.php` - Update sanitizePath()
- `lib/Service/PageService.php` - Update sanitizePath() to match

---

### 1.4 IDOR in Version Restoration

**File:** `lib/Service/PageService.php:2528-2533`

**Status:** Currently DISABLED - version functionality returns empty array.

```php
public function getPageVersions(string $pageId): array {
    // Versioning DISABLED due to Nextcloud 32 limitations
    return [];
}
```

**Required when re-enabled:**
- Verify page ownership before listing versions
- Verify write permission before restoring
- Validate version timestamp belongs to requested page

**Implementation (for future):**
```php
public function restorePageVersion(string $pageId, int $timestamp): bool {
    // 1. Get page and verify access
    $page = $this->getPage($pageId);
    if (!($page['permissions']['canWrite'] ?? false)) {
        throw new \RuntimeException('No write permission for this page');
    }

    // 2. Verify version belongs to this page
    $versions = $this->getPageVersions($pageId);
    $validVersion = false;
    foreach ($versions as $version) {
        if ($version['timestamp'] === $timestamp) {
            $validVersion = true;
            break;
        }
    }

    if (!$validVersion) {
        throw new \RuntimeException('Version not found for this page');
    }

    // 3. Proceed with restore
    // ... existing restore logic ...
}
```

**Files to modify (when versioning re-enabled):**
- `lib/Service/PageService.php` - Add permission checks to version methods

---

## Phase 2: Testing Infrastructure (Priority P3)

### 2.1 PHPUnit Setup

**New files to create:**
```
phpunit.xml
tests/
├── bootstrap.php
├── Unit/
│   ├── Service/
│   │   ├── PageServiceTest.php
│   │   ├── ImportServiceTest.php
│   │   └── CommentServiceTest.php
│   └── Controller/
│       ├── ApiControllerTest.php
│       └── PageControllerTest.php
└── Integration/
    ├── SVGUploadTest.php
    ├── PermissionTest.php
    └── ImportExportTest.php
```

**composer.json additions:**
```json
{
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "phpstan/phpstan": "^1.10"
  },
  "scripts": {
    "test": "phpunit",
    "phpstan": "phpstan analyse lib/"
  }
}
```

**Priority test cases:**
1. SVG sanitization (XSS, XXE, event handlers)
2. Path sanitization (traversal, null bytes)
3. Permission checks (IDOR prevention)
4. Import validation (parentPageId, file types)

### 2.2 JavaScript Tests (Vitest)

**package.json additions:**
```json
{
  "devDependencies": {
    "vitest": "^1.0.0",
    "@vue/test-utils": "^2.4.0",
    "happy-dom": "^12.0.0"
  },
  "scripts": {
    "test": "vitest",
    "test:coverage": "vitest --coverage"
  }
}
```

**Test files to create:**
```
src/
└── __tests__/
    ├── components/
    │   ├── MediaPicker.spec.js
    │   ├── Widget.spec.js
    │   └── PageEditor.spec.js
    └── services/
        └── ApiService.spec.js
```

---

## Phase 3: Documentation (Priority P3)

### 3.1 OpenAPI Specification

**File to create:** `docs/openapi.yaml`

Content outline:
- OpenAPI 3.0.3 specification
- All 34 API endpoints documented
- Request/response schemas
- Authentication requirements
- Error response formats

### 3.2 API Reference

**File to create:** `docs/API.md`

Content outline:
- Authentication instructions
- Endpoint reference with examples
- Error codes and handling
- Rate limiting information

---

## Phase 4: Quality & Performance (Priority P4)

### 4.1 WCAG Accessibility Audit

**Areas to audit:**
- Keyboard navigation
- Screen reader compatibility
- Color contrast ratios
- ARIA labels
- Focus management

**Tools:**
- axe DevTools
- WAVE
- Lighthouse accessibility audit

### 4.2 Performance Profiling

**Areas to profile:**
- Initial page load time
- API response times
- Bundle size analysis
- Database query performance

**Tools:**
- Lighthouse performance audit
- Nextcloud profiler
- Browser DevTools Network tab

---

## Implementation Order

### Week 1: Security (P0-P1)
1. Remove @PublicPage from PageController
2. Add parentPageId validation to ApiController
3. Enhance sanitizePath() in both files
4. Test all changes on 3dev server

### Week 2: Testing Setup (P3)
1. Configure PHPUnit
2. Write security-critical tests
3. Configure Vitest
4. Write component tests

### Week 3: Documentation (P3)
1. Create OpenAPI spec
2. Write API reference
3. Update README

### Week 4: Quality (P4)
1. WCAG audit and fixes
2. Performance profiling
3. Final review

---

## Files Modified Summary

| File | Changes |
|------|---------|
| `lib/Controller/PageController.php` | Remove @PublicPage |
| `lib/Controller/ApiController.php` | Add validateParentPageId(), enhance sanitizePath() |
| `lib/Service/PageService.php` | Enhance sanitizePath() |
| `composer.json` | Add PHPUnit dev dependency |
| `package.json` | Add Vitest dev dependencies |
| `docs/openapi.yaml` | New file - API spec |
| `docs/API.md` | New file - API reference |
| `tests/**` | New directory - all tests |

---

## Quick Reference Commands

```bash
# Run security fixes
cd /Users/rikdekker/Documents/Development/IntraVox

# Build and deploy
npm run build
./deploy-3dev-no-setup.sh

# Run tests (after setup)
composer test
npm run test

# Static analysis
./vendor/bin/phpstan analyse lib/
npm run lint

# View logs
ssh rdekker@145.38.188.218 'sudo tail -f /var/www/nextcloud/data/nextcloud.log'
```

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| @PublicPage removal breaks existing links | Low | Medium | No public sharing currently used |
| parentPageId validation breaks imports | Medium | Low | Test with existing Confluence exports |
| Path sanitization too strict | Low | Low | Allow common characters, test edge cases |
| Test setup time overrun | Medium | Low | Focus on security tests first |

---

*Document created: December 2024*
*Last updated: December 2024*
*Version: 0.8.0-rc1*
