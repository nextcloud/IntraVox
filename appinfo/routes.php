<?php
declare(strict_types=1);

return [
    'routes' => [
        // Page routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#show', 'url' => '/page/{id}', 'verb' => 'GET'],

        // API routes for pages
        ['name' => 'api#listPages', 'url' => '/api/pages', 'verb' => 'GET'],
        ['name' => 'api#getPage', 'url' => '/api/pages/{id}', 'verb' => 'GET'],
        ['name' => 'api#createPage', 'url' => '/api/pages', 'verb' => 'POST'],
        ['name' => 'api#updatePage', 'url' => '/api/pages/{id}', 'verb' => 'PUT'],
        ['name' => 'api#deletePage', 'url' => '/api/pages/{id}', 'verb' => 'DELETE'],

        // API routes for widgets
        ['name' => 'api#uploadImage', 'url' => '/api/pages/{pageId}/images', 'verb' => 'POST'],
        ['name' => 'api#getImage', 'url' => '/api/pages/{pageId}/images/{filename}', 'verb' => 'GET'],

        // API routes for navigation
        ['name' => 'navigation#get', 'url' => '/api/navigation', 'verb' => 'GET'],
        ['name' => 'navigation#save', 'url' => '/api/navigation', 'verb' => 'POST'],

        // API routes for footer
        ['name' => 'footer#get', 'url' => '/api/footer', 'verb' => 'GET'],
        ['name' => 'footer#save', 'url' => '/api/footer', 'verb' => 'POST'],
    ],
];
