<?php

use Daun\StatamicAssetThumbnails\Drivers\TransloaditDriver;
use Daun\StatamicAssetThumbnails\Jobs\CreateConversionJob;
use Illuminate\Support\Facades\Bus;
use Statamic\Assets\Asset;

/*
|--------------------------------------------------------------------------
| Transloadit Driver Tests
|--------------------------------------------------------------------------
|
| Tests for the TransloaditDriver class: extension support, job
| dispatching, and configuration.
|
*/

/**
 * Create a mock asset that stubs extension-related methods.
 */
function tlMockAsset(string $extension): Asset
{
    $asset = Mockery::mock(Asset::class)->makePartial();
    $asset->shouldReceive('extension')->andReturn($extension);
    $asset->shouldReceive('extensionIsOneOf')->andReturnUsing(
        fn (array $exts) => in_array(strtolower($extension), $exts)
    );
    $asset->shouldReceive('guessedExtension')->andReturn($extension);
    $asset->shouldReceive('guessedExtensionIsOneOf')->andReturnUsing(
        fn (array $exts) => in_array(strtolower($extension), $exts)
    );

    return $asset;
}

beforeEach(function () {
    $this->driver = new TransloaditDriver([
        'auth_key' => 'test-key',
        'auth_secret' => 'test-secret',
    ]);
});

/*
|--------------------------------------------------------------------------
| Extension Support
|--------------------------------------------------------------------------
*/

test('supports image formats', function (string $extension) {
    $asset = tlMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['bmp', 'tif', 'tiff', 'psd', 'psb', 'eps', 'ai']);

test('supports raw photo formats', function (string $extension) {
    $asset = tlMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['raw', 'heic', 'heif', 'nef', 'nrw', 'cr2', 'cr3', 'crw', 'orf', 'dng', 'arw', 'rw2', 'raf', 'dcm']);

test('supports video formats', function (string $extension) {
    $asset = tlMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['mp4', 'h264', 'm4v', 'mov', 'avi', 'ogv', 'mkv', 'webm', 'wmv']);

test('supports audio formats', function (string $extension) {
    $asset = tlMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['mp3', 'aac', 'aif', 'aiff', 'm4a', 'ogg', 'opus', 'flac', 'wav']);

test('supports document formats', function (string $extension) {
    $asset = tlMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['txt', 'rtf', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);

test('supports icon formats', function (string $extension) {
    $asset = tlMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['ico', 'cur']);

test('does not support native image formats handled by Statamic', function (string $extension) {
    $asset = tlMockAsset($extension);
    expect($this->driver->supports($asset))->toBeFalse();
})->with(['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg']);

test('does not support unknown formats', function (string $extension) {
    $asset = tlMockAsset($extension);
    expect($this->driver->supports($asset))->toBeFalse();
})->with(['xyz', 'unknown', 'zzz', 'abc']);

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

test('exposes the Transloadit API client', function () {
    expect($this->driver->api())->toBeInstanceOf(\transloadit\Transloadit::class);
});

test('accepts auth_key and auth_secret configuration', function () {
    $driver = new TransloaditDriver([
        'auth_key' => 'my-key',
        'auth_secret' => 'my-secret',
    ]);
    expect($driver->api())->toBeInstanceOf(\transloadit\Transloadit::class);
});

/*
|--------------------------------------------------------------------------
| Job Dispatching
|--------------------------------------------------------------------------
*/

test('dispatches CreateConversionJob', function () {
    Bus::fake([CreateConversionJob::class]);

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'test.pdf');

    $this->driver->generate($asset);

    Bus::assertDispatched(CreateConversionJob::class);
});
