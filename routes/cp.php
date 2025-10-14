<?php

use Daun\StatamicAssetThumbnails\Http\Controllers\Cp\ThumbnailController;
use Illuminate\Support\Facades\Route;

Route::get('/custom/thumbnails/{id}', [ThumbnailController::class, 'show'])->where('id', '.*')->name('custom.thumbnails.show');
