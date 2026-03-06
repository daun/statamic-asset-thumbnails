<?php

use Tests\Concerns\FakesTransloadit;
use Tests\Support\TransloaditResponseFactory;

/*
|--------------------------------------------------------------------------
| Transloadit createConversion() Tests
|--------------------------------------------------------------------------
|
| These tests use a Mockery mock of the Transloadit SDK class to intercept
| and verify all API calls made during conversion creation — since the
| Transloadit SDK uses raw cURL with no HTTP client injection point,
| we mock at the SDK method level rather than the HTTP transport level.
|
| With the refactored architecture, we test the driver's createConversion()
| method directly instead of going through job handle() methods.
|
*/

uses(FakesTransloadit::class);

beforeEach(function () {
    $this->setUpTransloaditFake();

    config(['statamic.asset-thumbnails.driver' => 'transloadit']);
    config(['queue.default' => 'sync']);
});

test('creates an assembly via the Transloadit API', function () {
    $this->mockCreateAssembly(TransloaditResponseFactory::assemblyCreated());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $this->transloaditDriver->createConversion($asset);

    $this->assertCreateAssemblyCalled();
});

test('passes file path in assembly options', function () {
    $this->mockCreateAssembly(TransloaditResponseFactory::assemblyCreated());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $this->transloaditDriver->createConversion($asset);

    $options = $this->getCreateAssemblyOptions();
    expect($options)->toHaveKey('files');
    expect($options['files'])->toBeArray()->toHaveCount(1);
    expect($options['files'][0])->toBe($asset->resolvedPath());
});

test('configures the preview step with correct defaults', function () {
    $this->mockCreateAssembly(TransloaditResponseFactory::assemblyCreated());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $this->transloaditDriver->createConversion($asset);

    $options = $this->getCreateAssemblyOptions();
    $steps = $options['params']['steps'] ?? [];

    expect($steps)->toHaveKey('preview');
    expect($steps['preview']['robot'])->toBe('/file/preview');
    expect($steps['preview']['format'])->toBeIn(['jpg', 'png']);
    expect($steps['preview']['resize_strategy'])->toBe('fit');
});

test('configures document-specific options for PDF files', function () {
    $this->mockCreateAssembly(TransloaditResponseFactory::assemblyCreated());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $this->transloaditDriver->createConversion($asset);

    $options = $this->getCreateAssemblyOptions();
    $preview = $options['params']['steps']['preview'] ?? [];

    // Documents use PNG format with higher resolution
    expect($preview['format'])->toBe('png');
    expect($preview['width'])->toBe(1000);
    expect($preview['height'])->toBe(1000);
});

test('returns the assembly ID on success', function () {
    $this->mockCreateAssembly(TransloaditResponseFactory::assemblyCreated('my-assembly-id'));

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $result = $this->transloaditDriver->createConversion($asset);

    expect($result)->toBe('my-assembly-id');
});

test('returns null when assembly creation fails', function () {
    $this->mockCreateAssembly(TransloaditResponseFactory::apiError());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $result = $this->transloaditDriver->createConversion($asset);

    expect($result)->toBeNull();
});

test('records all API interactions for debugging', function () {
    $this->mockCreateAssembly(TransloaditResponseFactory::assemblyCreated());

    $asset = $this->uploadTestFileToTestContainer('test.txt', 'document.pdf');
    $this->transloaditDriver->createConversion($asset);

    expect($this->transloaditCalls)->toHaveCount(1);
    expect($this->transloaditCalls[0]['method'])->toBe('createAssembly');
    expect($this->transloaditCalls[0]['args'])->toBeArray();
});
