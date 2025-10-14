<?php

namespace Daun\StatamicAssetThumbnails\Facades;

use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Daun\StatamicAssetThumbnails\Services\ThumbnailService
 */
class Thumbnails extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ThumbnailService::class;
    }
}
