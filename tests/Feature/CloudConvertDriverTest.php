<?php

use Daun\StatamicAssetThumbnails\Drivers\CloudConvertDriver;
use Daun\StatamicAssetThumbnails\Jobs\CreateConversionJob;
use Illuminate\Support\Facades\Bus;
use Statamic\Assets\Asset;

/*
|--------------------------------------------------------------------------
| CloudConvert Driver Tests
|--------------------------------------------------------------------------
|
| Tests for the CloudConvertDriver class: extension support, job
| dispatching, and configuration.
|
*/

/**
 * Create a mock asset that stubs extension-related methods.
 */
function ccMockAsset(string $extension): Asset
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
    $this->driver = new CloudConvertDriver(['api_key' => 'test-key']);
});

/*
|--------------------------------------------------------------------------
| Extension Support
|--------------------------------------------------------------------------
*/

test('supports image formats', function (string $extension) {
    $asset = ccMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['bmp', 'tif', 'tiff', 'psd', 'psb', 'eps']);

test('supports raw photo formats', function (string $extension) {
    $asset = ccMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['raw', 'heic', 'heif', 'nef', 'cr2', 'cr3', 'crw', 'orf', 'dng', 'arw', 'rw2', 'raf']);

test('supports video formats', function (string $extension) {
    $asset = ccMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['mp4', 'm4v', 'mov', 'avi', 'ogv', 'mkv', 'webm', 'wmv']);

test('supports document formats', function (string $extension) {
    $asset = ccMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['txt', 'rtf', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);

test('supports icon formats', function (string $extension) {
    $asset = ccMockAsset($extension);
    expect($this->driver->supports($asset))->toBeTrue();
})->with(['ico']);

test('does not support native image formats handled by Statamic', function (string $extension) {
    $asset = ccMockAsset($extension);
    expect($this->driver->supports($asset))->toBeFalse();
})->with(['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg']);

test('does not support unknown formats', function (string $extension) {
    $asset = ccMockAsset($extension);
    expect($this->driver->supports($asset))->toBeFalse();
})->with(['xyz', 'unknown', 'zzz', 'abc']);

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

test('exposes the CloudConvert API client', function () {
    expect($this->driver->api())->toBeInstanceOf(\CloudConvert\CloudConvert::class);
});

test('accepts api_key configuration', function () {
    $driver = new CloudConvertDriver(['api_key' => 'my-custom-key']);
    expect($driver->api())->toBeInstanceOf(\CloudConvert\CloudConvert::class);
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
