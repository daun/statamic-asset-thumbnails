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

    protected $publishables = [
        __DIR__.'/../resources/icons' => 'icons',
    ];

    public function register()
    {
        $this->app->singleton(ThumbnailService::class);

        $this->app->singleton(DriverInterface::class, function () {
            $driver = config('statamic.asset-thumbnails.driver', 'transloadit');

            return match ($driver) {
                'transloadit', TransloaditDriver::class => $this->resolveDriver('transloadit', TransloaditDriver::class, 'transloadit\\Transloadit', 'transloadit/php-sdk'),
                'cloudconvert', CloudConvertDriver::class => $this->resolveDriver('cloudconvert', CloudConvertDriver::class, 'CloudConvert\\CloudConvert', 'cloudconvert/cloudconvert-php'),
                null, 'null', NullDriver::class => new NullDriver,
                default => throw new \RuntimeException("Unsupported asset thumbnail driver [$driver]."),
            };
        });
    }

    protected function resolveDriver(string $name, string $driverClass, string $sdkClass, string $package): DriverInterface
    {
        if (! class_exists($sdkClass)) {
            throw new \RuntimeException(
                "The [{$name}] driver requires the [{$package}] package. Install it with: composer require {$package}"
            );
        }

        return new $driverClass(config("statamic.asset-thumbnails.{$name}", []));
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
            $payload->data->thumbnail ??= $thumbnail = app(ThumbnailService::class)->url($this->resource); // @phpstan-ignore property.notFound
            $payload->data->preview ??= $thumbnail ?? null;

            return $next($payload);
        });

        // Folder asset resource = listed in asset browser
        FolderAssetResource::hook('asset', function ($payload, $next) {
            $payload->data->thumbnail ??= app(ThumbnailService::class)->url($this->resource); // @phpstan-ignore property.notFound

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
