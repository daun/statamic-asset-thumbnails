<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use Statamic\Assets\Asset;

class NullDriver extends AbstractDriver implements DriverInterface
{
    public function generate(Asset $asset): void {}

    protected array $supportedExtensions = [];
}
