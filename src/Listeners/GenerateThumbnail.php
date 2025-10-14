<?php

namespace Daun\StatamicAssetThumbnails\Listeners;

use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Statamic\Events\AssetReuploaded;
use Statamic\Events\AssetUploaded;

class GenerateThumbnail
{
    public function __construct(protected ThumbnailService $service) {}

    public function handle(AssetUploaded|AssetReuploaded $event)
    {
        $this->service->generate($event->asset);
    }
}
