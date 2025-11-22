# IntraVox Technical Deep Dive

This document provides technical implementation details of how pages, breadcrumbs, and images are loaded in IntraVox.

## Table of Contents

1. [Page Loading Implementation](#page-loading-implementation)
2. [Breadcrumb Generation](#breadcrumb-generation)
3. [Image Loading Implementation](#image-loading-implementation)
4. [Common Issues and Solutions](#common-issues-and-solutions)
5. [Performance Optimization](#performance-optimization)
6. [Testing and Debugging](#testing-and-debugging)

## Page Loading Implementation

### Frontend Request Flow

1. **User Navigation**
   - User clicks navigation link or enters URL
   - Hash change detected: `window.location.hash` (e.g., `#page-6f0c9h18-g7e5-4h41-1d0h-2g6f5e7g9c0h`)
   - App.vue calls `selectPage(pageId)`

2. **API Request**
   ```javascript
   // Frontend makes API call
   axios.get(`/apps/intravox/api/pages/${pageId}`)
   ```

3. **Backend Processing** (PageService.php)

### Critical Bug Fix: Sanitization Timing (v0.4.27)

**The Problem:**
The original code sanitized the pageId BEFORE checking if it was a uniqueId, which stripped the 'page-' prefix needed for identification.

```php
// WRONG - Old implementation
public function getPage(string $id): array {
    $id = $this->sanitizeId($id);  // Removes 'page-' prefix

    if (strpos($id, 'page-') === 0) {  // This check ALWAYS fails!
        $result = $this->findPageByUniqueId($folder, $id);
    }
}

private function sanitizeId(string $id): string {
    if (strpos($id, 'page-') === 0) {
        $id = substr($id, 5);  // Strips 'page-' prefix
    }
    return $id;
}
```

**The Solution:**
Preserve the original ID before sanitization and use it for uniqueId matching.

```php
// CORRECT - Fixed implementation (v0.4.27)
public function getPage(string $id): array {
    $folder = $this->getLanguageFolder();
    $result = null;

    // Save original ID before sanitization
    $originalId = $id;

    // Check for uniqueId pattern BEFORE sanitization
    if (strpos($originalId, 'page-') === 0) {
        $result = $this->findPageByUniqueId($folder, $originalId);
    }

    // Only sanitize for legacy ID fallback
    if ($result === null) {
        $id = $this->sanitizeId($originalId);
        $result = $this->findPageById($folder, $id);
    }

    if ($result === null) {
        throw new \Exception('Page not found');
    }

    return $result;
}
```

### Page Lookup Methods

**Method 1: UniqueId Lookup (Modern)**
```php
private function findPageByUniqueId($folder, string $uniqueId): ?array {
    // Recursively search all folders
    foreach ($folder->getDirectoryListing() as $item) {
        if ($item->getType() === FileInfo::TYPE_FOLDER) {
            // Recurse into subfolders
            $result = $this->findPageByUniqueId($item, $uniqueId);
            if ($result !== null) {
                return $result;
            }
        } elseif ($item->getType() === FileInfo::TYPE_FILE) {
            // Check JSON files for matching uniqueId
            if (substr($item->getName(), -5) === '.json') {
                $data = json_decode($item->getContent(), true);

                // Compare uniqueId field in JSON
                if ($data && isset($data['uniqueId']) &&
                    $data['uniqueId'] === $uniqueId) {
                    return [
                        'file' => $item,
                        'data' => $data,
                        'folder' => $folder
                    ];
                }
            }
        }
    }
    return null;
}
```

**Method 2: Legacy ID Lookup**
```php
private function findPageById($folder, string $id): ?array {
    try {
        // Try root level: en/page-id.json
        $file = $folder->get($id . '.json');
        return ['file' => $file, 'data' => json_decode($file->getContent(), true)];
    } catch (NotFoundException $e) {
        try {
            // Try subfolder: en/page-id/page-id.json
            $subFolder = $folder->get($id);
            $file = $subFolder->get($id . '.json');
            return ['file' => $file, 'data' => json_decode($file->getContent(), true)];
        } catch (NotFoundException $e2) {
            return null;
        }
    }
}
```

### Folder Caching for Image Performance

After finding a page, the folder is cached for fast image access:

```php
// Cache folder location using both uniqueId and pageId
$this->pageFolderCache[$uniqueId] = $pageFolder;
$this->pageFolderCache[$id] = $pageFolder;
```

This cache is critical for image loading performance!

## Breadcrumb Generation

Breadcrumbs show the hierarchical path to the current page.

### Implementation

```php
public function getBreadcrumb(string $id): array {
    // Load the page to get its file location
    $page = $this->getPage($id);
    $file = $page['file'];

    // Extract path from file location
    // Example: /var/www/nextcloud/data/__groupfolders/1/files/en/news/product-updates/product-updates.json
    $fullPath = $file->getPath();

    // Parse path to extract components
    // Result: ['en', 'news', 'product-updates']
    preg_match('/\/files\/([^\/]+)\/(.+)\/[^\/]+\.json$/', $fullPath, $matches);
    $language = $matches[1];  // 'en'
    $relativePath = $matches[2];  // 'news/product-updates'

    // Build breadcrumb trail
    $breadcrumb = [
        ['id' => 'home', 'title' => 'Home']
    ];

    $pathParts = explode('/', $relativePath);
    $currentPath = '';

    foreach ($pathParts as $part) {
        $currentPath .= ($currentPath ? '/' : '') . $part;

        // Try to load the page at this path level
        try {
            $parentPage = $this->getPage($currentPath);
            $breadcrumb[] = [
                'id' => $parentPage['data']['uniqueId'],
                'title' => $parentPage['data']['title']
            ];
        } catch (\Exception $e) {
            // No page at this level, use folder name
            $breadcrumb[] = [
                'id' => $currentPath,
                'title' => ucfirst(str_replace('-', ' ', $part))
            ];
        }
    }

    return $breadcrumb;
}
```

### Example Breadcrumb

For page at `/en/news/product-updates/product-updates.json`:

```javascript
[
    { id: 'home', title: 'Home' },
    { id: 'page-abc-123...', title: 'News' },
    { id: 'page-def-456...', title: 'Product Updates' }
]
```

## Image Loading Implementation

Images are loaded via `/api/pages/{pageId}/images/{filename}`.

### The Same Bug Existed Here!

**The Problem:**
The `getImage()` method had the exact same sanitization bug as `getPage()`.

```php
// WRONG - Old implementation
public function getImage(string $pageId, string $filename) {
    $pageId = $this->sanitizeId($pageId);  // Strips 'page-' prefix

    // Cache lookup fails because key has 'page-' prefix
    if (isset($this->pageFolderCache[$pageId])) {
        // Never hits for uniqueId-based requests
    }

    // Search fails because uniqueId in JSON has 'page-' prefix
    $imagesFolder = $this->findImagesFolderForPage($folder, $pageId);
}
```

**The Solution (v0.4.27):**

```php
// CORRECT - Fixed implementation
public function getImage(string $pageId, string $filename) {
    // Save original BEFORE sanitization
    $originalPageId = $pageId;
    $filename = basename($filename);  // Security: prevent directory traversal

    $languageFolder = $this->getLanguageFolder();

    try {
        // Handle home page with original pageId
        if ($originalPageId === 'home' ||
            $originalPageId === '2e8f694e-147e-4793-8949-4732e679ae6b' ||
            $originalPageId === 'page-2e8f694e-147e-4793-8949-4732e679ae6b') {

            $imagesFolder = $languageFolder->get('images');
            $file = $imagesFolder->get($filename);
            return $this->createImageResponse($file);
        }

        // Try cache with BOTH original and sanitized IDs
        $imagesFolder = null;
        $pageId = $this->sanitizeId($originalPageId);

        if (isset($this->pageFolderCache[$originalPageId])) {
            // Cache hit with original ID (page-abc-123...)
            $pageFolder = $this->pageFolderCache[$originalPageId];
            $imagesFolder = $pageFolder->get('images');
        } else if (isset($this->pageFolderCache[$pageId])) {
            // Cache hit with sanitized ID (abc-123...)
            $pageFolder = $this->pageFolderCache[$pageId];
            $imagesFolder = $pageFolder->get('images');
        }

        // If cache miss, search using ORIGINAL pageId
        if ($imagesFolder === null) {
            $imagesFolder = $this->findImagesFolderForPage(
                $languageFolder,
                $originalPageId  // Use ORIGINAL with 'page-' prefix
            );
        }

        if ($imagesFolder === null) {
            throw new \Exception('Images folder not found for page: ' . $originalPageId);
        }

        $file = $imagesFolder->get($filename);
        return $this->createImageResponse($file);
    } catch (\Exception $e) {
        throw $e;
    }
}
```

### Recursive Image Folder Search

```php
private function findImagesFolderForPage($folder, string $uniqueId): ?\OCP\Files\Folder {
    foreach ($folder->getDirectoryListing() as $item) {
        if ($item->getType() === FileInfo::TYPE_FOLDER) {
            $itemName = $item->getName();

            // Skip special folders to avoid infinite loops
            if ($itemName === 'images' || strpos($itemName, '📷') === 0 ||
                $itemName === '.nomedia') {
                continue;
            }

            // Recurse into subfolder first (depth-first search)
            $result = $this->findImagesFolderForPage($item, $uniqueId);
            if ($result !== null) {
                return $result;
            }

            // Check if THIS folder contains the page
            try {
                // Check for images subfolder
                $imagesFolder = $item->get('images');
                if ($imagesFolder->getType() !== FileInfo::TYPE_FOLDER) {
                    continue;
                }

                // Scan all JSON files in this folder
                foreach ($item->getDirectoryListing() as $subItem) {
                    if ($subItem->getType() === FileInfo::TYPE_FILE &&
                        substr($subItem->getName(), -5) === '.json' &&
                        $subItem->getName() !== 'navigation.json' &&
                        $subItem->getName() !== 'footer.json') {

                        $content = $subItem->getContent();
                        $data = json_decode($content, true);

                        // Match against uniqueId field
                        // This requires $uniqueId to have 'page-' prefix!
                        if ($data && isset($data['uniqueId']) &&
                            $data['uniqueId'] === $uniqueId) {

                            // Found matching page, return its images folder
                            return $imagesFolder;
                        }
                    }
                }
            } catch (NotFoundException $e) {
                // No images folder in this folder
                continue;
            }
        }
    }

    return null;
}
```

### Why the Cache is Critical

**With Cache (Fast Path):**
```
1. User loads page -> getPage() caches folder location
2. Browser requests image -> getImage() uses cached folder
3. Direct file access to images/photo.jpg
Total: 2 filesystem operations
```

**Without Cache (Slow Path):**
```
1. Browser requests image directly
2. getImage() must recursively search entire filesystem
3. Scan /en/ folder
4. Scan /en/news/ folder
5. Scan /en/news/product-updates/ folder
6. Read product-updates.json
7. Compare uniqueId
8. Access images/photo.jpg
Total: 7+ filesystem operations
```

## Common Issues and Solutions

### Issue 1: Pages Return 404

**Symptom:** `GET /api/pages/page-abc-123... 404 Not Found`

**Root Cause:** sanitizeId() called before uniqueId check

**Fix:** Preserve originalId before sanitization (v0.4.27)

### Issue 2: Images Return 404

**Symptom:** Page loads but images show broken

**Root Cause:** Same sanitization issue in getImage()

**Fix:** Use originalPageId for cache lookup and search (v0.4.27)

### Issue 3: Home Page Works, Others Don't

**Explanation:** Home page has special handling that bypasses uniqueId lookup:

```php
if ($originalPageId === 'home' ||
    $originalPageId === 'page-2e8f694e-147e-4793-8949-4732e679ae6b') {
    // Direct access to /en/images/
    $imagesFolder = $languageFolder->get('images');
}
```

Other pages require correct uniqueId matching with 'page-' prefix intact.

### Issue 4: Slow Image Loading

**Symptom:** Images take several seconds to load

**Root Cause:** Cache miss on every request

**Fix:** Ensure getPage() is called before image requests to warm cache

## Performance Optimization

### Best Practices

1. **Always load page before images**
   ```javascript
   // Frontend best practice
   await this.loadPage(pageId);  // Warms cache
   // Now images load fast from cache
   ```

2. **Parallel image loading**
   ```javascript
   // Browser loads all images concurrently
   const imagePromises = images.map(img =>
       axios.get(`/api/pages/${pageId}/images/${img.src}`)
   );
   await Promise.all(imagePromises);
   ```

3. **Browser caching**
   ```php
   // 1 year cache for images
   $response->addHeader('Cache-Control', 'public, max-age=31536000');
   ```

## Testing and Debugging

### Check Server Logs

```bash
ssh user@server 'cd /var/www/nextcloud && \
    sudo -u www-data php occ log:tail | grep IntraVox'
```

**Expected Log Output:**

```
IntraVox: Getting page - originalId: page-6f0c9h18-g7e5-4h41-1d0h-2g6f5e7g9c0h
IntraVox: Searching by uniqueId - uniqueId: page-6f0c9h18-g7e5-4h41-1d0h-2g6f5e7g9c0h
IntraVox: Found by uniqueId - file: product-updates.json
IntraVox getImage: START - originalPageId: page-6f0c9h18-g7e5-4h41-1d0h-2g6f5e7g9c0h
IntraVox getImage: CACHE HIT (original) - pageId: page-6f0c9h18-g7e5-4h41-1d0h-2g6f5e7g9c0h
IntraVox: Images folder found in cache
```

**Bad Log Output (Bug Present):**

```
IntraVox: Getting page - originalId: page-6f0c9h18-g7e5-4h41-1d0h-2g6f5e7g9c0h
IntraVox: Trying legacy id lookup - id: 6f0c9h18-g7e5-4h41-1d0h-2g6f5e7g9c0h
IntraVox: Not found by uniqueId  // BUG: Never searched because prefix was stripped!
IntraVox getImage: CACHE MISS
IntraVox: Images folder NOT FOUND  // BUG: Can't match uniqueId without prefix!
```

### Test Commands

```bash
# Test page loading with uniqueId
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost/apps/intravox/api/pages/page-6f0c9h18-g7e5-4h41-1d0h-2g6f5e7g9c0h

# Test image loading
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost/apps/intravox/api/pages/page-6f0c9h18-g7e5-4h41-1d0h-2g6f5e7g9c0h/images/banner.jpg \
  -o /tmp/test.jpg && file /tmp/test.jpg
```

## Version History

- **v0.4.27** (2025-01-22): Fixed critical bug in page and image loading by preserving original ID before sanitization
- **v0.2.8** (2025-01-11): Introduced uniqueId-based URLs for permanent page identification
- **v0.2.5** (2025-01-10): Changed to human-readable page IDs from technical IDs

## Related Documentation

- [pages.md](pages.md) - General page system documentation
- [CHANGELOG.md](CHANGELOG.md) - Complete version history
- [lib/Service/PageService.php](lib/Service/PageService.php) - Backend implementation
