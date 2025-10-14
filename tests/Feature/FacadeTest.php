<?php

use Daun\StatamicAssetThumbnails\Facades\Thumbnails;
use Daun\StatamicAssetThumbnails\Services\ThumbnailService;

test('creates correct facade instance', function () {
    expect(Thumbnails::getFacadeRoot())->toBeInstanceOf(ThumbnailService::class);
});
