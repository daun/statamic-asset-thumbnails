<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use Daun\StatamicAssetThumbnails\Drivers\AbstractDriver;
use Daun\StatamicAssetThumbnails\Drivers\Transloadit\GenerateThumbnailJob;
use Daun\StatamicAssetThumbnails\Interfaces\DriverInterface;
use Daun\StatamicAssetThumbnails\Support\Queue;
use Statamic\Assets\Asset;
use transloadit\Transloadit as TransloaditApi;

class TransloaditDriver extends AbstractDriver implements DriverInterface
{
    protected string $id = 'transloadit';

    protected TransloaditApi $api;

    public function __construct()
    {
        $this->api = new TransloaditApi([
            'key' => config('statamic-asset-thumbnails.transloadit.auth_key'),
            'secret' => config('statamic-asset-thumbnails.transloadit.auth_secret'),
        ]);
    }

    public function api(): TransloaditApi
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
        // 'svg',
        // 'svgz',

        // Adobe formats
        'eps',
        'ai',
        'psd',
        'psb',


        // Raw image formats
        'raw',
        'heic',
        'heif',
        'nef',
        'nrw',
        'cr2',
        'cr3',
        'crw',
        'orf',
        'dng',
        'arw',
        'rw2',
        'raf',
        'dcm',

        // Icon formats
        'cur',
        'ico',

        // Video formats
        'mp4',
        'h264',
        'm4v',
        'mov',
        'avi',
        'ogv',
        'mkv',
        'webm',

        // Audio formats
        'mp3',
        'aac',
        'aif',
        'aiff',
        'm4a',
        'ogg',
        'opus',
        'flac',
        'wav',

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
