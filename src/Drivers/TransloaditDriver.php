<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use Statamic\Assets\Asset;
use transloadit\Transloadit;

class TransloaditDriver extends AbstractDriver implements DriverInterface
{
    protected Transloadit $api;

    public function __construct(array $config = [])
    {
        $this->api = new Transloadit([
            'key' => $config['auth_key'] ?? null,
            'secret' => $config['auth_secret'] ?? null,
        ]);
    }

    public function api(): Transloadit
    {
        return $this->api;
    }

    public function createConversion(Asset $asset): ?string
    {
        $response = $this->api->createAssembly([
            'files' => [$asset->resolvedPath()],
            'params' => [
                'steps' => [
                    'preview' => [
                        'robot' => '/file/preview',
                        ...$this->getFilePreviewRobotOptions($asset),
                    ],
                ],
            ],
        ]);

        return $response->data['assembly_id'] ?? null;
    }

    public function fetchResult(string $conversionId): ConversionResult|false|null
    {
        $response = $this->api->getAssembly($conversionId);
        $assembly = ($response->data['ok'] ?? null) ? $response->data : null;

        if (! $assembly) {
            return null;
        }

        $status = $assembly['ok'] ?? null;

        if (in_array($status, ['ASSEMBLY_EXECUTING', 'ASSEMBLY_UPLOADING'])) {
            return null;
        }

        if (in_array($status, ['ASSEMBLY_CANCELED', 'REQUEST_ABORTED'])) {
            return false;
        }

        if ($status === 'ASSEMBLY_COMPLETED') {
            $result = $assembly['results']['preview'][0] ?? null;
            if ($result) {
                $url = $result['ssl_url'] ?? $result['url'] ?? null;
                $filename = $result['name'] ?? null;
                if ($url && $filename) {
                    return new ConversionResult($url, $filename);
                }
            }
        }

        return false;
    }

    protected function getFilePreviewRobotOptions(Asset $asset): array
    {
        $defaults = [
            'format' => 'jpg',
            'width' => 500,
            'height' => 500,
            'resize_strategy' => 'fit',
            'output_meta' => false,
            'queue' => 'batch',
            'optimize' => true,
            'strategy' => [
                'audio' => ['artwork', 'waveform'],
                'video' => ['clip', 'frame'],
                'document' => ['page'],
                'image' => ['image'],
                'webpage' => ['render'],
            ],
        ];

        $options = [];

        if ($asset->isAudio()) {
            $options = [
                'format' => 'png',
                'background' => '#EFEFF0',
                'waveform_center_color' => '#27272A',
                'waveform_outer_color' => '#27272A',
                'width' => 800,
                'height' => 800,
                'waveform_width' => 800,
                'waveform_height' => 800,
            ];
        }

        if ($asset->isVideo()) {
            $options = [
                'width' => 400,
                'height' => 400,
                'clip_format' => 'webp',
                'clip_framerate' => 8,
            ];
        }

        if ($asset->guessedExtensionIsOneOf(['txt', 'rtf', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'pages', 'key', 'numbers'])) {
            $options = [
                'format' => 'png',
                'width' => 1000,
                'height' => 1000,
            ];
        }

        return array_merge($defaults, $options);
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
