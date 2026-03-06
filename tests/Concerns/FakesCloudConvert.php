<?php

namespace Tests\Concerns;

use CloudConvert\CloudConvert;
use Daun\StatamicAssetThumbnails\Drivers\CloudConvertDriver;
use Daun\StatamicAssetThumbnails\Drivers\DriverInterface;
use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Tests\Support\MockHttpClient;

trait FakesCloudConvert
{
    protected MockHttpClient $mockHttpClient;

    protected CloudConvertDriver $cloudConvertDriver;

    /**
     * Set up a CloudConvertDriver with a mock HTTP client injected into the SDK.
     *
     * The mock HTTP client records all requests and returns pre-configured
     * responses, functioning like Saloon's recording/playback or Http::fake().
     */
    protected function setUpCloudConvertFake(): void
    {
        $this->mockHttpClient = new MockHttpClient;

        $api = new CloudConvert([
            'api_key' => 'test-api-key',
            'http_client' => $this->mockHttpClient,
        ]);

        // Create driver with a real CloudConvert instance but fake HTTP transport
        $this->cloudConvertDriver = new CloudConvertDriver(['api_key' => 'test-api-key']);

        // Replace the internal API client with our instrumented one
        $reflector = new \ReflectionProperty(CloudConvertDriver::class, 'api');
        $reflector->setValue($this->cloudConvertDriver, $api);

        // Bind in container so jobs resolve our instrumented driver
        $this->app->instance(CloudConvertDriver::class, $this->cloudConvertDriver);
        $this->app->instance(DriverInterface::class, $this->cloudConvertDriver);

        // Clear thumbnail cache to prevent stale data from interfering
        $this->clearThumbnailCache();
    }

    /**
     * Clear the thumbnail cache disk to ensure a clean state for each test.
     */
    protected function clearThumbnailCache(): void
    {
        $service = app(ThumbnailService::class);
        $disk = $service->disk();
        foreach ($disk->directories() as $dir) {
            $disk->deleteDirectory($dir);
        }
    }
}
