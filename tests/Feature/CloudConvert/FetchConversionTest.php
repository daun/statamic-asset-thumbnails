<?php

use Daun\StatamicAssetThumbnails\Drivers\ConversionResult;
use Tests\Concerns\FakesCloudConvert;
use Tests\Support\CloudConvertResponseFactory;

/*
|--------------------------------------------------------------------------
| CloudConvert fetchResult() Tests
|--------------------------------------------------------------------------
|
| These tests use a PSR-18 MockHttpClient injected into the CloudConvert
| SDK to verify the polling/result-fetching behavior of fetchResult() —
| similar to Saloon's request recording or Http::fake().
|
| With the refactored architecture, we test the driver's fetchResult()
| method directly instead of going through job handle() methods.
|
*/

uses(FakesCloudConvert::class);

beforeEach(function () {
    $this->setUpCloudConvertFake();

    config(['statamic.asset-thumbnails.driver' => 'cloudconvert']);
    config(['queue.default' => 'sync']);
});

test('polls CloudConvert API for job status', function () {
    $this->mockHttpClient->addResponse(CloudConvertResponseFactory::jobProcessing());

    $this->cloudConvertDriver->fetchResult('job-123');

    // Should have made a GET request to check the job status
    $this->mockHttpClient->assertRequestMade('/v2/jobs/job-123', 'GET');
});

test('returns null when job is still processing', function () {
    $this->mockHttpClient->addResponse(CloudConvertResponseFactory::jobProcessing());

    $result = $this->cloudConvertDriver->fetchResult('job-123');

    expect($result)->toBeNull();
});

test('returns ConversionResult when job finishes successfully', function () {
    $downloadUrl = 'https://storage.cloudconvert.com/result/thumb.webp';

    $this->mockHttpClient->addResponse(
        CloudConvertResponseFactory::jobFinished(
            downloadUrl: $downloadUrl,
            filename: 'thumb.webp',
        )
    );

    $result = $this->cloudConvertDriver->fetchResult('job-123');

    expect($result)->toBeInstanceOf(ConversionResult::class);
    expect($result->url)->toBe($downloadUrl);
    expect($result->filename)->toBe('thumb.webp');
});

test('returns false when job has errored', function () {
    $this->mockHttpClient->addResponse(CloudConvertResponseFactory::jobError());

    $result = $this->cloudConvertDriver->fetchResult('job-123');

    expect($result)->toBeFalse();
});

test('sends authorization header when polling job status', function () {
    $this->mockHttpClient->addResponse(CloudConvertResponseFactory::jobProcessing());

    $this->cloudConvertDriver->fetchResult('job-123');

    $pollRequest = $this->mockHttpClient->getRequest(0);
    expect($pollRequest->getHeaderLine('Authorization'))->toContain('Bearer');
});

test('records all HTTP interactions for debugging', function () {
    $this->mockHttpClient->addResponse(CloudConvertResponseFactory::jobProcessing());

    $this->cloudConvertDriver->fetchResult('job-123');

    $recorded = $this->mockHttpClient->getRecorded();
    expect($recorded)->toBeArray()->toHaveCount(1);

    foreach ($recorded as $interaction) {
        expect($interaction)->toHaveKeys(['request', 'response']);
        expect($interaction['request'])->toBeInstanceOf(\Psr\Http\Message\RequestInterface::class);
        expect($interaction['response'])->toBeInstanceOf(\Psr\Http\Message\ResponseInterface::class);
    }
});

test('returns null on API exception to allow retry', function () {
    // Queue no responses — the mock will return an empty 200 with "{}" body
    // The SDK hydrator will fail, triggering the catch block in fetchResult()
    $this->mockHttpClient->setDefaultResponse(
        new \GuzzleHttp\Psr7\Response(500, [], '{"error": "Internal Server Error"}')
    );

    $result = $this->cloudConvertDriver->fetchResult('job-123');

    expect($result)->toBeNull();
});
