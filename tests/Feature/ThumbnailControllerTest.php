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

it('redirects to placeholder', function () {
    $this->setTestRoles(['test' => ['access cp', 'view test assets']]);
    $user = User::make()->assignRole('test')->save();

    $this->actingAs($user)
        ->get('/cp/addons/asset-thumbnails/'.base64_encode('test::one.png'))
        ->assertRedirect('https://placehold.co/600?text=Generating\nPreview&font=raleway');
});
