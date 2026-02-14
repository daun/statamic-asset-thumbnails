<?php

namespace Daun\StatamicAssetThumbnails\Drivers\CloudConvert;

use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use Daun\StatamicAssetThumbnails\Drivers\CloudConvertDriver;
use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Daun\StatamicAssetThumbnails\Support\Queue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Statamic\Assets\Asset;

class GenerateThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Asset $asset)
    {
        $this->connection = Queue::connection();
        $this->queue = Queue::queue();
    }

    public function handle(ThumbnailService $service, CloudConvertDriver $driver): void
    {
        // Abort if a thumbnail was generated in the meantime
        if ($service->exists($this->asset)) {
            return;
        }

        $assemblyId = $this->createThumbnailJob($driver);
        if ($assemblyId) {
            DownloadThumbnailJob::dispatch($this->asset, $assemblyId)->delay(now()->addSeconds(2));
        }
    }

    protected function createThumbnailJob(CloudConvertDriver $driver): ?string
    {
        $job = (new Job())
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

        $job = $driver->api()->jobs()->create($job);

        $uploadTask = $job->getTasks()?->whereName('upload-task')[0];

        $driver->api()->tasks()->upload($uploadTask, fopen($this->asset->resolvedPath(), 'r'), $this->asset->basename());

        // ray($response, $response->data ?? [])->label('CloudConvert response');

        return $job->getId();
    }

    protected function getThumbnailParams(CloudConvertDriver $driver): array
    {
        $defaults = [
            'output_format' => 'webp',
            'width' => 500,
            'height' => 500,
            'fit' => 'max',
        ];

        $options = [];

        if ($this->asset->isVideo()) {
            $options = [
                'width' => 400,
                'height' => 400,
                'output_format' => 'webp',
                'clip_framerate' => 8,
            ];
        }

        if ($this->asset->guessedExtensionIsOneOf(['txt', 'rtf', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'pages', 'key', 'numbers'])) {
            $options = [
                'output_format' => 'png',
                'width' => 1000,
                'height' => 1000,
            ];
        }

        return array_merge($defaults, $options);
    }
}
