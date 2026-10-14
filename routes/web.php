<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Serve public disk files (storage/app/public via /storage/... URL)
$router->get('/storage/{path:.*}', function ($path) {
    $path = str_replace(['..', '\\'], ['', '/'], (string) $path);
    $path = ltrim($path, '/');

    if ($path === '') {
        return response('File not found', 404);
    }

    $basePath = storage_path('app/public');
    $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

    if (!is_file($fullPath)) {
        return response('File not found', 404);
    }

    $realBase = realpath($basePath);
    $realFull = realpath($fullPath);

    if (!$realBase || !$realFull || !str_starts_with($realFull, $realBase)) {
        return response('File not found', 404);
    }

    $mimeType = mime_content_type($realFull) ?: 'application/octet-stream';

    return response()->file($realFull, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
});

$router->get('/exports/brands/{filename}', function ($filename) {
    $filename = basename(str_replace(['..', '\\'], ['', '/'], (string) $filename));

    if (!preg_match('/^brand_import_failed_[A-Za-z0-9._-]+\.xlsx$/', $filename)) {
        return response('File not found', 404);
    }

    $basePath = base_path('writable/exports/brands');
    $fullPath = $basePath . DIRECTORY_SEPARATOR . $filename;

    if (!is_file($fullPath)) {
        return response('File not found', 404);
    }

    $realBase = realpath($basePath);
    $realFull = realpath($fullPath);

    if (!$realBase || !$realFull || !str_starts_with($realFull, $realBase)) {
        return response('File not found', 404);
    }

    return response()->download($realFull, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ]);
});