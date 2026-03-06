<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

/**
 * Represents the status of a conversion that has not yet produced a result.
 *
 * Returned by DriverInterface::fetchResult() when the conversion
 * did not complete successfully with a downloadable file.
 */
enum ConversionStatus
{
    /** The conversion is still processing; caller should retry later. */
    case Pending;

    /** The conversion failed permanently; caller should not retry. */
    case Failed;
}
