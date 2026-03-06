<?php

use Daun\StatamicAssetThumbnails\Drivers\CloudConvertDriver;
use Daun\StatamicAssetThumbnails\Drivers\ConversionResult;
use Daun\StatamicAssetThumbnails\Drivers\ConversionStatus;
use Daun\StatamicAssetThumbnails\Drivers\DriverInterface;
use Daun\StatamicAssetThumbnails\Drivers\NullDriver;
use Daun\StatamicAssetThumbnails\Drivers\TransloaditDriver;
use Statamic\Assets\Asset;
use Tests\Support\FakeDriver;

/*
|--------------------------------------------------------------------------
| Driver Interface Contract Tests
|--------------------------------------------------------------------------
|
| These tests validate that all DriverInterface implementations fulfill
| the contract correctly. Each driver is tested against the same set of
| expectations using Pest datasets.
|
*/

function createDriver(string $class): DriverInterface
{
    return match ($class) {
        FakeDriver::class => new FakeDriver,
        NullDriver::class => new NullDriver,
        CloudConvertDriver::class => new CloudConvertDriver(['api_key' => 'test-key']),
        TransloaditDriver::class => new TransloaditDriver(['auth_key' => 'test-key', 'auth_secret' => 'test-secret']),
        default => throw new \RuntimeException("Unknown driver class: {$class}"),
    };
}

/**
 * Create a mock asset that stubs extension-related methods.
 * This avoids needing real files on disk for supports() checks.
 */
function mockAssetWithExtension(string $extension): Asset
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

dataset('drivers', [
    'FakeDriver' => FakeDriver::class,
    'NullDriver' => NullDriver::class,
    'CloudConvertDriver' => CloudConvertDriver::class,
    'TransloaditDriver' => TransloaditDriver::class,
]);

test('implements DriverInterface', function (string $driverClass) {
    $driver = createDriver($driverClass);

    expect($driver)->toBeInstanceOf(DriverInterface::class);
})->with('drivers');

test('supports() returns a boolean', function (string $driverClass) {
    $driver = createDriver($driverClass);
    $asset = mockAssetWithExtension('mp4');

    expect($driver->supports($asset))->toBeBool();
})->with('drivers');

test('supports() returns false for unsupported extensions', function (string $driverClass) {
    $driver = createDriver($driverClass);

    // These extensions should not be supported by any driver
    $unsupportedExtensions = ['xyz', 'unknown', 'zzz'];

    foreach ($unsupportedExtensions as $ext) {
        $asset = mockAssetWithExtension($ext);
        expect($driver->supports($asset))->toBeFalse("Driver {$driverClass} should not support .{$ext}");
    }
})->with('drivers');

test('FakeDriver and CloudConvertDriver support common extensions', function (string $driverClass) {
    if (in_array($driverClass, [NullDriver::class])) {
        $this->markTestSkipped('NullDriver supports no extensions by design');
    }

    $driver = createDriver($driverClass);

    // Common extensions that FakeDriver and both real drivers should support
    $commonExtensions = ['mp4', 'pdf', 'psd', 'heic', 'mov'];

    foreach ($commonExtensions as $ext) {
        $asset = mockAssetWithExtension($ext);
        expect($driver->supports($asset))->toBeTrue("Driver {$driverClass} should support .{$ext}");
    }
})->with('drivers');

test('NullDriver supports no extensions', function () {
    $driver = new NullDriver;

    $extensions = ['mp4', 'pdf', 'psd', 'heic', 'mov', 'jpg', 'png'];

    foreach ($extensions as $ext) {
        $asset = mockAssetWithExtension($ext);
        expect($driver->supports($asset))->toBeFalse("NullDriver should not support .{$ext}");
    }
});

test('FakeDriver records generated assets', function () {
    $driver = new FakeDriver;
    $asset = $this->makeEmptyAsset('test.mp4');

    $driver->assertNothingGenerated();

    $driver->generate($asset);

    $driver->assertGenerated($asset);
    $driver->assertGeneratedCount(1);
});

test('FakeDriver can be reset', function () {
    $driver = new FakeDriver;
    $asset = $this->makeEmptyAsset('test.mp4');

    $driver->generate($asset);
    $driver->assertGeneratedCount(1);

    $driver->reset();
    $driver->assertNothingGenerated();
});

test('FakeDriver can replace the bound driver', function () {
    $fake = new FakeDriver;
    $this->app->instance(DriverInterface::class, $fake);

    $resolved = $this->app->make(DriverInterface::class);

    expect($resolved)->toBe($fake);
    expect($resolved)->toBeInstanceOf(DriverInterface::class);
});

/*
|--------------------------------------------------------------------------
| createConversion() Contract Tests
|--------------------------------------------------------------------------
*/

test('createConversion() returns a string or null', function (string $driverClass) {
    $driver = createDriver($driverClass);
    $asset = mockAssetWithExtension('mp4');

    // NullDriver returns null; FakeDriver returns a configurable string
    $result = $driver->createConversion($asset);
    expect($result)->toBeIn([null, is_string($result) ? $result : null]);
})->with([
    'FakeDriver' => FakeDriver::class,
    'NullDriver' => NullDriver::class,
]);

test('NullDriver createConversion() returns null', function () {
    $driver = new NullDriver;
    $asset = mockAssetWithExtension('mp4');

    expect($driver->createConversion($asset))->toBeNull();
});

test('FakeDriver createConversion() returns configurable ID', function () {
    $driver = new FakeDriver;
    $asset = mockAssetWithExtension('mp4');

    expect($driver->createConversion($asset))->toBe('fake-conversion-id');

    $driver->fakeConversionId = 'custom-id';
    expect($driver->createConversion($asset))->toBe('custom-id');

    $driver->fakeConversionId = null;
    expect($driver->createConversion($asset))->toBeNull();
});

test('FakeDriver records created conversions', function () {
    $driver = new FakeDriver;
    $asset = $this->makeEmptyAsset('test.mp4');

    $driver->createConversion($asset);

    $driver->assertConversionCreated($asset);
});

/*
|--------------------------------------------------------------------------
| fetchResult() Contract Tests
|--------------------------------------------------------------------------
*/

test('NullDriver fetchResult() returns Failed', function () {
    $driver = new NullDriver;

    expect($driver->fetchResult('any-id'))->toBe(ConversionStatus::Failed);
});

test('FakeDriver fetchResult() returns configurable result', function () {
    $driver = new FakeDriver;

    // Default: Pending (still processing)
    expect($driver->fetchResult('conv-1'))->toBe(ConversionStatus::Pending);

    // Set to Failed
    $driver->fakeResult = ConversionStatus::Failed;
    expect($driver->fetchResult('conv-2'))->toBe(ConversionStatus::Failed);

    // Set to ConversionResult (succeeded)
    $driver->fakeResult = new ConversionResult('https://example.com/thumb.webp', 'thumb.webp');
    $result = $driver->fetchResult('conv-3');
    expect($result)->toBeInstanceOf(ConversionResult::class);
    expect($result->url)->toBe('https://example.com/thumb.webp');
    expect($result->filename)->toBe('thumb.webp');
});

test('FakeDriver records fetched conversions', function () {
    $driver = new FakeDriver;

    $driver->fetchResult('conv-123');

    $driver->assertResultFetched('conv-123');
});
