<?php

namespace Tests\Support;

use transloadit\TransloaditResponse;

/**
 * Factory for Transloadit API mock responses.
 *
 * Creates TransloaditResponse objects with pre-configured data arrays,
 * matching the structure returned by the Transloadit PHP SDK.
 * Used with Mockery to simulate Transloadit API interactions.
 */
class TransloaditResponseFactory
{
    /**
     * Create a successful assembly creation response.
     */
    public static function assemblyCreated(
        string $assemblyId = 'assembly-123',
        string $assemblySslUrl = 'https://api2.transloadit.com/assemblies/assembly-123',
    ): TransloaditResponse {
        $response = new TransloaditResponse;
        $response->data = [
            'ok' => 'ASSEMBLY_UPLOADING',
            'assembly_id' => $assemblyId,
            'assembly_ssl_url' => $assemblySslUrl,
            'assembly_url' => str_replace('https://', 'http://', $assemblySslUrl),
            'bytes_expected' => 12345,
            'bytes_received' => 0,
            'uploads' => [],
            'results' => [],
        ];

        return $response;
    }

    /**
     * Create a response for an assembly still uploading.
     */
    public static function assemblyUploading(string $assemblyId = 'assembly-123'): TransloaditResponse
    {
        $response = new TransloaditResponse;
        $response->data = [
            'ok' => 'ASSEMBLY_UPLOADING',
            'assembly_id' => $assemblyId,
            'assembly_ssl_url' => "https://api2.transloadit.com/assemblies/{$assemblyId}",
            'bytes_expected' => 12345,
            'bytes_received' => 6000,
            'uploads' => [],
            'results' => [],
        ];

        return $response;
    }

    /**
     * Create a response for an assembly still executing (processing).
     */
    public static function assemblyExecuting(string $assemblyId = 'assembly-123'): TransloaditResponse
    {
        $response = new TransloaditResponse;
        $response->data = [
            'ok' => 'ASSEMBLY_EXECUTING',
            'assembly_id' => $assemblyId,
            'assembly_ssl_url' => "https://api2.transloadit.com/assemblies/{$assemblyId}",
            'bytes_expected' => 12345,
            'bytes_received' => 12345,
            'uploads' => [
                [
                    'id' => 'upload-1',
                    'name' => 'document.pdf',
                    'basename' => 'document',
                    'ext' => 'pdf',
                    'size' => 12345,
                    'mime' => 'application/pdf',
                    'type' => 'document',
                    'ssl_url' => 'https://tmp.transloadit.com/upload-1/document.pdf',
                ],
            ],
            'results' => [],
        ];

        return $response;
    }

    /**
     * Create a response for a completed assembly with preview results.
     */
    public static function assemblyCompleted(
        string $assemblyId = 'assembly-123',
        string $downloadUrl = 'https://tmp.transloadit.com/result/preview.jpg',
        string $filename = 'preview.jpg',
    ): TransloaditResponse {
        $response = new TransloaditResponse;
        $response->data = [
            'ok' => 'ASSEMBLY_COMPLETED',
            'assembly_id' => $assemblyId,
            'assembly_ssl_url' => "https://api2.transloadit.com/assemblies/{$assemblyId}",
            'bytes_expected' => 12345,
            'bytes_received' => 12345,
            'uploads' => [
                [
                    'id' => 'upload-1',
                    'name' => 'document.pdf',
                    'basename' => 'document',
                    'ext' => 'pdf',
                    'size' => 12345,
                    'mime' => 'application/pdf',
                    'type' => 'document',
                    'ssl_url' => 'https://tmp.transloadit.com/upload-1/document.pdf',
                ],
            ],
            'results' => [
                'preview' => [
                    [
                        'id' => 'result-1',
                        'name' => $filename,
                        'basename' => pathinfo($filename, PATHINFO_FILENAME),
                        'ext' => pathinfo($filename, PATHINFO_EXTENSION),
                        'size' => 5678,
                        'mime' => 'image/jpeg',
                        'type' => 'image',
                        'ssl_url' => $downloadUrl,
                        'url' => str_replace('https://', 'http://', $downloadUrl),
                    ],
                ],
            ],
        ];

        return $response;
    }

    /**
     * Create a response for a canceled assembly.
     */
    public static function assemblyCanceled(string $assemblyId = 'assembly-123'): TransloaditResponse
    {
        $response = new TransloaditResponse;
        $response->data = [
            'ok' => 'ASSEMBLY_CANCELED',
            'assembly_id' => $assemblyId,
            'assembly_ssl_url' => "https://api2.transloadit.com/assemblies/{$assemblyId}",
            'uploads' => [],
            'results' => [],
        ];

        return $response;
    }

    /**
     * Create a response for a failed/aborted assembly.
     */
    public static function assemblyAborted(string $assemblyId = 'assembly-123'): TransloaditResponse
    {
        $response = new TransloaditResponse;
        $response->data = [
            'ok' => 'REQUEST_ABORTED',
            'assembly_id' => $assemblyId,
            'assembly_ssl_url' => "https://api2.transloadit.com/assemblies/{$assemblyId}",
            'error' => 'REQUEST_ABORTED',
            'message' => 'The request was aborted.',
            'uploads' => [],
            'results' => [],
        ];

        return $response;
    }

    /**
     * Create a response for an API error (no ok key).
     */
    public static function apiError(string $error = 'SERVER_ERROR', string $message = 'Internal server error'): TransloaditResponse
    {
        $response = new TransloaditResponse;
        $response->data = [
            'error' => $error,
            'message' => $message,
        ];

        return $response;
    }
}
