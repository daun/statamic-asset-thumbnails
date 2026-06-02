<?php

use CloudConvert\Exceptions\HttpClientException;
use Daun\StatamicAssetThumbnails\Drivers\ConversionStatus;
use GuzzleHttp\Psr7\Response;
use Tests\Concerns\FakesCloudConvert;
use Tests\Support\CloudConvertResponseFactory;

/*
|--------------------------------------------------------------------------
| CloudConvert Error Handling Tests
|--------------------------------------------------------------------------
|
| Ensure we distinguish transient errors (network, 5xx, 429) from permanent
| errors (401, 403, 404) and let permanent ones propagate to fail the job.
*/

uses(FakesCloudConvert::class);

beforeEach(function () {
    $this->setUpCloudConvertFake();

    config(['statamic.asset-thumbnails.driver' => 'cloudconvert']);
    config(['queue.default' => 'sync']);
});

test('propagates permanent authentication error instead of returning Pending', function () {
    // A 401 response from CloudConvert means the API key is invalid.
    $this->mockHttpClient->addResponse(
        new Response(401, ['Content-Type' => 'application/json'], json_encode([
            'message' => 'Invalid API key',
        ]))
    );

    $this->cloudConvertDriver->fetchResult('job-123');
})->throws(HttpClientException::class);

test('propagates permanent not-found error instead of returning Pending', function () {
    // A 404 response means the job doesn't exist.
    $this->mockHttpClient->addResponse(
        new Response(404, ['Content-Type' => 'application/json'], json_encode([
            'message' => 'Job not found',
        ])),
    );

    $this->cloudConvertDriver->fetchResult('job-123');
})->throws(HttpClientException::class);

test('returns Pending on transient server error', function () {
    // A 500 response is transient — the server might recover.
    $this->mockHttpClient->addResponse(
        new Response(500, ['Content-Type' => 'application/json'], json_encode([
            'message' => 'Internal server error',
        ]))
    );

    $result = $this->cloudConvertDriver->fetchResult('job-123');

    expect($result)->toBe(ConversionStatus::Pending);
});

test('returns Pending when job is still processing', function () {
    // Normal processing state — should retry.
    $this->mockHttpClient->addResponse(CloudConvertResponseFactory::jobProcessing());

    $result = $this->cloudConvertDriver->fetchResult('job-123');

    expect($result)->toBe(ConversionStatus::Pending);
});
