<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use Daun\StatamicAssetThumbnails\Drivers\Transloadit\GenerateThumbnailJob;
use Statamic\Assets\Asset;
use transloadit\Transloadit;

class TransloaditDriver extends AbstractDriver implements DriverInterface
{
    protected string $id = 'transloadit';

    protected Transloadit $api;

    public function __construct()
    {
        $this->api = new Transloadit([
            'key' => config('statamic-asset-thumbnails.transloadit.auth_key'),
            'secret' => config('statamic-asset-thumbnails.transloadit.auth_secret'),
        ]);
    }

    public function api(): Transloadit
    {
        return $this->api;
    }

    public function generate(Asset $asset): void
    {
        GenerateThumbnailJob::dispatch($asset)->afterResponse();
    }

    protected array $supportedExtensions = [
        // Image formats
        'bmp',
        'tif',
        'tiff',

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
        'wmv',

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
