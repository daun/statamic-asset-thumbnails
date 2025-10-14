<?php

namespace Daun\StatamicAssetThumbnails\Services;

use Daun\StatamicAssetThumbnails\Drivers\DriverInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Statamic\Assets\Asset;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ThumbnailService
{
    protected ?DriverInterface $driver = null;

    protected FilesystemAdapter $disk;

    public function __construct()
    {
        $this->disk = $this->customCacheDisk() ?? $this->defaultCacheDisk();
    }

    public function enabled(): bool
    {
        return (bool) config('statamic-asset-thumbnails.driver');
    }

    public function driver(): DriverInterface
    {
        if ($this->driver) {
            return $this->driver;
        }

        $class = config('statamic-asset-thumbnails.driver');
        if (! class_exists($class)) {
            throw new \RuntimeException("Thumbnail driver class [$class] does not exist.");
        }

        $instance = app()->make($class);
        if (! $instance instanceof DriverInterface) {
            throw new \RuntimeException("Thumbnail driver class [$class] must implement DriverInterface.");
        }

        return $this->driver = $instance;
    }

    public function url(Asset $asset): ?string
    {
        $path = $this->get($asset);

        if ($path && $this->diskIsPublic()) {
            return $this->disk->url($path);
        }

        if ($path || $this->canGenerate($asset)) {
            return cp_route('custom.thumbnails.show', base64_encode($asset->id()));
        }

        return null;
    }

    public function exists(Asset $asset): bool
    {
        $dir = $this->cacheDir($asset);
        $files = $this->disk->files($dir);

        return count($files) > 0;
    }

    public function generate(Asset $asset): void
    {
        if (! $this->canGenerate($asset)) {
            return;
        }

        if ($this->exists($asset) || $this->isGenerating($asset)) {
            return;
        }

        Cache::put($this->mutex($asset), true, now()->addMinutes(2));

        $this->driver()->generate($asset);
    }

    public function canGenerate(Asset $asset): bool
    {
        return $this->enabled()
            && $this->driver()
            && $this->driver()->supports($asset)
            && file_exists($asset->resolvedPath());
    }

    public function isGenerating(Asset $asset): bool
    {
        if ($this->exists($asset)) {
            return false;
        }

        return (bool) Cache::get($this->mutex($asset));
    }

    public function waitUntilGenerated(Asset $asset, int $max = 15): void
    {
        $cur = 0;
        while ($this->isGenerating($asset) && $cur < $max) {
            sleep(1);
            $cur++;
        }
    }

    public function get(Asset $asset): ?string
    {
        $dir = $this->cacheDir($asset);
        $files = $this->disk->files($dir);

        return $files[0] ?? null;
    }

    public function read(Asset $asset): ?string
    {
        return ($file = $this->get($asset))
            ? $this->disk->get($file)
            : null;
    }

    public function response(Asset $asset): ?StreamedResponse
    {
        return ($file = $this->get($asset))
            ? $this->disk->response($file)
            : null;
    }

    public function put(Asset $asset, string $contents, string $filename): string|bool
    {
        $dir = $this->cacheDir($asset);
        $path = $dir.'/'.$filename;

        Cache::forget($this->mutex($asset));

        return $this->disk->put($path, $contents);
    }

    public function remove(Asset $asset): bool
    {
        $dir = $this->cacheDir($asset);

        Cache::forget($this->mutex($asset));

        return $this->disk->directoryExists($dir)
            ? $this->disk->deleteDirectory($dir)
            : false;
    }

    public function disk(): FilesystemAdapter
    {
        return $this->disk;
    }

    protected function diskIsPublic(): bool
    {
        return ($this->disk->getConfig()['visibility'] ?? null) === 'public';
    }

    protected function customCacheDisk(): ?FilesystemAdapter
    {
        return ($disk = config('statamic-asset-thumbnails.cache.disk'))
            ? Storage::disk($disk)
            : null;
    }

    protected function defaultCacheDisk(): FilesystemAdapter
    {
        return Storage::createLocalDriver([
            'driver' => 'local',
            'root' => storage_path('statamic/addons/asset-thumbnails'),
            'visibility' => 'private',
            'permissions' => [
                'file' => [
                    'public' => 0644,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0700,
                ],
            ],
        ], 'asset_thumbnails_default');
    }

    public function cacheDir(Asset $asset): string
    {
        return md5($asset->id());
    }

    public function mutex(Asset $asset): ?string
    {
        return 'thumbnailservice.generating.'.md5($asset->id());
    }
}
