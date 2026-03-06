<?php

namespace Tests\Concerns;

use Daun\StatamicAssetThumbnails\Drivers\DriverInterface;
use Daun\StatamicAssetThumbnails\Drivers\TransloaditDriver;
use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use transloadit\Transloadit;
use transloadit\TransloaditResponse;

trait FakesTransloadit
{
    protected TransloaditDriver $transloaditDriver;

    /**
     * @var \Mockery\MockInterface&Transloadit
     */
    protected $mockTransloaditApi;

    /**
     * @var list<array{method: string, args: array}>
     */
    protected array $transloaditCalls = [];

    /**
     * Set up a TransloaditDriver with a mocked Transloadit SDK.
     *
     * Since the Transloadit PHP SDK uses raw cURL with no HTTP client
     * injection point, we mock the Transloadit class itself using Mockery,
     * recording all calls and returning pre-configured TransloaditResponse
     * objects — similar to how FakesCloudConvert uses a PSR-18 MockHttpClient.
     */
    protected function setUpTransloaditFake(): void
    {
        $this->transloaditCalls = [];
        $this->mockTransloaditApi = \Mockery::mock(Transloadit::class);

        // Create driver and inject the mocked API client
        $this->transloaditDriver = new TransloaditDriver([
            'auth_key' => 'test-key',
            'auth_secret' => 'test-secret',
        ]);

        $reflector = new \ReflectionProperty(TransloaditDriver::class, 'api');
        $reflector->setValue($this->transloaditDriver, $this->mockTransloaditApi);

        // Bind in container so jobs resolve our instrumented driver
        $this->app->instance(TransloaditDriver::class, $this->transloaditDriver);
        $this->app->instance(DriverInterface::class, $this->transloaditDriver);

        // Clear thumbnail cache to prevent stale data from interfering
        $this->clearThumbnailCache();
    }

    /**
     * Set up the mock to return the given response from createAssembly().
     */
    protected function mockCreateAssembly(TransloaditResponse $response): void
    {
        $this->mockTransloaditApi
            ->shouldReceive('createAssembly')
            ->once()
            ->andReturnUsing(function (array $options) use ($response) {
                $this->transloaditCalls[] = ['method' => 'createAssembly', 'args' => $options];

                return $response;
            });
    }

    /**
     * Set up the mock to return the given response from getAssembly().
     */
    protected function mockGetAssembly(TransloaditResponse $response, ?string $expectedId = null): void
    {
        $expectation = $this->mockTransloaditApi
            ->shouldReceive('getAssembly')
            ->once();

        if ($expectedId !== null) {
            $expectation->with($expectedId);
        }

        $expectation->andReturnUsing(function (string $id) use ($response) {
            $this->transloaditCalls[] = ['method' => 'getAssembly', 'args' => ['assembly_id' => $id]];

            return $response;
        });
    }

    /**
     * Assert that createAssembly was called.
     */
    protected function assertCreateAssemblyCalled(): void
    {
        $found = collect($this->transloaditCalls)->contains(
            fn ($call) => $call['method'] === 'createAssembly'
        );

        \PHPUnit\Framework\Assert::assertTrue($found, 'Expected createAssembly to be called, but it was not.');
    }

    /**
     * Assert that getAssembly was called with the given ID.
     */
    protected function assertGetAssemblyCalled(?string $assemblyId = null): void
    {
        $found = collect($this->transloaditCalls)->contains(function ($call) use ($assemblyId) {
            if ($call['method'] !== 'getAssembly') {
                return false;
            }

            if ($assemblyId !== null) {
                return ($call['args']['assembly_id'] ?? null) === $assemblyId;
            }

            return true;
        });

        $msg = $assemblyId
            ? "Expected getAssembly to be called with [{$assemblyId}], but it was not."
            : 'Expected getAssembly to be called, but it was not.';

        \PHPUnit\Framework\Assert::assertTrue($found, $msg);
    }

    /**
     * Get the options passed to the last createAssembly call.
     */
    protected function getCreateAssemblyOptions(): ?array
    {
        $call = collect($this->transloaditCalls)
            ->last(fn ($call) => $call['method'] === 'createAssembly');

        return $call['args'] ?? null;
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
