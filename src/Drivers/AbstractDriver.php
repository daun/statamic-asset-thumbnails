<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use Daun\StatamicAssetThumbnails\Interfaces\DriverInterface;
use Statamic\Assets\Asset;

abstract class AbstractDriver implements DriverInterface
{
    protected string $id;

    protected array $supportedExtensions;

    /**
     * The unique id of the driver.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Check if the driver supports generating thumbnails for the given asset.
     */
    public function supports(Asset $asset): bool
    {
        return $asset->guessedExtensionIsOneOf($this->supportedExtensions)
            || $asset->extensionIsOneOf($this->supportedExtensions);
    }

    /**
     * Dispatch a job to generate a thumbnail for the given asset.
     */
    abstract public function generate(Asset $asset): void;
}
