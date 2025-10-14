<?php

namespace Daun\StatamicAssetThumbnails\Support;

class Queue
{
    public static function connection(): ?string
    {
        return config('statamic-asset-thumbnails.queue.connection') ?? config('queue.default');
    }

    public static function queue(): ?string
    {
        return config('statamic-asset-thumbnails.queue.queue', 'default');
    }

    public static function isSync(): bool
    {
        return static::connection() === 'sync';
    }
}
