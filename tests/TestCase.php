<?php

namespace Tests;

use Daun\StatamicAssetThumbnails\ServiceProvider as AddonServiceProvider;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\Concerns\DealsWithAssets;
use Tests\Concerns\FakesRoles;
use Tests\Concerns\ResolvesStatamicConfig;

abstract class TestCase extends AddonTestCase
{
    use DealsWithAssets;
    use FakesRoles;
    use InteractsWithViews;
    use PreventsSavingStacheItemsToDisk;
    use ResolvesStatamicConfig;

    protected string $addonServiceProvider = AddonServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAssetTest();
    }

    protected function tearDown(): void
    {
        $this->tearDownAssetTest();

        parent::tearDown();
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        // Custom view directory
        $app['config']->set('view.paths', [fixtures_path('views')]);

        // Pull in statamic default config
        $this->resolveStatamicConfiguration($app);

        // Rewrite content paths to use our test fixtures
        $this->resolveStacheStores($app);

        // Set user repository to default flat file system
        $app['config']->set('statamic.users.repository', 'file');

        // Assume pro edition for our tests
        $app['config']->set('statamic.editions.pro', true);

        // Set specific config for asset tests
        $this->resolveApplicationConfigurationForAssetTest($app);

        // Set specific stache stores for asset tests
        $this->resolveStacheStoresForAssetTest($app);
    }
}
