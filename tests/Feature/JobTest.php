<?php

use Daun\StatamicAssetThumbnails\Drivers\ConversionResult;
use Daun\StatamicAssetThumbnails\Drivers\DriverInterface;
use Daun\StatamicAssetThumbnails\Jobs\CreateConversionJob;
use Daun\StatamicAssetThumbnails\Jobs\FetchConversionJob;
use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeDriver;

/*
|--------------------------------------------------------------------------
| Unified Job Tests
|--------------------------------------------------------------------------
|
| These tests exercise CreateConversionJob and FetchConversionJob using
| the FakeDriver. Since jobs are now driver-agnostic, we test them
| independently of any external API — using FakeDriver for conversion
| logic and Http::fake() for download simulation.
|
*/

beforeEach(function () {
    $this->fakeDriver = new FakeDriver;
    $this->app->instance(DriverInterface::class, $this->fakeDriver);

    // Clear thumbnail cache to prevent cross-test contamination
    $service = app(ThumbnailService::class);
    $disk = $service->disk();
    foreach ($disk->directories() as $dir) {
        $disk->deleteDirectory($dir);
    }
});

/*
|--------------------------------------------------------------------------
| CreateConversionJob
|--------------------------------------------------------------------------
*/

test('CreateConversionJob calls driver createConversion', function () {
    Bus::fake([FetchConversionJob::class]);

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    $job = new CreateConversionJob($asset);
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    $this->fakeDriver->assertConversionCreated($asset);
});

test('CreateConversionJob dispatches FetchConversionJob on success', function () {
    Bus::fake([FetchConversionJob::class]);

    $this->fakeDriver->fakeConversionId = 'conv-abc-123';

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    $job = new CreateConversionJob($asset);
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    Bus::assertDispatched(FetchConversionJob::class);
});

test('CreateConversionJob does not dispatch FetchConversionJob when conversion fails', function () {
    Bus::fake([FetchConversionJob::class]);

    $this->fakeDriver->fakeConversionId = null;

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    $job = new CreateConversionJob($asset);
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    Bus::assertNotDispatched(FetchConversionJob::class);
});

test('CreateConversionJob skips if thumbnail already exists', function () {
    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    // Pre-populate a cached thumbnail
    app(ThumbnailService::class)->put($asset, 'existing-data', 'thumb.webp');

    $job = new CreateConversionJob($asset);
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    // Driver should not have been called
    expect($this->fakeDriver->createdConversions)->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| FetchConversionJob
|--------------------------------------------------------------------------
*/

test('FetchConversionJob calls driver fetchResult', function () {
    Bus::fake([FetchConversionJob::class]);

    $this->fakeDriver->fakeResult = null; // still processing

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    $job = new FetchConversionJob($asset, 'conv-123');
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    $this->fakeDriver->assertResultFetched('conv-123');
});

test('FetchConversionJob retries when result is null', function () {
    Bus::fake([FetchConversionJob::class]);

    $this->fakeDriver->fakeResult = null; // still processing

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    $job = new FetchConversionJob($asset, 'conv-123', attempt: 1);
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    // Should dispatch a retry
    Bus::assertDispatched(FetchConversionJob::class);
});

test('FetchConversionJob does not retry when result is false (failed)', function () {
    Bus::fake([FetchConversionJob::class]);

    $this->fakeDriver->fakeResult = false; // failed

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    $job = new FetchConversionJob($asset, 'conv-123', attempt: 1);
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    Bus::assertNotDispatched(FetchConversionJob::class);
});

test('FetchConversionJob downloads and saves thumbnail on success', function () {
    $this->fakeDriver->fakeResult = new ConversionResult(
        'https://example.com/thumb.webp',
        'thumb.webp',
    );

    Http::fake([
        'https://example.com/thumb.webp' => Http::response('fake-thumbnail-data', 200),
    ]);

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    $job = new FetchConversionJob($asset, 'conv-123');
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    $service = app(ThumbnailService::class);
    expect($service->exists($asset))->toBeTrue();
    expect($service->read($asset))->toBe('fake-thumbnail-data');
});

test('FetchConversionJob does not save when download fails', function () {
    $this->fakeDriver->fakeResult = new ConversionResult(
        'https://example.com/thumb.webp',
        'thumb.webp',
    );

    Http::fake([
        'https://example.com/thumb.webp' => Http::response('', 500),
    ]);

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    $job = new FetchConversionJob($asset, 'conv-123');
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    $service = app(ThumbnailService::class);
    expect($service->exists($asset))->toBeFalse();
});

test('FetchConversionJob skips if thumbnail already exists', function () {
    Bus::fake([FetchConversionJob::class]);

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    // Pre-populate a cached thumbnail
    app(ThumbnailService::class)->put($asset, 'existing-data', 'thumb.webp');

    $job = new FetchConversionJob($asset, 'conv-123');
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    // Driver should not have been called
    expect($this->fakeDriver->fetchedConversions)->toBeEmpty();
});

test('FetchConversionJob stops after max attempts', function () {
    Bus::fake([FetchConversionJob::class]);

    $this->fakeDriver->fakeResult = null; // still processing

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');

    // Attempt 6 exceeds maxAttempts of 5
    $job = new FetchConversionJob($asset, 'conv-123', attempt: 6);
    $job->handle(app(ThumbnailService::class), $this->fakeDriver);

    // Should not call the driver at all
    expect($this->fakeDriver->fetchedConversions)->toBeEmpty();
    Bus::assertNotDispatched(FetchConversionJob::class);
});
