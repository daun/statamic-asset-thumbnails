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

class DownloadThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $maxAttempts = 5;

    public function __construct(protected Asset $asset, protected string $jobId, protected int $attempt = 1)
    {
        $this->connection = Queue::connection();
        $this->queue = Queue::queue();
    }

    public function handle(ThumbnailService $service, CloudConvertDriver $driver): void
    {
        // No attempts left, abort
        if ($this->attempt > $this->maxAttempts) {
            return;
        }

        // Abort if a thumbnail was generated in the meantime
        if ($service->exists($this->asset)) {
            return;
        }

        $job = $this->getJobInfo($driver);

        // Job not found or still processing, retry
        if (! $job || $this->isJobProcessing($job)) {
            $this->retryJob();

            return;
        }

        // Job was canceled, do not retry
        if ($this->isJobCanceled($job)) {
            return;
        }

        // Job completed, try to get the result and save the thumbnail
        if ($this->isJobCompleted($job)) {
            if ($result = $this->getJobResult($job)) {
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

        static::dispatch($this->asset, $this->jobId, $this->attempt + 1)->delay(now()->addSeconds($wait));
    }

    protected function getJobInfo(CloudConvertDriver $driver): ?Job
    {
        try {
            $job = $driver->api()->jobs()->get($this->jobId);
            ray($job->getPayload())->label('CloudConvert payload');
        } catch (\Throwable $th) {
            ray($th)->label('CloudConvert error');
        }

        return $job ?? null;
    }

    protected function isJobCompleted(Job $job): bool
    {
        return in_array($job->getStatus(), [Job::STATUS_FINISHED]);
    }

    protected function isJobCanceled(Job $job): bool
    {
        return in_array($job->getStatus(), [Job::STATUS_ERROR]);
    }

    protected function isJobProcessing(Job $job): bool
    {
        return in_array($job->getStatus(), [Job::STATUS_PROCESSING, Job::STATUS_WATING]);
    }

    protected function getJobResult(Job $job): ?array
    {
        $exports = $job->getTasks()?->operation('export/url')?->status(Task::STATUS_FINISHED) ?? [];

        if (count($exports)) {
            $result = $exports[0]->getResult()?->files[0] ?? null;
            $url = $result->url ?? null;
            $filename = $result->filename ?? null;
            if ($url && $filename) {
                return [$url, $filename];
            }
        }

        return null;
    }
}
