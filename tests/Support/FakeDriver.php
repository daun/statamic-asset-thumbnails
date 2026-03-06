<?php

namespace Tests\Support;

use Daun\StatamicAssetThumbnails\Drivers\AbstractDriver;
use Daun\StatamicAssetThumbnails\Drivers\ConversionResult;
use Daun\StatamicAssetThumbnails\Drivers\DriverInterface;
use PHPUnit\Framework\Assert;
use Statamic\Assets\Asset;

class FakeDriver extends AbstractDriver implements DriverInterface
{
    /** @var list<Asset> */
    public array $generatedAssets = [];

    /** @var list<Asset> */
    public array $createdConversions = [];

    /** @var list<string> */
    public array $fetchedConversions = [];

    /**
     * The conversion ID to return from createConversion().
     */
    public ?string $fakeConversionId = 'fake-conversion-id';

    /**
     * The result to return from fetchResult().
     * Set to ConversionResult for success, null for "still processing", false for "failed".
     */
    public ConversionResult|false|null $fakeResult = null;

    protected array $supportedExtensions = [
        'bmp', 'tif', 'tiff',
        'eps', 'psd', 'psb',
        'raw', 'heic', 'heif', 'nef', 'cr2', 'cr3', 'crw', 'orf', 'dng', 'arw', 'rw2', 'raf',
        'ico',
        'mp4', 'm4v', 'mov', 'avi', 'ogv', 'mkv', 'webm', 'wmv',
        'txt', 'rtf', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    ];

    public function generate(Asset $asset): void
    {
        $this->generatedAssets[] = $asset;
    }

    public function createConversion(Asset $asset): ?string
    {
        $this->createdConversions[] = $asset;

        return $this->fakeConversionId;
    }

    public function fetchResult(string $conversionId): ConversionResult|false|null
    {
        $this->fetchedConversions[] = $conversionId;

        return $this->fakeResult;
    }

    /**
     * Assert that a thumbnail was generated for the given asset.
     */
    public function assertGenerated(Asset $asset): void
    {
        $found = collect($this->generatedAssets)->contains(
            fn (Asset $a) => $a->id() === $asset->id()
        );

        Assert::assertTrue($found, "Expected thumbnail generation for asset [{$asset->id()}], but none was triggered.");
    }

    /**
     * Assert that no thumbnails were generated.
     */
    public function assertNothingGenerated(): void
    {
        Assert::assertEmpty(
            $this->generatedAssets,
            'Expected no thumbnail generation, but '.count($this->generatedAssets).' asset(s) were generated.'
        );
    }

    /**
     * Assert that the given count of thumbnails were generated.
     */
    public function assertGeneratedCount(int $count): void
    {
        Assert::assertCount(
            $count,
            $this->generatedAssets,
            "Expected {$count} thumbnail generation(s), but ".count($this->generatedAssets).' were triggered.'
        );
    }

    /**
     * Assert that createConversion was called for the given asset.
     */
    public function assertConversionCreated(Asset $asset): void
    {
        $found = collect($this->createdConversions)->contains(
            fn (Asset $a) => $a->id() === $asset->id()
        );

        Assert::assertTrue($found, "Expected conversion creation for asset [{$asset->id()}], but none was triggered.");
    }

    /**
     * Assert that fetchResult was called with the given conversion ID.
     */
    public function assertResultFetched(string $conversionId): void
    {
        Assert::assertContains(
            $conversionId,
            $this->fetchedConversions,
            "Expected fetchResult to be called with [{$conversionId}], but it wasn't."
        );
    }

    /**
     * Reset the recorded state.
     */
    public function reset(): void
    {
        $this->generatedAssets = [];
        $this->createdConversions = [];
        $this->fetchedConversions = [];
    }
}
