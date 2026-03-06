<?php

namespace Daun\StatamicAssetThumbnails\Http\Controllers\Cp;

use Daun\StatamicAssetThumbnails\Services\ThumbnailService;
use Statamic\Facades\Asset as Assets;
use Statamic\Http\Controllers\CP\CpController;
use Symfony\Component\HttpFoundation\Response;

class ThumbnailController extends CpController
{
    public function __construct(
        protected ThumbnailService $service,
    ) {}

    public function show(string $id): Response
    {
        $asset = Assets::findById(base64_decode($id));
        abort_unless($asset, 404);

        $this->authorize('view', $asset);

        if ($this->service->isGenerating($asset)) {
            $this->service->waitUntilGenerated($asset);
        }

        if ($this->service->exists($asset)) {
            return $this->service->response($asset);
        }

        $this->service->generate($asset);

        return redirect('https://placehold.co/600?text=Generating\nPreview&font=raleway', 302);
    }
}
