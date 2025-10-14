<?php

namespace Daun\StatamicAssetThumbnails;

use Daun\StatamicAssetThumbnails\Commands\ClearCommand;
use Daun\StatamicAssetThumbnails\Listeners\ClearThumbnail;
use Daun\StatamicAssetThumbnails\Listeners\GenerateThumbnail;
use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Statamic\Events\AssetDeleted;
use Statamic\Events\AssetReuploaded;
use Statamic\Events\AssetUploaded;
use Statamic\Http\Resources\CP\Assets\Asset as AssetResource;
use Statamic\Http\Resources\CP\Assets\FolderAsset as FolderAssetResource;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        ClearCommand::class,
    ];

    protected $listen = [
        AssetUploaded::class => [GenerateThumbnail::class],
        AssetReuploaded::class => [GenerateThumbnail::class],
        AssetDeleted::class => [ClearThumbnail::class],
    ];

    public function register()
    {
        $this->app->singleton(ThumbnailService::class);
    }

    public function bootAddon(): void
    {
        $this->autoPublishConfig();
        $this->createThumbnailHooks();
    }

    protected function bootConfig()
    {
        $directory = $this->getAddon()->directory();
        $origin = "{$directory}config/statamic/asset-thumbnails.php";

        $this->mergeConfigFrom($origin, 'statamic.asset-thumbnails');

        $this->publishes([
            $origin => config_path('statamic/asset-thumbnails.php'),
        ], 'statamic-asset-thumbnails-config');

        return $this;
    }

    protected function autoPublishConfig(): self
    {
        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish', ['--tag' => 'statamic-asset-thumbnails-config']);
        });

        return $this;
    }

    protected function createThumbnailHooks()
    {
        // Asset resource = opened in asset editor or linked in asset field
        AssetResource::hook('asset', function ($payload, $next) {
            $payload->data->thumbnail ??= app(ThumbnailService::class)->url($this->resource);
            $payload->data->preview ??= app(ThumbnailService::class)->url($this->resource);

            return $next($payload);
        });

        // Folder asset resource = listed in asset browser
        FolderAssetResource::hook('asset', function ($payload, $next) {
            $payload->data->thumbnail ??= app(ThumbnailService::class)->url($this->resource);

            return $next($payload);
        });
    }

    public function provides(): array
    {
        return [
            ThumbnailService::class,
        ];
    }
}
