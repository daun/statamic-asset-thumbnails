<?php

use Daun\StatamicAssetThumbnails\Drivers\CloudConvertDriver;
use Daun\StatamicAssetThumbnails\Drivers\DriverInterface;
use Daun\StatamicAssetThumbnails\Drivers\NullDriver;
use Daun\StatamicAssetThumbnails\Drivers\TransloaditDriver;
use Daun\StatamicAssetThumbnails\ServiceProvider;
use Daun\StatamicAssetThumbnails\Services\ThumbnailService;

test('provides services', function () {
    $provider = new ServiceProvider($this->app);
    expect($provider->provides())->toBeArray()->not->toBeEmpty();
});

test('binds thumbnail service', function () {
    expect($this->app[ThumbnailService::class])->toBeInstanceOf(ThumbnailService::class);
});

test('resolves driver from short name', function (string $name, string $expected) {
    config()->set('statamic.asset-thumbnails.driver', $name);
    config()->set('statamic.asset-thumbnails.cloudconvert.api_key', 'test-key');
    $this->app->forgetInstance(DriverInterface::class);

    expect($this->app[DriverInterface::class])->toBeInstanceOf($expected);
})->with([
    ['transloadit', TransloaditDriver::class],
    ['cloudconvert', CloudConvertDriver::class],
    ['null', NullDriver::class],
]);

test('resolves driver from class name for backward compatibility', function (string $class) {
    config()->set('statamic.asset-thumbnails.driver', $class);
    config()->set('statamic.asset-thumbnails.cloudconvert.api_key', 'test-key');
    $this->app->forgetInstance(DriverInterface::class);

    expect($this->app[DriverInterface::class])->toBeInstanceOf($class);
})->with([
    TransloaditDriver::class,
    CloudConvertDriver::class,
    NullDriver::class,
]);
