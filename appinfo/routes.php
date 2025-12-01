<?php
declare(strict_types=1);

return [
    'routes' => [
        // API routes MUST come first to avoid conflicts with language routes
        ['name' => 'api#listPages', 'url' => '/api/pages', 'verb' => 'GET'],
        ['name' => 'api#getPageTree', 'url' => '/api/pages/tree', 'verb' => 'GET'],
        ['name' => 'api#getPage', 'url' => '/api/pages/{id}', 'verb' => 'GET'],
        ['name' => 'api#createPage', 'url' => '/api/pages', 'verb' => 'POST'],
        ['name' => 'api#updatePage', 'url' => '/api/pages/{id}', 'verb' => 'PUT'],
        ['name' => 'api#deletePage', 'url' => '/api/pages/{id}', 'verb' => 'DELETE'],
        ['name' => 'api#uploadImage', 'url' => '/api/pages/{pageId}/images', 'verb' => 'POST'],
        ['name' => 'api#getImage', 'url' => '/api/pages/{pageId}/images/{filename}', 'verb' => 'GET'],
        ['name' => 'api#getPageVersions', 'url' => '/api/pages/{pageId}/versions', 'verb' => 'GET'],
        ['name' => 'api#restorePageVersion', 'url' => '/api/pages/{pageId}/versions/{timestamp}', 'verb' => 'POST'],
        ['name' => 'api#updateVersionLabel', 'url' => '/api/pages/{pageId}/versions/{timestamp}/label', 'verb' => 'PUT'],
        ['name' => 'api#getVersionContent', 'url' => '/api/pages/{pageId}/versions/{timestamp}/content', 'verb' => 'GET'],
        ['name' => 'api#getCurrentPageContent', 'url' => '/api/pages/{pageId}/content', 'verb' => 'GET'],
        ['name' => 'api#getPageMetadata', 'url' => '/api/pages/{pageId}/metadata', 'verb' => 'GET'],
        ['name' => 'api#updatePageMetadata', 'url' => '/api/pages/{pageId}/metadata', 'verb' => 'PUT'],
        ['name' => 'api#checkPageCacheStatus', 'url' => '/api/page/{pageId}/cache-status', 'verb' => 'GET'],
        ['name' => 'api#searchPages', 'url' => '/api/search', 'verb' => 'GET'],
        ['name' => 'api#getBreadcrumb', 'url' => '/api/pages/{id}/breadcrumb', 'verb' => 'GET'],
        ['name' => 'api#getMetaVoxStatus', 'url' => '/api/metavox/status', 'verb' => 'GET'],
        ['name' => 'api#getPermissions', 'url' => '/api/permissions', 'verb' => 'GET'],
        ['name' => 'navigation#get', 'url' => '/api/navigation', 'verb' => 'GET'],
        ['name' => 'navigation#save', 'url' => '/api/navigation', 'verb' => 'POST'],
        ['name' => 'footer#get', 'url' => '/api/footer', 'verb' => 'GET'],
        ['name' => 'footer#save', 'url' => '/api/footer', 'verb' => 'POST'],

        // Demo data routes
        ['name' => 'demoData#getStatus', 'url' => '/api/demo-data/status', 'verb' => 'GET'],
        ['name' => 'demoData#getLanguages', 'url' => '/api/demo-data/languages', 'verb' => 'GET'],
        ['name' => 'demoData#importDemoData', 'url' => '/api/demo-data/import', 'verb' => 'POST'],

        // Page routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#show', 'url' => '/page/{id}', 'verb' => 'GET'],
        ['name' => 'page#showByUniqueId', 'url' => '/p/{uniqueId}', 'verb' => 'GET'],
    ],
];
