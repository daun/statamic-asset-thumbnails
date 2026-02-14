<?php

use Daun\StatamicAssetThumbnails\Http\Controllers\Cp\ThumbnailController;
use Illuminate\Support\Facades\Route;

Route::get('/addons/asset-thumbnails/{id}', [ThumbnailController::class, 'show'])
    ->where('id', '.*')
    ->name('addons.asset-thumbnails.show');
