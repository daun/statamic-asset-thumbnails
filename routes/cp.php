<?php

use Daun\StatamicAssetThumbnails\Http\Controllers\Cp\ThumbnailController;
use Illuminate\Support\Facades\Route;

Route::get('/addons/daun/thumbnails/{id}', [ThumbnailController::class, 'show'])
    ->where('id', '.*')
    ->name('addons.daun.thumbnails.show');
