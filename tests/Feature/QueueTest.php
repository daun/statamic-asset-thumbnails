<?php

use Daun\StatamicAssetThumbnails\Support\Queue;

test('returns queue connection', function () {
    expect(Queue::connection())->toEqual(config('queue.default'));

    config(['statamic.asset-thumbnails.queue.connection' => 'custom-connection']);
    expect(Queue::connection())->toEqual('custom-connection');
});

test('returns queue name', function () {
    expect(Queue::queue())->toEqual('default');

    config(['statamic.asset-thumbnails.queue.queue' => 'custom-queue']);
    expect(Queue::queue())->toEqual('custom-queue');
});
