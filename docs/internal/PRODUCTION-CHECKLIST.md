# Nextcloud App Production Checklist

## Overview

This checklist is intended for making a Nextcloud app like IntraVox production-ready. All items must be verified before the app goes to production.

---

## 1. SECURITY

### 1.1 Input Validation & Sanitization
- [ ] **SQL Injection**: All database queries use QueryBuilder with parameters
- [ ] **XSS Prevention**: All user input is sanitized (DOMPurify, htmlspecialchars)
- [ ] **Path Traversal**: All file paths are validated (no `..`, null bytes, etc.)
- [ ] **Command Injection**: Shell commands use `escapeshellarg()` / `escapeshellcmd()`
- [ ] **SSRF Prevention**: External URLs are validated against whitelist

### 1.2 Authentication & Authorization
- [ ] **Admin endpoints**: All admin-only endpoints lack `@NoAdminRequired`
- [ ] **Permission checks**: Every data mutation checks user permissions
- [ ] **IDOR Prevention**: Object IDs are validated against user ownership
- [ ] **CSRF Protection**: All POST/PUT/DELETE endpoints have CSRF tokens
- [ ] **Rate Limiting**: Sensitive endpoints have rate limiting

### 1.3 File Handling
- [ ] **Upload Validation**: MIME type + magic bytes verification
- [ ] **ZIP Slip Prevention**: ZIP extraction validates all paths
- [ ] **Temp Files**: Cryptographically secure names + restrictive permissions (0700)
- [ ] **File Size Limits**: Maximum upload size configured
- [ ] **SVG Sanitization**: SVG uploads are sanitized server-side

### 1.4 Data Protection
- [ ] **Sensitive Logging**: No passwords, tokens, or PII in logs
- [ ] **Encryption at Rest**: Sensitive data encrypted when stored
- [ ] **Secure Headers**: CSP, X-Frame-Options, X-Content-Type-Options

---

## 2. CODE QUALITY

### 2.1 PHP Code
- [ ] **Strict Types**: `declare(strict_types=1)` in all PHP files
- [ ] **Type Hints**: All functions have parameter and return type hints
- [ ] **PSR-12**: Code follows PSR-12 coding standard
- [ ] **PHPStan/Psalm**: No level 5+ errors
- [ ] **No Deprecated**: No deprecated Nextcloud APIs used

### 2.2 Frontend Code
- [ ] **ESLint**: No errors or warnings
- [ ] **TypeScript**: No `any` types (if using TS)
- [ ] **Bundle Size**: JavaScript bundles < 500KB (gzip)
- [ ] **No Console**: No `console.log` in production code
- [ ] **Memory Leaks**: Event listeners are cleaned up in `beforeUnmount`

### 2.3 Dependencies
- [ ] **npm audit**: No high/critical vulnerabilities
- [ ] **composer audit**: No security advisories
- [ ] **Outdated Packages**: All packages within 2 major versions
- [ ] **License Check**: All dependencies have compatible licenses

---

## 3. NEXTCLOUD SPECIFIC

### 3.1 App Metadata
- [ ] **info.xml**: Correct version, min/max NC version, category
- [ ] **CHANGELOG.md**: Up-to-date with all changes
- [ ] **README.md**: Installation instructions, screenshots, requirements
- [ ] **LICENSE**: Correct license (AGPL-3.0 for Nextcloud apps)
- [ ] **Screenshots**: At least 3 screenshots for App Store

### 3.2 Database
- [ ] **Migrations**: All schema changes via migrations
- [ ] **Indexes**: Proper indexes on frequently used queries
- [ ] **Rollback**: Migrations support downgrade
- [ ] **No Raw SQL**: Only QueryBuilder, no raw SQL

### 3.3 Background Jobs
- [ ] **Cron Jobs**: Registered in `info.xml`
- [ ] **Timeouts**: Jobs have reasonable timeouts
- [ ] **Error Handling**: Jobs log errors, don't crash silently
- [ ] **Idempotent**: Jobs can be safely re-executed

### 3.4 API
- [ ] **OCS API**: Endpoints follow OCS standard
- [ ] **Versioning**: API version in URL or header
- [ ] **Documentation**: OpenAPI/Swagger spec available
- [ ] **Error Responses**: Consistent error format with codes

---

## 4. PERFORMANCE

### 4.1 Database
- [ ] **N+1 Queries**: No N+1 query patterns
- [ ] **Batch Operations**: Bulk inserts/updates where possible
- [ ] **Query Caching**: Request-level caching for repeated queries
- [ ] **Lazy Loading**: Large datasets are paginated

### 4.2 Frontend
- [ ] **Code Splitting**: Lazy loading for large components
- [ ] **Image Optimization**: Images compressed
- [ ] **Caching**: Correct cache headers for static assets
- [ ] **Debouncing**: Input handlers are debounced

### 4.3 Backend
- [ ] **Async Operations**: Heavy tasks in background jobs
- [ ] **Memory Limits**: Large operations stream data
- [ ] **File Caching**: OPcache compatible

---

## 5. TESTING

### 5.1 Unit Tests
- [ ] **Coverage**: Minimum 60% code coverage
- [ ] **Critical Paths**: 100% coverage for auth/security code
- [ ] **Edge Cases**: Null, empty, boundary values tested

### 5.2 Integration Tests
- [ ] **API Tests**: All endpoints have integration tests
- [ ] **Database Tests**: Migrations up/down tested
- [ ] **Permission Tests**: ACL/permission checks tested

### 5.3 Manual Testing
- [ ] **Fresh Install**: App works on clean NC installation
- [ ] **Upgrade Path**: Upgrade from previous version works
- [ ] **Multi-user**: Works correctly with multiple users
- [ ] **Mobile**: Responsive design tested on mobile

---

## 6. DEPLOYMENT

### 6.1 Build Process
- [ ] **Reproducible**: Build is deterministic
- [ ] **No Dev Dependencies**: No dev packages in production bundle
- [ ] **Minification**: JS/CSS minified
- [ ] **Source Maps**: Source maps NOT in production

### 6.2 Release
- [ ] **Semantic Versioning**: Version follows semver
- [ ] **Git Tag**: Release tagged in git
- [ ] **Signed**: Package cryptographically signed
- [ ] **Release Notes**: Changelog for users

### 6.3 Monitoring
- [ ] **Error Logging**: Errors go to NC log
- [ ] **Health Check**: Endpoint for monitoring
- [ ] **Metrics**: Key metrics are logged

---

## 7. DOCUMENTATION

### 7.1 User Documentation
- [ ] **Installation Guide**: Step-by-step installation
- [ ] **User Manual**: How to use the app
- [ ] **FAQ**: Frequently asked questions answered
- [ ] **Troubleshooting**: Known issues and solutions

### 7.2 Developer Documentation
- [ ] **Architecture**: Overview of codebase structure
- [ ] **API Reference**: All endpoints documented
- [ ] **Contributing**: How to contribute
- [ ] **Security Policy**: How to report vulnerabilities

---

## 8. COMPLIANCE

### 8.1 Privacy
- [ ] **GDPR**: Data export/delete functionality
- [ ] **Data Minimization**: Only necessary data stored
- [ ] **Retention**: Data retention policy documented
- [ ] **Third Parties**: External services documented

### 8.2 Accessibility
- [ ] **WCAG 2.1**: Level AA compliance
- [ ] **Keyboard Navigation**: All functions via keyboard
- [ ] **Screen Readers**: ARIA labels correct
- [ ] **Color Contrast**: Minimum 4.5:1 ratio

---

## INTRAVOX v0.8 STATUS

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
- [x] N+1 Query Fixes (batch MetaVox queries)
- [x] Request-level Caching

### Still To Do
- [ ] IDOR in version restoration
- [ ] @PublicPage validation
- [ ] parentPageId validation
- [ ] Extended path sanitization
- [ ] SVG Sanitization library
- [ ] Unit/Integration Tests
- [ ] API Documentation
- [ ] WCAG Accessibility Audit
- [ ] Performance profiling

---

## Quick Reference Commands

```bash
# Security audit
npm audit
composer audit

# Code quality
./vendor/bin/phpstan analyse lib/
npm run lint

# Build
npm run build

# Deploy to test
./deploy-3dev-no-setup.sh

# View logs
ssh rdekker@145.38.188.218 'sudo tail -f /var/www/nextcloud/data/nextcloud.log'
```
