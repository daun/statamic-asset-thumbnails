# Statamic Advanced Asset Thumbnails

This repository is a Statamic (Laravel) addon that generates and serves Control Panel thumbnails for non-image (“exotic”) asset types like videos, audio files, raw photos, documents and design files.

Main goals:
- Make unsupported asset types feel first-class in the Statamic CP by providing thumbnails for almost any file.
- Offload thumbnail generation to an external conversion service, then cache results for fast subsequent loads.
- Keep setup simple by default (works out of the box) while allowing opt-in performance tradeoffs (public cache disk).

# Project Guidelines

## Code Style
- PHP (Laravel-style, PSR-12). Format with Pint: `composer format`; check with `composer lint`.
- Prefer typed properties + constructor property promotion (see `src/Services/ThumbnailService.php`, `src/Http/Controllers/Cp/ThumbnailController.php`).
- Prefer dependency injection for services in controllers/listeners/jobs; use facades/helpers where idiomatic (`Cache`, `Storage`, `abort_unless`, `redirect`).
- Queue jobs follow standard Laravel traits (`ShouldQueue`, `Dispatchable`, `Queueable`, `SerializesModels`) and set connection/queue in the constructor (see `src/Drivers/Transloadit/*.php`).

## Architecture
- Statamic integration:
  - Event listeners are wired in `src/ServiceProvider.php`: upload/reupload → generate, delete → clear (`src/Listeners/*`).
  - CP asset payloads are modified via resource hooks in `src/ServiceProvider.php` to inject `thumbnail`/`preview` URLs.
- Thumbnail orchestration lives in `src/Services/ThumbnailService.php`:
  - Chooses cache disk: custom disk from `statamic-asset-thumbnails.cache.disk` or an internal private local disk rooted at `storage/statamic/addons/asset-thumbnails`.
  - Uses a short-lived cache mutex (`thumbnailservice.generating.{md5(asset_id)}`) to avoid duplicate work.
  - `url()` returns either a direct disk URL (when disk visibility is `public`) or a CP route URL.
- Serving thumbnails:
  - CP route `custom.thumbnails.show` is registered in `routes/cp.php` and streams via `src/Http/Controllers/Cp/ThumbnailController.php`.
- Drivers:
  - Add new generators by implementing `src/Drivers/DriverInterface.php` (usually extend `src/Drivers/AbstractDriver.php`).
  - Current implementation `src/Drivers/TransloaditDriver.php` dispatches `GenerateThumbnailJob` → `DownloadThumbnailJob` (polling + saving via `ThumbnailService::put()`).

## Build and Test
- Install: `composer install`
- Tests (Pest + Orchestra Testbench): `composer test`
- Coverage: `composer test:coverage` (or CI clover: `composer test:ci`)
- Static analysis (PHPStan/Larastan): `composer analyse`
- Formatting: `composer format` (check only: `composer lint`)
- Clear thumbnail cache: `php please thumbnails:clear`

## Project Conventions
- Config lives in `config/statamic/asset-thumbnails.php` and is accessed via `statamic-asset-thumbnails.*` keys.
- Use `cp_route(...)` (not `route(...)`) for CP routes.
- The CP thumbnail route id is `base64(asset_id)`; treat it as an identifier, not a secret.
- Queue configuration is addon-specific: `statamic-asset-thumbnails.queue.connection` / `.queue` (resolved via `src/Support/Queue.php`).
  - Sync queue cannot delay jobs; `DownloadThumbnailJob` handles this with a manual `sleep()` backoff.

## Integration Points
- Transloadit:
  - Credentials: `TRANSLOADIT_AUTH_KEY`, `TRANSLOADIT_AUTH_SECRET`
  - Network calls happen inside queued jobs (`src/Drivers/Transloadit/DownloadThumbnailJob.php`).

## Security
- When streaming via the CP controller, authorization is enforced with `authorize('view', $asset)`.
- If using a public cache disk, `ThumbnailService::url()` returns a direct public URL (bypassing CP streaming/auth); treat public disks as an explicit tradeoff.
