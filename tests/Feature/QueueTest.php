<?php

use Daun\StatamicAssetThumbnails\Support\Queue;

test('returns queue connection', function () {
    expect(Queue::connection())->toEqual(config('queue.default'));

    config(['statamic-asset-thumbnails.queue.connection' => 'mux-connection']);
    expect(Queue::connection())->toEqual('mux-connection');
});

test('returns queue name', function () {
    expect(Queue::queue())->toEqual('default');

    config(['statamic-asset-thumbnails.queue.queue' => 'mux-queue']);
    expect(Queue::queue())->toEqual('mux-queue');
});
