<?php

use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| ThumbnailService::download() Tests
|--------------------------------------------------------------------------
|
| Tests for the download() method: timeout behavior and size limits.

*/

beforeEach(function () {
    if (! config('app.key')) {
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    }
});

test('rejects downloads exceeding maximum size', function () {
    // Create a 15MB fake body (well above a reasonable thumbnail size)
    $largeBody = str_repeat('x', 15 * 1024 * 1024);

    Http::fake([
        'https://example.com/large-thumb.webp' => Http::response($largeBody, 200),
    ]);

    $service = app(ThumbnailService::class);
    $result = $service->download('https://example.com/large-thumb.webp');

    expect($result)->toBeNull();
});

test('downloads that fail with HTTP error return null', function () {
    Http::fake([
        'https://example.com/thumb.webp' => Http::response('', 500),
    ]);

    $service = app(ThumbnailService::class);
    $result = $service->download('https://example.com/thumb.webp');

    expect($result)->toBeNull();
});

test('downloads that throw connection exception return null', function () {
    Http::fake([
        'https://example.com/thumb.webp' => fn () => throw new ConnectionException('timeout'),
    ]);

    $service = app(ThumbnailService::class);
    $result = $service->download('https://example.com/thumb.webp');

    expect($result)->toBeNull();
});
