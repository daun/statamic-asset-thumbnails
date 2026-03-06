<?php

namespace Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Factory for CloudConvert API mock responses.
 *
 * All responses follow the CloudConvert API format with a `data` envelope.
 * Used with MockHttpClient to simulate CloudConvert API interactions.
 */
class CloudConvertResponseFactory
{
    /**
     * Create a successful job creation response.
     *
     * This response is returned from POST /v2/jobs and includes
     * all three tasks (upload, thumbnail, export) with the upload
     * task in "waiting" status with a form URL for file upload.
     */
    public static function jobCreated(
        string $jobId = 'job-123',
        string $uploadFormUrl = 'https://storage.cloudconvert.com/upload/test',
    ): ResponseInterface {
        return new Response(201, ['Content-Type' => 'application/json'], json_encode([
            'data' => [
                'id' => $jobId,
                'tag' => null,
                'status' => 'waiting',
                'created_at' => '2024-01-01T00:00:00+00:00',
                'started_at' => null,
                'ended_at' => null,
                'tasks' => [
                    [
                        'id' => 'task-upload-1',
                        'name' => 'upload-task',
                        'operation' => 'import/upload',
                        'status' => 'waiting',
                        'result' => [
                            'form' => [
                                'url' => $uploadFormUrl,
                                'parameters' => [
                                    'expires' => '2024-01-02T00:00:00Z',
                                    'max_file_count' => 1,
                                    'max_file_size' => 5368709120,
                                    'signature' => 'test-signature',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 'task-thumbnail-1',
                        'name' => 'thumbnail-task',
                        'operation' => 'thumbnail',
                        'status' => 'waiting',
                        'result' => null,
                    ],
                    [
                        'id' => 'task-export-1',
                        'name' => 'export-task',
                        'operation' => 'export/url',
                        'status' => 'waiting',
                        'result' => null,
                    ],
                ],
            ],
        ]));
    }

    /**
     * Create a response for a job still in progress (processing).
     */
    public static function jobProcessing(string $jobId = 'job-123'): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'data' => [
                'id' => $jobId,
                'tag' => null,
                'status' => 'processing',
                'created_at' => '2024-01-01T00:00:00+00:00',
                'started_at' => '2024-01-01T00:00:01+00:00',
                'ended_at' => null,
                'tasks' => [
                    [
                        'id' => 'task-upload-1',
                        'name' => 'upload-task',
                        'operation' => 'import/upload',
                        'status' => 'finished',
                        'result' => null,
                    ],
                    [
                        'id' => 'task-thumbnail-1',
                        'name' => 'thumbnail-task',
                        'operation' => 'thumbnail',
                        'status' => 'processing',
                        'result' => null,
                    ],
                    [
                        'id' => 'task-export-1',
                        'name' => 'export-task',
                        'operation' => 'export/url',
                        'status' => 'waiting',
                        'result' => null,
                    ],
                ],
            ],
        ]));
    }

    /**
     * Create a response for a completed job with an exportable thumbnail URL.
     */
    public static function jobFinished(
        string $jobId = 'job-123',
        string $downloadUrl = 'https://storage.cloudconvert.com/result/thumbnail.webp',
        string $filename = 'thumbnail.webp',
    ): ResponseInterface {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'data' => [
                'id' => $jobId,
                'tag' => null,
                'status' => 'finished',
                'created_at' => '2024-01-01T00:00:00+00:00',
                'started_at' => '2024-01-01T00:00:01+00:00',
                'ended_at' => '2024-01-01T00:00:05+00:00',
                'tasks' => [
                    [
                        'id' => 'task-upload-1',
                        'name' => 'upload-task',
                        'operation' => 'import/upload',
                        'status' => 'finished',
                        'result' => null,
                    ],
                    [
                        'id' => 'task-thumbnail-1',
                        'name' => 'thumbnail-task',
                        'operation' => 'thumbnail',
                        'status' => 'finished',
                        'result' => null,
                    ],
                    [
                        'id' => 'task-export-1',
                        'name' => 'export-task',
                        'operation' => 'export/url',
                        'status' => 'finished',
                        'result' => [
                            'files' => [
                                [
                                    'filename' => $filename,
                                    'url' => $downloadUrl,
                                    'size' => 12345,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]));
    }

    /**
     * Create a response for a failed/errored job.
     */
    public static function jobError(string $jobId = 'job-123'): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'data' => [
                'id' => $jobId,
                'tag' => null,
                'status' => 'error',
                'created_at' => '2024-01-01T00:00:00+00:00',
                'started_at' => '2024-01-01T00:00:01+00:00',
                'ended_at' => '2024-01-01T00:00:05+00:00',
                'tasks' => [
                    [
                        'id' => 'task-upload-1',
                        'name' => 'upload-task',
                        'operation' => 'import/upload',
                        'status' => 'finished',
                        'result' => null,
                    ],
                    [
                        'id' => 'task-thumbnail-1',
                        'name' => 'thumbnail-task',
                        'operation' => 'thumbnail',
                        'status' => 'error',
                        'message' => 'Conversion failed',
                        'code' => 'CONVERSION_FAILED',
                        'result' => null,
                    ],
                ],
            ],
        ]));
    }

    /**
     * Create a successful file upload response.
     */
    public static function uploadSuccessful(): ResponseInterface
    {
        return new Response(201, ['Content-Type' => 'application/json'], json_encode([
            'data' => [
                'id' => 'task-upload-1',
                'operation' => 'import/upload',
                'status' => 'finished',
                'result' => null,
            ],
        ]));
    }
}
