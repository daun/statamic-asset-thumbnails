<?php

namespace Daun\StatamicAssetThumbnails\Drivers\Transloadit;

use Daun\StatamicAssetThumbnails\Drivers\TransloaditDriver;
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

    public function handle(ThumbnailService $service, TransloaditDriver $driver): void
    {
        // Abort if a thumbnail was generated in the meantime
        if ($service->exists($this->asset)) {
            return;
        }

        $assemblyId = $this->createFilePreviewAssembly($driver);
        if ($assemblyId) {
            DownloadThumbnailJob::dispatch($this->asset, $assemblyId)->delay(now()->addSeconds(2));
        }
    }

    protected function createFilePreviewAssembly(TransloaditDriver $driver): ?string
    {
        $response = $driver->api()->createAssembly([
            'files' => [$this->asset->resolvedPath()],
            'params' => [
                'steps' => [
                    'preview' => [
                        'robot' => '/file/preview',
                        ...$this->getFilePreviewRobotOptions($driver),
                    ],
                ],
            ],
        ]);

        // ray($response, $response->data ?? [])->label('Transloadit response');

        return $response->data['assembly_id'] ?? null;
    }

    protected function getFilePreviewRobotOptions(TransloaditDriver $driver): array
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

        if ($this->asset->isAudio()) {
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

        if ($this->asset->isVideo()) {
            $options = [
                'width' => 400,
                'height' => 400,
                'clip_format' => 'webp',
                'clip_framerate' => 8,
            ];
        }

        if ($this->asset->guessedExtensionIsOneOf(['txt', 'rtf', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'pages', 'key', 'numbers'])) {
            $options = [
                'format' => 'png',
                'width' => 1000,
                'height' => 1000,
            ];
        }

        return array_merge($defaults, $options);
    }
}
