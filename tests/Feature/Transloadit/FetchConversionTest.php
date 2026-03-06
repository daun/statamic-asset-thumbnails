<?php

use Daun\StatamicAssetThumbnails\Drivers\ConversionResult;
use Tests\Concerns\FakesTransloadit;
use Tests\Support\TransloaditResponseFactory;

/*
|--------------------------------------------------------------------------
| Transloadit fetchResult() Tests
|--------------------------------------------------------------------------
|
| These tests use a Mockery mock of the Transloadit SDK class to verify
| the polling/result-fetching behavior of fetchResult() — since the
| Transloadit SDK uses raw cURL, we mock at the SDK method level.
|
| With the refactored architecture, we test the driver's fetchResult()
| method directly instead of going through job handle() methods.
|
*/

uses(FakesTransloadit::class);

beforeEach(function () {
    $this->setUpTransloaditFake();

    config(['statamic.asset-thumbnails.driver' => 'transloadit']);
    config(['queue.default' => 'sync']);
});

test('polls Transloadit API for assembly status', function () {
    $this->mockGetAssembly(
        TransloaditResponseFactory::assemblyExecuting(),
        expectedId: 'assembly-123',
    );

    $this->transloaditDriver->fetchResult('assembly-123');

    $this->assertGetAssemblyCalled('assembly-123');
});

test('returns null when assembly is still executing', function () {
    $this->mockGetAssembly(TransloaditResponseFactory::assemblyExecuting());

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBeNull();
});

test('returns null when assembly is still uploading', function () {
    $this->mockGetAssembly(TransloaditResponseFactory::assemblyUploading());

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBeNull();
});

test('returns ConversionResult when assembly completes successfully', function () {
    $downloadUrl = 'https://tmp.transloadit.com/result/thumb.jpg';

    $this->mockGetAssembly(
        TransloaditResponseFactory::assemblyCompleted(
            downloadUrl: $downloadUrl,
            filename: 'thumb.jpg',
        )
    );

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBeInstanceOf(ConversionResult::class);
    expect($result->url)->toBe($downloadUrl);
    expect($result->filename)->toBe('thumb.jpg');
});

test('returns false when assembly is canceled', function () {
    $this->mockGetAssembly(TransloaditResponseFactory::assemblyCanceled());

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBeFalse();
});

test('returns false when request is aborted', function () {
    $this->mockGetAssembly(TransloaditResponseFactory::assemblyAborted());

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBeFalse();
});

test('returns null when API returns error response', function () {
    $this->mockGetAssembly(TransloaditResponseFactory::apiError());

    $result = $this->transloaditDriver->fetchResult('assembly-123');

    expect($result)->toBeNull();
});

test('records all API interactions for debugging', function () {
    $this->mockGetAssembly(TransloaditResponseFactory::assemblyExecuting());

    $this->transloaditDriver->fetchResult('assembly-123');

    expect($this->transloaditCalls)->toHaveCount(1);
    expect($this->transloaditCalls[0]['method'])->toBe('getAssembly');
    expect($this->transloaditCalls[0]['args']['assembly_id'])->toBe('assembly-123');
});
