<?php

use Daun\StatamicAssetThumbnails\Drivers\CloudConvertDriver;
use Tests\Concerns\FakesCloudConvert;
use Tests\Support\CloudConvertResponseFactory;

/*
|--------------------------------------------------------------------------
| CloudConvert createConversion() Missing File Tests
|--------------------------------------------------------------------------
|
| BUG: createConversion() calls fopen($asset->resolvedPath(), 'r')
| without checking if the file still exists. If the asset file was
| deleted between ThumbnailService::canGenerate() (which checks
| file_exists) and the driver call, fopen() returns false and the
| SDK may handle the invalid resource unpredictably.
|
| FIX: Add a file_exists() guard and return null early.
*/

uses(FakesCloudConvert::class);

beforeEach(function () {
    $this->setUpCloudConvertFake();

    config(['statamic.asset-thumbnails.driver' => 'cloudconvert']);
    config(['queue.default' => 'sync']);
});

test('returns null when asset file does not exist', function () {
    // Queue job creation and upload responses — neither should be consumed
    $this->mockHttpClient
        ->addResponse(CloudConvertResponseFactory::jobCreated())
        ->addResponse(CloudConvertResponseFactory::uploadSuccessful());

    // Create an asset pointing to a non-existent file
    $asset = $this->makeEmptyAsset('test.nonexistent');
    // The container disk points to the temp directory, so resolvedPath()
    // will point to a file we never created

    $result = $this->cloudConvertDriver->createConversion($asset);

    // Before the fix: this might return a job ID (SDK may or may not handle
    // the invalid resource), or throw, or make HTTP calls with bad data.
    // After the fix: returns null immediately without any HTTP requests.
    expect($result)->toBeNull();

    // Verify NO HTTP requests were made — we caught it before the API call
    $this->mockHttpClient->assertNoRequestsMade();
});

test('returns conversion ID when asset file exists', function () {
    $this->mockHttpClient
        ->addResponse(CloudConvertResponseFactory::jobCreated('existing-job'))
        ->addResponse(CloudConvertResponseFactory::uploadSuccessful());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $result = $this->cloudConvertDriver->createConversion($asset);

    expect($result)->toBe('existing-job');
});
