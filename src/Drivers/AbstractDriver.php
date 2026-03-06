<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use Daun\StatamicAssetThumbnails\Jobs\CreateConversionJob;
use Daun\StatamicAssetThumbnails\Support\Queue;
use Statamic\Assets\Asset;

abstract class AbstractDriver implements DriverInterface
{
    protected array $supportedExtensions;

    /** @param array<string, mixed> $config */
    public function __construct(protected array $config = []) {}

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
    public function generate(Asset $asset): void
    {
        if (Queue::isSync()) {
            CreateConversionJob::dispatch($asset)->afterResponse();
        } else {
            CreateConversionJob::dispatch($asset);
        }
    }

    /**
     * Create a conversion job on the external service.
     */
    abstract public function createConversion(Asset $asset): ?string;

    /**
     * Fetch the result of a previously created conversion.
     */
    abstract public function fetchResult(string $conversionId): ConversionResult|ConversionStatus;
}
