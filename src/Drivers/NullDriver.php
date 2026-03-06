<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use Statamic\Assets\Asset;

class NullDriver extends AbstractDriver implements DriverInterface
{
    public function generate(Asset $asset): void {}

    public function createConversion(Asset $asset): ?string
    {
        return null;
    }

    public function fetchResult(string $conversionId): ConversionResult|ConversionStatus
    {
        return ConversionStatus::Failed;
    }

    protected array $supportedExtensions = [];
}
