<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use Statamic\Assets\Asset;

abstract class AbstractDriver implements DriverInterface
{
    protected array $supportedExtensions;

    public function __construct(array $config = []) {}

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
