<?php

use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| ThumbnailService::download() Tests
|--------------------------------------------------------------------------
|
| Tests for the download() method: timeout behavior and size limits.
|
| BUG: The download() method currently uses Http::get($url) with no timeout
| and no size limit on the response body. This means a slow or malicious
| URL could block a queue worker indefinitely, or a large response could
| exhaust memory.
*/

beforeEach(function () {
    // Ensure an app key is set for the service to use
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

    // The current code has NO size check — it will accept the full 15MB body.
    // After the fix, this should return null for oversized downloads.
    expect($result)->toBeNull();
});

test('downloads that succeed with normal size return the body', function () {
    Http::fake([
        'https://example.com/thumb.webp' => Http::response('fake-thumbnail-data', 200),
    ]);

    $service = app(ThumbnailService::class);
    $result = $service->download('https://example.com/thumb.webp');

    expect($result)->toBe('fake-thumbnail-data');
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
        'https://example.com/thumb.webp' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'),
    ]);

    $service = app(ThumbnailService::class);
    $result = $service->download('https://example.com/thumb.webp');

    expect($result)->toBeNull();
});
