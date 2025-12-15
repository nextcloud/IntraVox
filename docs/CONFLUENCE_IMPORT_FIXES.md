# Confluence Import Fixes - December 2025

## Overview
This document summarizes the critical fixes applied to the Confluence HTML import functionality to ensure proper page structure, hierarchy preservation, and navigation integration.

## Issues Fixed

### 1. Page Folder Structure Mismatch
**Problem**: Imported pages were creating flat `page.json` files directly in folders instead of following IntraVox's native page structure pattern.

**Expected Structure** (from PageService.php createPageAtPath):
```
{pageId}/
  {pageId}.json
  _media/
    .nomedia
```

**Previous Incorrect Structure**:
```
{pageId}/
  page.json
```

**Solution Applied**:
- Modified `ImportService.php` lines 280-336 to replicate exact pattern from `PageService.php`
- Changed from `page.json` to `{pageId}.json`
- Added proper `_media` folder creation with `.nomedia` marker file
- Home page special handling: `home.json` directly in language folder

**Code Location**: `lib/Service/ImportService.php` lines 230-348

### 2. Navigation Integration Missing
**Problem**: Imported pages were not being added to the site navigation, making them inaccessible through the navigation menu.

**Requirements**:
- Add imported pages to `navigation.json` at correct hierarchical level
- Place under selected parent page
- Preserve Confluence parent-child hierarchy
- Use original Confluence page titles

**Solution Applied**:
- Created `updateNavigationWithImportedPages()` - orchestrates navigation update
- Created `buildPageTree()` - builds hierarchical tree from flat page list
- Created `findNavigationItemPath()` - locates parent item in navigation tree
- Created `getNavigationItemByPath()` - retrieves item reference by path
- Created `addPagesToNavigation()` - adds pages to navigation array

**Code Location**: `lib/Service/ImportService.php` lines 723-849

**Algorithm**:
1. Load current navigation for language
2. Build tree structure from imported pages (preserving Confluence hierarchy)
3. Find path to parent navigation item
4. Add imported pages as children of parent
5. Save updated navigation

### 3. uniqueId Format Mismatch (404 Errors)
**Problem**: Imported pages used `page_` prefix but IntraVox expected `page-` prefix, causing 404 errors when attempting to load pages.

**Root Cause**: `PageService.php` checks:
```php
if (strpos($originalId, 'page-') === 0)
```

But `IntermediateFormat.php` was generating:
```php
$this->uniqueId = uniqid('page_', true);  // WRONG
```

**Solution Applied**:
Changed to match IntraVox convention:
```php
$this->uniqueId = uniqid('page-', true);  // CORRECT
```

**Code Location**: `lib/Service/Import/IntermediateFormat.php` line 57

### 4. PHP Reference Syntax Error
**Problem**: Attempting to return a reference with a typed return value caused syntax error:
```
syntax error, unexpected token "&", expecting ";" at line 805
```

**Broken Code**:
```php
private function findNavigationItemByUniqueId(array &$items, string $uniqueId): ?array {
    foreach ($items as &$item) {
        if (isset($item['uniqueId']) && $item['uniqueId'] === $uniqueId) {
            return &$item;  // ❌ SYNTAX ERROR with typed return
        }
    }
}
```

**Solution Applied**: Refactored to use path-based traversal instead of references:
1. `findNavigationItemPath()` - returns array of indices to navigate to item
2. `getNavigationItemByPath()` - uses those indices to get reference to item

**Code Location**: `lib/Service/ImportService.php` lines 803-829

### 5. Page Order Preservation
**Problem**: Imported pages did not maintain the order from Confluence export.

**Solution Applied**: Order extracted from `index.html`:
- Pages receive order number (`confluenceOrder`) in metadata
- Order preserved in both navigation and page structure
- Sort algorithm respects both hierarchy and order

**Code Location**: `lib/Service/Import/ConfluenceHtmlImporter.php` lines 411-456, 533-536

### 6. Improved HTML Cleanup
**Problem**: Some pages displayed raw HTML like `</p>`, search forms, and breadcrumbs.

**Solution Applied**: Advanced HTML parsing with DOMDocument and XPath:
- Removes Confluence navigation elements (breadcrumbs, search forms)
- Cleans up orphaned HTML tags (tags without opening tags)
- Removes empty elements

**Code Location**: `lib/Service/Import/ConfluenceHtmlImporter.php` lines 297-416

### 7. UniqueId Format Consistency
**Problem**: Imported pages used `uniqid('page-', true)` which generates IDs with dots like `page-66b8c12a.87654321`, while native IntraVox page creation uses RFC 4122 UUID v4 format: `page-a1b2c3d4-e5f6-4789-abcd-ef1234567890`.

**Solution Applied**: Import now uses same UUID v4 format as native page creation:
- Added `generateUUID()` method to `IntermediatePage` class
- Generates RFC 4122 compliant UUID v4
- No dots in uniqueIds
- Consistent with how IntraVox creates pages via UI

**Code Location**: `lib/Service/Import/IntermediateFormat.php` lines 57, 60-76

**Benefits**:
- Consistent ID format throughout application
- RFC 4122 standard (industry best practice)
- Guaranteed uniqueness
- Better readability (no confusing dots)

## Implementation Details

### Folder Structure Creation Flow

```php
// Regular page (non-home):
1. Determine folderId from exportPath or uniqueId
2. Determine parent folder location
3. Create or get page folder: {pageId}/
4. Create or update JSON file: {pageId}/{pageId}.json
5. Ensure _media subfolder exists: {pageId}/_media/
6. Create .nomedia marker: {pageId}/_media/.nomedia

// Home page:
1. Create home.json directly in language folder
2. Ensure _media folder exists at language level
```

### Navigation Tree Building

```php
1. First pass: Create all navigation nodes from imported pages
   - Generate nav_id from uniqueId
   - Set title from page content
   - Initialize empty children array

2. Second pass: Build hierarchy
   - If page has parentUniqueId in lookup, add to parent's children
   - Otherwise, add to root level

3. Result: Tree structure preserving Confluence hierarchy
```

### Navigation Update Process

```php
1. Load current navigation.json for language
2. Build tree from imported pages
3. Find parent item in navigation:
   - Search recursively through navigation items
   - Return path as array of indices [0, 'children', 2, 'children', 1]
4. Navigate to parent using path
5. Add imported page tree to parent's children
6. Save updated navigation.json
```

## Files Modified

### Backend (PHP)
- `lib/Service/ImportService.php` - Major refactoring of page creation and navigation update
- `lib/Service/Import/IntermediateFormat.php` - Fixed uniqueId format

### Helper Functions Added
- `createMediaFolderMarker()` - Creates `.nomedia` marker file
- `getRelativePath()` - Calculates relative path between folders
- `updateNavigationWithImportedPages()` - Main navigation update orchestrator
- `buildPageTree()` - Builds hierarchical tree from flat pages
- `findNavigationItemPath()` - Finds path to navigation item
- `getNavigationItemByPath()` - Gets item reference by path
- `addPagesToNavigation()` - Adds pages to navigation array
- `extractPageOrderFromIndex()` - Extracts page order from index.html
- `removeUnwantedElements()` - Removes Confluence navigation elements
- `cleanupHtml()` - Cleans up orphaned and empty HTML tags
- `generateUUID()` - Generates RFC 4122 UUID v4 (in IntermediatePage)

## Testing Checklist

After browser hard refresh (Cmd+Shift+R / Ctrl+Shift+R):

- [ ] Import Confluence HTML ZIP
- [ ] Verify folder structure: `{pageId}/{pageId}.json` created
- [ ] Verify `_media/` folders exist with `.nomedia` marker
- [ ] Verify pages appear in navigation at correct level
- [ ] Verify hierarchy preserved from Confluence
- [ ] Verify page order matches Confluence export order
- [ ] Verify pages load without 404 errors
- [ ] Verify parent-child relationships correct
- [ ] Verify page titles match original Confluence titles
- [ ] Verify no raw HTML displayed (no `</p>`, search forms, etc.)
- [ ] Verify uniqueIds use UUID format: `page-XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX` (no dots)

## Known Issues

### JavaScript Module Loading Error (Pending Verification)
**Symptom**:
```javascript
TypeError: can't access property "call", n[e] is undefined
```

**Likely Cause**: Browser caching old JavaScript files

**Resolution**: User needs to perform hard refresh:
- Mac: Cmd + Shift + R
- Windows/Linux: Ctrl + Shift + R

**Status**: Awaiting user confirmation after hard refresh

## References

- Original request: "kijk goed naa intravox hoe dit werkt" (look carefully at how IntraVox does this)
- Navigation request: "voeg bij het importeren ook de items toe aan de navigatie op het juiste niveau"
- Reference implementation: `PageService.php` createPageAtPath() lines 1284-1350
- Navigation structure: `navigation.json` in IntraVox root per language

## Deployment

All changes deployed to server on December 15, 2025 at 10:41 UTC.

Build completed successfully with no errors.

**Latest Changes**:
- UniqueId format changed from `uniqid('page-', true)` to RFC 4122 UUID v4
- Page order preservation from Confluence index.html
- Improved HTML cleanup with DOMDocument/XPath parsing
