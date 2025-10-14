<?php

namespace Daun\StatamicAssetThumbnails\Interfaces;

use Statamic\Assets\Asset;

interface DriverInterface
{
    /**
     * The unique id of the driver.
     */
    public function id(): string;

    /**
     * Check if the driver supports generating thumbnails for the given asset.
     */
    public function supports(Asset $asset): bool;

    /**
     * Dispatch a job to generate a thumbnail for the given asset.
     */
    public function generate(Asset $asset): void;
}
