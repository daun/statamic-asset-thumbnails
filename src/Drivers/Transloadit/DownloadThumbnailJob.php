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

class DownloadThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $maxAttempts = 5;

    public function __construct(protected Asset $asset, protected string $assemblyId, protected int $attempt = 1)
    {
        $this->connection = Queue::connection();
        $this->queue = Queue::queue();
    }

    public function handle(ThumbnailService $service, TransloaditDriver $driver): void
    {
        // No attempts left, abort
        if ($this->attempt > $this->maxAttempts) {
            return;
        }

        // Abort if a thumbnail was generated in the meantime
        if ($service->exists($this->asset)) {
            return;
        }

        $assembly = $this->getAssemblyData($driver);

        // Assembly not found or still processing, retry
        if (! $assembly || $this->isAssemblyProcessing($assembly)) {
            $this->retryJob();

            return;
        }

        // Assembly was canceled, do not retry
        if ($this->isAssemblyCanceled($assembly)) {
            return;
        }

        // Assembly completed, try to get the result and save the thumbnail
        if ($this->isAssemblyCompleted($assembly)) {
            if ($result = $this->getAssemblyResult($assembly)) {
                [$downloadUrl, $filename] = $result;
                if ($contents = file_get_contents($downloadUrl)) {
                    $service->put($this->asset, $contents, $filename);
                }
            }
        }
    }

    protected function retryJob(): void
    {
        // Very simple backoff strategy: wait 0, 1, 2, 3... seconds
        $wait = $this->attempt - 1;

        // Sync queue cannot delay jobs, so we need to sleep manually
        if (Queue::isSync()) {
            sleep($wait);
        }

        static::dispatch($this->asset, $this->assemblyId, $this->attempt + 1)->delay(now()->addSeconds($wait));
    }

    protected function getAssemblyData(TransloaditDriver $driver): ?array
    {
        $response = $driver->api()->getAssembly($this->assemblyId);
        // ray($response->data ?? [])->label('Transloadit response');

        return ($response->data['ok'] ?? null) ? $response->data : null;
    }

    protected function isAssemblyCompleted(array $assembly): bool
    {
        return in_array($assembly['ok'] ?? null, ['ASSEMBLY_COMPLETED']);
    }

    protected function isAssemblyCanceled(array $assembly): bool
    {
        return in_array($assembly['ok'] ?? null, ['ASSEMBLY_CANCELED', 'REQUEST_ABORTED']);
    }

    protected function isAssemblyProcessing(array $assembly): bool
    {
        return in_array($assembly['ok'] ?? null, ['ASSEMBLY_EXECUTING', 'ASSEMBLY_UPLOADING']);
    }

    protected function getAssemblyResult(array $assembly): ?array
    {
        $result = $assembly['results']['preview'][0] ?? null;
        if ($result) {
            $url = $result['ssl_url'] ?? $result['url'] ?? null;
            $filename = $result['name'] ?? null;
            if ($url && $filename) {
                return [$url, $filename];
            }
        }

        return null;
    }
}
