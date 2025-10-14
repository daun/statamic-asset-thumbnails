<?php

namespace Daun\StatamicAssetThumbnails;

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
    // protected $commands = [
    //     Commands\GenerateCommand::class,
    //     Commands\ClearCommand::class,
    // ];

    protected $listen = [
        AssetUploaded::class => [
            Listeners\GenerateThumbnail::class,
        ],
        AssetReuploaded::class => [
            Listeners\GenerateThumbnail::class,
        ],
        AssetDeleted::class => [
            Listeners\ClearThumbnail::class,
        ],
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

    protected function autoPublishConfig(): self
    {
        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish', ['--tag' => 'statamic-asset-thumbnails-config']);
        });

        return $this;
    }

    protected function createThumbnailHooks()
    {
        // Asset resource = asset opened in editor modal
        AssetResource::hook('asset', function ($payload, $next) {
            $payload->data->preview ??= app(ThumbnailService::class)->url($this->resource);

            return $next($payload);
        });

        // Folder asset resource = asset listed in asset browser or asset field
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
