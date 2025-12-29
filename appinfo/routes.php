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
        ['name' => 'api#uploadMedia', 'url' => '/api/pages/{pageId}/media', 'verb' => 'POST'],
        ['name' => 'api#checkMediaDuplicate', 'url' => '/api/pages/{pageId}/media/check', 'verb' => 'POST'],
        ['name' => 'api#uploadMediaWithName', 'url' => '/api/pages/{pageId}/media/upload', 'verb' => 'POST'],
        ['name' => 'api#listMedia', 'url' => '/api/pages/{pageId}/media/list', 'verb' => 'GET'],
        ['name' => 'api#getMedia', 'url' => '/api/pages/{pageId}/media/{filename}', 'verb' => 'GET'],
        // Resources media routes (supports subfolders like backgrounds/file.svg)
        ['name' => 'api#getResourcesMediaWithFolder', 'url' => '/api/resources/media/{folder}/{filename}', 'verb' => 'GET'],
        ['name' => 'api#getResourcesMedia', 'url' => '/api/resources/media/{filename}', 'verb' => 'GET'],
        ['name' => 'api#getPageVersions', 'url' => '/api/pages/{pageId}/versions', 'verb' => 'GET'],
        ['name' => 'api#restorePageVersion', 'url' => '/api/pages/{pageId}/versions/{timestamp}', 'verb' => 'POST'],
        ['name' => 'api#updateVersionLabel', 'url' => '/api/pages/{pageId}/versions/{timestamp}/label', 'verb' => 'PUT'],
        ['name' => 'api#getVersionContent', 'url' => '/api/pages/{pageId}/versions/{timestamp}/content', 'verb' => 'GET'],
        ['name' => 'api#getCurrentPageContent', 'url' => '/api/pages/{pageId}/content', 'verb' => 'GET'],
        ['name' => 'api#getPageMetadata', 'url' => '/api/pages/{pageId}/metadata', 'verb' => 'GET'],
        ['name' => 'api#updatePageMetadata', 'url' => '/api/pages/{pageId}/metadata', 'verb' => 'PUT'],
        ['name' => 'api#checkPageCacheStatus', 'url' => '/api/page/{pageId}/cache-status', 'verb' => 'GET'],
        ['name' => 'api#searchPages', 'url' => '/api/search', 'verb' => 'GET'],
        ['name' => 'api#getNews', 'url' => '/api/news', 'verb' => 'GET'],
        ['name' => 'api#getBreadcrumb', 'url' => '/api/pages/{id}/breadcrumb', 'verb' => 'GET'],
        ['name' => 'api#getMetavoxStatus', 'url' => '/api/metavox/status', 'verb' => 'GET'],
        ['name' => 'api#getMetavoxFields', 'url' => '/api/metavox/fields', 'verb' => 'GET'],
        ['name' => 'api#getPermissions', 'url' => '/api/permissions', 'verb' => 'GET'],
        ['name' => 'navigation#get', 'url' => '/api/navigation', 'verb' => 'GET'],
        ['name' => 'navigation#save', 'url' => '/api/navigation', 'verb' => 'POST'],
        ['name' => 'footer#get', 'url' => '/api/footer', 'verb' => 'GET'],
        ['name' => 'footer#save', 'url' => '/api/footer', 'verb' => 'POST'],

        // Setup route
        ['name' => 'api#runSetup', 'url' => '/api/setup', 'verb' => 'POST'],

        // Demo data routes
        ['name' => 'demoData#getStatus', 'url' => '/api/demo-data/status', 'verb' => 'GET'],
        ['name' => 'demoData#getLanguages', 'url' => '/api/demo-data/languages', 'verb' => 'GET'],
        ['name' => 'demoData#importDemoData', 'url' => '/api/demo-data/import', 'verb' => 'POST'],

        // Settings routes
        ['name' => 'api#getVideoDomains', 'url' => '/api/settings/video-domains', 'verb' => 'GET'],
        ['name' => 'api#setVideoDomains', 'url' => '/api/settings/video-domains', 'verb' => 'POST'],
        ['name' => 'api#getUploadLimit', 'url' => '/api/settings/upload-limit', 'verb' => 'GET'],
        ['name' => 'api#getEngagementSettings', 'url' => '/api/settings/engagement', 'verb' => 'GET'],
        ['name' => 'api#setEngagementSettings', 'url' => '/api/settings/engagement', 'verb' => 'POST'],
        ['name' => 'api#getPublicationSettings', 'url' => '/api/settings/publication', 'verb' => 'GET'],
        ['name' => 'api#setPublicationSettings', 'url' => '/api/settings/publication', 'verb' => 'POST'],

        // Export routes
        ['name' => 'export#getExportableLanguages', 'url' => '/api/export/languages', 'verb' => 'GET'],
        ['name' => 'export#exportLanguage', 'url' => '/api/export/language/{language}', 'verb' => 'GET'],
        ['name' => 'export#exportLanguageZip', 'url' => '/api/export/language/{language}/zip', 'verb' => 'GET'],
        ['name' => 'export#exportPage', 'url' => '/api/export/page/{uniqueId}', 'verb' => 'GET'],

        // Import routes (file uploads handled by ApiController)
        ['name' => 'api#import_zip', 'url' => '/api/import/zip', 'verb' => 'POST'],
        ['name' => 'api#import_confluence_html', 'url' => '/api/import/confluence/html', 'verb' => 'POST'],

        // Comments API routes
        ['name' => 'comment#getComments', 'url' => '/api/pages/{pageId}/comments', 'verb' => 'GET'],
        ['name' => 'comment#createComment', 'url' => '/api/pages/{pageId}/comments', 'verb' => 'POST'],
        ['name' => 'comment#updateComment', 'url' => '/api/comments/{commentId}', 'verb' => 'PUT'],
        ['name' => 'comment#deleteComment', 'url' => '/api/comments/{commentId}', 'verb' => 'DELETE'],

        // Page reactions API routes
        ['name' => 'comment#getPageReactions', 'url' => '/api/pages/{pageId}/reactions', 'verb' => 'GET'],
        ['name' => 'comment#addPageReaction', 'url' => '/api/pages/{pageId}/reactions/{emoji}', 'verb' => 'POST'],
        ['name' => 'comment#removePageReaction', 'url' => '/api/pages/{pageId}/reactions/{emoji}', 'verb' => 'DELETE'],

        // Comment reactions API routes
        ['name' => 'comment#getCommentReactions', 'url' => '/api/comments/{commentId}/reactions', 'verb' => 'GET'],
        ['name' => 'comment#addCommentReaction', 'url' => '/api/comments/{commentId}/reactions/{emoji}', 'verb' => 'POST'],
        ['name' => 'comment#removeCommentReaction', 'url' => '/api/comments/{commentId}/reactions/{emoji}', 'verb' => 'DELETE'],

        // Page routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#show', 'url' => '/page/{id}', 'verb' => 'GET'],
        ['name' => 'page#showByUniqueId', 'url' => '/p/{uniqueId}', 'verb' => 'GET'],
    ],
];
