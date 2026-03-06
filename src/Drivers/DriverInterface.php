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

    /**
     * Create a conversion job on the external service and return its ID.
     *
     * This is where the driver-specific API calls happen: creating the job,
     * uploading the source file, etc.
     *
     * Returns the conversion/job/assembly ID, or null on failure.
     */
    public function createConversion(Asset $asset): ?string;

    /**
     * Fetch the result of a previously created conversion.
     *
     * Returns:
     * - ConversionResult: the conversion completed successfully with a downloadable file
     * - ConversionStatus::Pending: still processing, caller should retry
     * - ConversionStatus::Failed: failed permanently, caller should not retry
     */
    public function fetchResult(string $conversionId): ConversionResult|ConversionStatus;
}
