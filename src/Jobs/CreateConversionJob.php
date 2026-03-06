<?php

namespace Daun\StatamicAssetThumbnails\Jobs;

use Daun\StatamicAssetThumbnails\Drivers\DriverInterface;
use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Daun\StatamicAssetThumbnails\Support\Queue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Statamic\Assets\Asset;

class CreateConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Asset $asset)
    {
        $this->connection = Queue::connection();
        $this->queue = Queue::queue();
    }

    public function handle(ThumbnailService $service, DriverInterface $driver): void
    {
        // Abort if a thumbnail was generated in the meantime
        if ($service->exists($this->asset)) {
            return;
        }

        $conversionId = $driver->createConversion($this->asset);
        if ($conversionId) {
            FetchConversionJob::dispatch($this->asset, $conversionId)->delay(now()->addSeconds(2));
        }
    }
}
