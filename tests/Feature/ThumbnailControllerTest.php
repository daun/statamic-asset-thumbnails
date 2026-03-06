<?php

use Facades\Statamic\Version;
use Illuminate\Http\UploadedFile;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\User;

beforeEach(function () {
    if (! config('app.key')) {
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    }

    Version::shouldReceive('get')->andReturn('3.4.0');

    config(['filesystems.disks.test' => [
        'driver' => 'local',
        'root' => $this->getTempDirectory(),
    ]]);

    $this->image = AssetContainer::make('test')->disk('test')->save()
        ->makeAsset('one.png')
        ->upload(UploadedFile::fake()->image('one.png'));
});

it('denies access when logged out', function () {
    $this->get('/cp/addons/asset-thumbnails/'.base64_encode('test::one.png'))
        ->assertRedirect('/cp/auth/login');
});

it('denies access without permission to view asset', function () {
    $this->setTestRoles(['test' => ['access cp']]);
    $user = User::make()->assignRole('test')->save();

    $this->actingAs($user)
        ->get('/cp/addons/asset-thumbnails/'.base64_encode('test::one.png'))
        ->assertRedirect('/cp');
});

it('404s when the asset doesnt exist', function () {
    $this->setTestRoles(['test' => ['access cp', 'view test assets']]);
    $user = User::make()->assignRole('test')->save();

    $this->actingAs($user)
        ->get('/cp/addons/asset-thumbnails/'.base64_encode('test::unknown.png'))
        ->assertNotFound();
});

it('redirects to placeholder at root url', function () {
    config(['app.asset_url' => null, 'app.url' => 'http://localhost']);

    $this->setTestRoles(['test' => ['access cp', 'view test assets']]);
    $user = User::make()->assignRole('test')->save();

    $this->actingAs($user)
        ->get('/cp/addons/asset-thumbnails/'.base64_encode('test::one.png'))
        ->assertRedirect('http://localhost/vendor/statamic-asset-thumbnails/icons/placeholder.svg');
});

it('redirects to placeholder at custom asset url', function () {
    app('url')->useAssetOrigin('https://cdn.example.com/assets');

    $this->setTestRoles(['test' => ['access cp', 'view test assets']]);
    $user = User::make()->assignRole('test')->save();

    $this->actingAs($user)
        ->get('/cp/addons/asset-thumbnails/'.base64_encode('test::one.png'))
        ->assertRedirect('https://cdn.example.com/assets/vendor/statamic-asset-thumbnails/icons/placeholder.svg');
});
