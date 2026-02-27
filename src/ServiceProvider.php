<?php

namespace Daun\StatamicAssetThumbnails;

use Daun\StatamicAssetThumbnails\Commands\ClearCommand;
use Daun\StatamicAssetThumbnails\Drivers\CloudConvertDriver;
use Daun\StatamicAssetThumbnails\Drivers\DriverInterface;
use Daun\StatamicAssetThumbnails\Drivers\NullDriver;
use Daun\StatamicAssetThumbnails\Drivers\TransloaditDriver;
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

        $this->app->singleton(TransloaditDriver::class, function () {
            return new TransloaditDriver(config('statamic.asset-thumbnails.transloadit', []));
        });

        $this->app->singleton(CloudConvertDriver::class, function () {
            return new CloudConvertDriver(config('statamic.asset-thumbnails.cloudconvert', []));
        });

        $this->app->singleton(NullDriver::class, function () {
            return new NullDriver();
        });

        $this->app->singleton(DriverInterface::class, function () {
            $driver = config('statamic.asset-thumbnails.driver', 'transloadit');

            return match ($driver) {
                'transloadit', TransloaditDriver::class => app(TransloaditDriver::class),
                'cloudconvert', CloudConvertDriver::class => app(CloudConvertDriver::class),
                null, 'null', NullDriver::class => app(NullDriver::class),
                default => throw new \RuntimeException("Unsupported asset thumbnail driver [$driver]."),
            };
        });
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
            $payload->data->thumbnail ??= $thumbnail = app(ThumbnailService::class)->url($this->resource);
            $payload->data->preview ??= $thumbnail ?? null;

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
