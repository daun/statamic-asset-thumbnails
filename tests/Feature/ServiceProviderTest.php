<?php

use Daun\StatamicAssetThumbnails\ServiceProvider;
use Daun\StatamicAssetThumbnails\Services\ThumbnailService;

test('provides services', function () {
    $provider = new ServiceProvider($this->app);
    expect($provider->provides())->toBeArray()->not->toBeEmpty();
});

test('binds thumbnail service', function () {
    expect($this->app[ThumbnailService::class])->toBeInstanceOf(ThumbnailService::class);
});
