<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Generation Driver
    |--------------------------------------------------------------------------
    |
    | Choose the external service to use for generating asset thumbnails.
    | Set to `null` to disable automatic generation of thumbnails.
    |
    | Each driver may require specific configuration options, which can be set
    | in the sections below. Consult the readme for details.
    |
    | Available drivers:
    | - \Daun\StatamicAssetThumbnails\Drivers\TransloaditDriver::class
    | - `null` (disable generation of new thumbnails)
    |
    */

    'driver' => env('ASSET_THUMBNAILS_DRIVER', \Daun\StatamicAssetThumbnails\Drivers\TransloaditDriver::class),

    /*
    |--------------------------------------------------------------------------
    | Transloadit Driver
    |--------------------------------------------------------------------------
    |
    | Settings for using the Transloadit service for thumbnail generation.
    |
    */

    'transloadit' => [

        'auth_key' => env('TRANSLOADIT_AUTH_KEY'),

        'auth_secret' => env('TRANSLOADIT_AUTH_SECRET'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Thumbnails are cached in the `storage` folder and streamed from a
    | controller to simplify setup. You can serve thumbnails much faster by
    | creating a custom disk inside the `public` folder and setting it here.
    |
    */

    'cache' => [

        'disk' => null,

    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Define the queue to use for processing thumbnail generation jobs.
    | Leave empty to use the default connection and queue of your app.
    |
    */

    'queue' => [

        'connection' => null,

        'queue' => null,

    ],

];
