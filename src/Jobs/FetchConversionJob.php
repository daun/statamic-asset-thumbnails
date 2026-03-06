<?php

namespace Daun\StatamicAssetThumbnails\Jobs;

use Daun\StatamicAssetThumbnails\Drivers\ConversionResult;
use Daun\StatamicAssetThumbnails\Drivers\DriverInterface;
use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Daun\StatamicAssetThumbnails\Support\Queue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Statamic\Assets\Asset;

class FetchConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $maxAttempts = 5;

    public function __construct(
        protected Asset $asset,
        protected string $conversionId,
        protected int $attempt = 1,
    ) {
        $this->connection = Queue::connection();
        $this->queue = Queue::queue();
    }

    public function handle(ThumbnailService $service, DriverInterface $driver): void
    {
        // No attempts left, abort
        if ($this->attempt > $this->maxAttempts) {
            return;
        }

        // Abort if a thumbnail was generated in the meantime
        if ($service->exists($this->asset)) {
            return;
        }

        $result = $driver->fetchResult($this->conversionId);

        // Still processing → retry
        if ($result === null) {
            $this->retry();

            return;
        }

        // Failed → don't retry
        if ($result === false) {
            return;
        }

        // Completed → download and save
        if ($result instanceof ConversionResult) {
            if ($contents = $service->download($result->url)) {
                $service->put($this->asset, $contents, $result->filename);
            }
        }
    }

    protected function retry(): void
    {
        // Very simple backoff strategy: wait 0, 1, 2, 3... seconds
        $wait = $this->attempt - 1;

        // Sync queue cannot delay jobs, so we need to sleep manually
        if (Queue::isSync()) {
            sleep($wait);
        }

        static::dispatch($this->asset, $this->conversionId, $this->attempt + 1)->delay(now()->addSeconds($wait));
    }
}
