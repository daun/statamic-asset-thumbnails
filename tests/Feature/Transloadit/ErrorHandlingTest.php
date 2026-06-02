<?php

use Daun\StatamicAssetThumbnails\Drivers\ConversionStatus;
use Tests\Concerns\FakesTransloadit;

/*
|--------------------------------------------------------------------------
| Transloadit fetchResult() Error Handling Tests
|--------------------------------------------------------------------------
|
| BUG: TransloaditDriver::fetchResult() has no try/catch around the
| SDK call. If the Transloadit SDK throws an exception (network error,
| auth failure, etc.), it propagates directly to FetchConversionJob and
| fails the job — bypassing the controlled retry/backoff logic.
|
| Compare with CloudConvertDriver which catches exceptions and returns
| ConversionStatus::Pending to trigger orderly retries.
|
| FIX: Wrap the SDK call in try/catch and return Pending on errors,
| consistent with CloudConvert's behavior for transient errors.
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
        ->andThrow(new \RuntimeException('Network error'));

    // Before the fix: this throws RuntimeException (bug — no error handling)
    // After the fix: this returns Pending (correct — allows orderly retry)
    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBe(ConversionStatus::Pending);
});

test('returns Pending when API returns error response', function () {
    // The "ok" flag is false — should return Pending
    $this->mockGetAssembly(\Tests\Support\TransloaditResponseFactory::apiError());

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBe(ConversionStatus::Pending);
});

test('returns Pending when assembly is still executing', function () {
    $this->mockGetAssembly(\Tests\Support\TransloaditResponseFactory::assemblyExecuting());

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBe(ConversionStatus::Pending);
});
