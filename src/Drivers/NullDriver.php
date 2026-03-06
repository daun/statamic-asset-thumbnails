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

    public function fetchResult(string $conversionId): ConversionResult|false|null
    {
        return false;
    }

    protected array $supportedExtensions = [];
}
