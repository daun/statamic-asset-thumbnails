<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use Daun\StatamicAssetThumbnails\Drivers\CloudConvert\GenerateThumbnailJob;
use Daun\StatamicAssetThumbnails\Support\Queue;
use Statamic\Assets\Asset;
use CloudConvert\CloudConvert as CloudConvertApi;

class CloudConvertDriver extends AbstractDriver implements DriverInterface
{
    protected string $id = 'cloudconvert';

    protected CloudConvertApi $api;

    public function __construct()
    {
        $this->api = new CloudConvertApi([
            'api_key' => config('statamic-asset-thumbnails.cloudconvert.api_key'),
            'sandbox' => false,
        ]);
    }

    public function api(): CloudConvertApi
    {
        return $this->api;
    }

    public function generate(Asset $asset): void
    {
        GenerateThumbnailJob::dispatch($asset)
            ->onConnection(Queue::connection())
            ->onQueue(Queue::queue())
            ->afterResponse();
    }

    protected array $supportedExtensions = [
        // Image formats
        'bmp',
        'tif',
        'tiff',

        // Adobe formats
        'eps',
        // 'ai', // (not supported)
        'psd',
        'psb',

        // Raw image formats
        'raw',
        'heic',
        'heif',
        'nef',
        // 'nrw', // (not supported)
        'cr2',
        'cr3',
        'crw',
        'orf',
        'dng',
        'arw',
        'rw2',
        'raf',
        // 'dcm', // (not supported)

        // Icon formats
        // 'cur', // (not supported)
        'ico',

        // Video formats
        'mp4',
        // 'h264', // (not supported)
        'm4v',
        'mov',
        'avi',
        'ogv',
        'mkv',
        'webm',
        'wmv',

        // Audio formats (not supported)

        // Document formats
        'txt',
        'rtf',
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
    ];
}
