<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

/**
 * Value object representing a completed conversion result.
 *
 * Returned by DriverInterface::fetchResult() when the external
 * service has finished generating the thumbnail.
 */
class ConversionResult
{
    public function __construct(
        public readonly string $url,
        public readonly string $filename,
    ) {}
}
