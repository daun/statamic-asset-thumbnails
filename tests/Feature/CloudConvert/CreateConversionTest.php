<?php

use Tests\Concerns\FakesCloudConvert;
use Tests\Support\CloudConvertResponseFactory;

/*
|--------------------------------------------------------------------------
| CloudConvert createConversion() Tests
|--------------------------------------------------------------------------
|
| These tests use a PSR-18 MockHttpClient injected into the CloudConvert
| SDK to intercept and verify all HTTP requests made during conversion
| creation — similar to Saloon's request recording or Http::fake().
|
| With the refactored architecture, we test the driver's createConversion()
| method directly instead of going through job handle() methods.
|
*/

uses(FakesCloudConvert::class);

beforeEach(function () {
    $this->setUpCloudConvertFake();

    config(['statamic.asset-thumbnails.driver' => 'cloudconvert']);
    config(['queue.default' => 'sync']);
});

test('creates a CloudConvert job with correct task pipeline', function () {
    $this->mockHttpClient
        ->addResponse(CloudConvertResponseFactory::jobCreated())
        ->addResponse(CloudConvertResponseFactory::uploadSuccessful());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $this->cloudConvertDriver->createConversion($asset);

    // Verify the job creation request was sent
    $this->mockHttpClient->assertRequestMade('/v2/jobs', 'POST');

    // Verify the request body contains the expected task pipeline
    $createRequest = $this->mockHttpClient->getRequest(0);
    $body = json_decode((string) $createRequest->getBody(), true);

    expect($body['tasks'])->toHaveKeys(['upload-task', 'thumbnail-task', 'export-task']);
    expect($body['tasks']['upload-task']['operation'])->toBe('import/upload');
    expect($body['tasks']['thumbnail-task']['operation'])->toBe('thumbnail');
    expect($body['tasks']['export-task']['operation'])->toBe('export/url');
});

test('configures thumbnail task with correct defaults', function () {
    $this->mockHttpClient
        ->addResponse(CloudConvertResponseFactory::jobCreated())
        ->addResponse(CloudConvertResponseFactory::uploadSuccessful());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $this->cloudConvertDriver->createConversion($asset);

    $createRequest = $this->mockHttpClient->getRequest(0);
    $body = json_decode((string) $createRequest->getBody(), true);

    $thumbnailTask = $body['tasks']['thumbnail-task'];
    expect($thumbnailTask['output_format'])->toBe('webp');
    expect($thumbnailTask['width'])->toBe(500);
    expect($thumbnailTask['height'])->toBe(500);
    expect($thumbnailTask['fit'])->toBe('max');
});

test('uploads asset file to CloudConvert', function () {
    $uploadFormUrl = 'https://storage.cloudconvert.com/upload/test-upload';

    $this->mockHttpClient
        ->addResponse(CloudConvertResponseFactory::jobCreated(uploadFormUrl: $uploadFormUrl))
        ->addResponse(CloudConvertResponseFactory::uploadSuccessful());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $this->cloudConvertDriver->createConversion($asset);

    // Verify the file upload request was made to the form URL
    $this->mockHttpClient->assertRequestCount(2);
    $this->mockHttpClient->assertRequestMade($uploadFormUrl, 'POST');
});

test('returns the job ID on success', function () {
    $this->mockHttpClient
        ->addResponse(CloudConvertResponseFactory::jobCreated('my-job-id'))
        ->addResponse(CloudConvertResponseFactory::uploadSuccessful());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $result = $this->cloudConvertDriver->createConversion($asset);

    expect($result)->toBe('my-job-id');
});

test('sends authorization header with job creation request', function () {
    $this->mockHttpClient
        ->addResponse(CloudConvertResponseFactory::jobCreated())
        ->addResponse(CloudConvertResponseFactory::uploadSuccessful());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $this->cloudConvertDriver->createConversion($asset);

    $createRequest = $this->mockHttpClient->getRequest(0);
    expect($createRequest->getHeaderLine('Authorization'))->toContain('Bearer');
});

test('records all HTTP interactions for debugging', function () {
    $this->mockHttpClient
        ->addResponse(CloudConvertResponseFactory::jobCreated())
        ->addResponse(CloudConvertResponseFactory::uploadSuccessful());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $this->cloudConvertDriver->createConversion($asset);

    // All interactions are recorded and can be inspected
    $recorded = $this->mockHttpClient->getRecorded();
    expect($recorded)->toBeArray()->toHaveCount(2);

    foreach ($recorded as $interaction) {
        expect($interaction)->toHaveKeys(['request', 'response']);
        expect($interaction['request'])->toBeInstanceOf(\Psr\Http\Message\RequestInterface::class);
        expect($interaction['response'])->toBeInstanceOf(\Psr\Http\Message\ResponseInterface::class);
    }
});
