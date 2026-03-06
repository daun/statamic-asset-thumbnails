<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use Statamic\Assets\Asset;

interface DriverInterface
{
    /**
     * Check if the driver supports generating thumbnails for the given asset.
     */
    public function supports(Asset $asset): bool;

    /**
     * Dispatch a job to generate a thumbnail for the given asset.
     */
    public function generate(Asset $asset): void;
}
