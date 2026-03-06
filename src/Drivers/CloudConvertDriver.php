<?php

namespace Daun\StatamicAssetThumbnails\Drivers;

use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use Statamic\Assets\Asset;

class CloudConvertDriver extends AbstractDriver implements DriverInterface
{
    protected CloudConvert $api;

    public function __construct(array $config = [])
    {
        $this->api = new CloudConvert([
            'api_key' => $config['api_key'] ?? null,
        ]);
    }

    public function api(): CloudConvert
    {
        return $this->api;
    }

    public function createConversion(Asset $asset): ?string
    {
        $job = (new Job)
            ->addTask(new Task('import/upload', 'upload-task'))
            ->addTask(
                (new Task('thumbnail', 'thumbnail-task'))
                    ->set('input', 'upload-task')
                    ->set('timeout', 15)
                    ->set('output_format', 'webp')
                    ->set('width', 500)
                    ->set('height', 500)
                    ->set('fit', 'max')
            )
            ->addTask(
                (new Task('export/url', 'export-task'))
                    ->set('input', 'thumbnail-task')
            );

        $job = $this->api->jobs()->create($job);

        $uploadTask = $job->getTasks()?->whereName('upload-task')[0];

        $this->api->tasks()->upload($uploadTask, fopen($asset->resolvedPath(), 'r'), $asset->basename());

        return $job->getId();
    }

    public function fetchResult(string $conversionId): ConversionResult|false|null
    {
        try {
            $job = $this->api->jobs()->get($conversionId);
        } catch (\Throwable) {
            return null;
        }

        if (in_array($job->getStatus(), [Job::STATUS_PROCESSING, Job::STATUS_WATING])) {
            return null;
        }

        if ($job->getStatus() === Job::STATUS_ERROR) {
            return false;
        }

        if ($job->getStatus() === Job::STATUS_FINISHED) {
            $exports = $job->getTasks()?->operation('export/url')?->status(Task::STATUS_FINISHED) ?? [];

            if (count($exports)) {
                $result = $exports[0]->getResult()?->files[0] ?? null;
                $url = $result->url ?? null;
                $filename = $result->filename ?? null;
                if ($url && $filename) {
                    return new ConversionResult($url, $filename);
                }
            }
        }

        return false;
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
