<?php

use Daun\StatamicAssetThumbnails\Drivers\ConversionStatus;
use Tests\Concerns\FakesTransloadit;
use Tests\Support\TransloaditResponseFactory;

/*
|--------------------------------------------------------------------------
| Transloadit Error Handling Tests
|--------------------------------------------------------------------------
|
| Ensure we distinguish transient errors (network, 5xx, 429) from permanent
| errors (401, 403, 404) and let permanent ones propagate to fail the job.
*/

uses(FakesTransloadit::class);

beforeEach(function () {
    $this->setUpTransloaditFake();

    config(['statamic.asset-thumbnails.driver' => 'transloadit']);
    config(['queue.default' => 'sync']);
});

test('returns Pending when SDK throws an exception', function () {
    // Simulate a network error or SDK failure during getAssembly()
    $this->mockTransloaditApi
        ->shouldReceive('getAssembly')
        ->once()
        ->andThrow(new RuntimeException('Network error'));

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBe(ConversionStatus::Pending);
});

test('returns Pending when API returns error response', function () {
    $this->mockGetAssembly(TransloaditResponseFactory::apiError());

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBe(ConversionStatus::Pending);
});

test('returns Pending when assembly is still executing', function () {
    $this->mockGetAssembly(TransloaditResponseFactory::assemblyExecuting());

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBe(ConversionStatus::Pending);
});
