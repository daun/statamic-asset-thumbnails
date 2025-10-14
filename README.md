# Statamic Advanced Asset Thumbnails

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daun/statamic-asset-thumbnails.svg)](https://packagist.org/packages/daun/statamic-asset-thumbnails)

**Generate asset thumbnails for exotic file formats like videos, raw photos, audio files and documents.**

![Example asset thumbnails](art/asset-thumbnails.gif)

> [!NOTE]
> Requires Statamic 6, which is currently in alpha, so the implementation might change and things
> could break at any time. Please report any issues you encounter.

## How It Works

The addon generates control panel thumbnails for non-image file by integrating with a
third-party file conversion service and caching the resulting image preview. Currently it supports
the following service. Support for CloudConvert is planned.

- [Transloadit](https://transloadit.com/): 9$/month, free tier available

## Quick Start

1. Install using `composer require daun/statamic-asset-thumbnails`
2. Publish the config using `php artisan vendor:publish --tag=statamic-asset-thumbnails-config`
3. Configure the driver and credentials in `config/statamic-asset-thumbnails.php`

## License

This addon is paid software with an open-source codebase. To use it in production, you'll need
to [buy a license](https://statamic.com/addons/daun/asset-thumbnails) from the Statamic Marketplace.
