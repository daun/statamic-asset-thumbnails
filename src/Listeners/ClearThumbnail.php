<?php

namespace Daun\StatamicAssetThumbnails\Listeners;

use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Statamic\Events\AssetDeleted;

class ClearThumbnail
{
    public function __construct(protected ThumbnailService $service) {}

    public function handle(AssetDeleted $event)
    {
        $this->service->remove($event->asset);
    }
}
