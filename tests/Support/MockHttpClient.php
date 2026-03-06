<?php

namespace Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A PSR-18 mock HTTP client that returns pre-configured responses
 * and records all requests for later assertions.
 *
 * Used to inject into the CloudConvert SDK via the `http_client` option,
 * similar to how Saloon's MockClient or Laravel's Http::fake() work.
 */
class MockHttpClient implements ClientInterface
{
    /** @var list<array{request: RequestInterface, response: ResponseInterface}> */
    protected array $recorded = [];

    /** @var list<ResponseInterface|\Closure> */
    protected array $responseQueue = [];

    /** @var array<string, ResponseInterface|\Closure> */
    protected array $urlResponses = [];

    /** @var ResponseInterface|\Closure|null */
    protected $defaultResponse = null;

    /**
     * Queue a response to be returned for the next request.
     */
    public function addResponse(ResponseInterface|\Closure $response): static
    {
        $this->responseQueue[] = $response;

        return $this;
    }

    /**
     * Register a response for a specific URL pattern (substring match).
     */
    public function addResponseForUrl(string $urlPattern, ResponseInterface|\Closure $response): static
    {
        $this->urlResponses[$urlPattern] = $response;

        return $this;
    }

    /**
     * Set a default response when no queued or URL-specific response matches.
     */
    public function setDefaultResponse(ResponseInterface|\Closure $response): static
    {
        $this->defaultResponse = $response;

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->resolveResponse($request);

        $this->recorded[] = [
            'request' => $request,
            'response' => $response,
        ];

        return $response;
    }

    protected function resolveResponse(RequestInterface $request): ResponseInterface
    {
        // 1. Check queued responses (FIFO)
        if (! empty($this->responseQueue)) {
            $response = array_shift($this->responseQueue);

            return $response instanceof \Closure ? $response($request) : $response;
        }

        // 2. Check URL-specific responses
        $url = (string) $request->getUri();
        foreach ($this->urlResponses as $pattern => $response) {
            if (str_contains($url, $pattern)) {
                return $response instanceof \Closure ? $response($request) : $response;
            }
        }

        // 3. Default response
        if ($this->defaultResponse !== null) {
            return $this->defaultResponse instanceof \Closure
                ? ($this->defaultResponse)($request)
                : $this->defaultResponse;
        }

        return new Response(200, [], '{}');
    }

    /**
     * Get all recorded request/response pairs.
     *
     * @return list<array{request: RequestInterface, response: ResponseInterface}>
     */
    public function getRecorded(): array
    {
        return $this->recorded;
    }

    /**
     * Get all recorded requests.
     *
     * @return list<RequestInterface>
     */
    public function getRequests(): array
    {
        return array_map(fn ($pair) => $pair['request'], $this->recorded);
    }

    /**
     * Get a specific recorded request by index.
     */
    public function getRequest(int $index): ?RequestInterface
    {
        return $this->recorded[$index]['request'] ?? null;
    }

    /**
     * Get the number of recorded requests.
     */
    public function requestCount(): int
    {
        return count($this->recorded);
    }

    /**
     * Assert the expected number of requests were made.
     */
    public function assertRequestCount(int $expected): void
    {
        \PHPUnit\Framework\Assert::assertCount(
            $expected,
            $this->recorded,
            "Expected {$expected} HTTP request(s), but {$this->requestCount()} were recorded."
        );
    }

    /**
     * Assert a request was made to a URL containing the given substring.
     */
    public function assertRequestMade(string $urlSubstring, ?string $method = null): void
    {
        $found = collect($this->recorded)->contains(function ($pair) use ($urlSubstring, $method) {
            $url = (string) $pair['request']->getUri();
            $matchesUrl = str_contains($url, $urlSubstring);
            $matchesMethod = $method === null || strtoupper($pair['request']->getMethod()) === strtoupper($method);

            return $matchesUrl && $matchesMethod;
        });

        $methodStr = $method ? " {$method}" : '';
        \PHPUnit\Framework\Assert::assertTrue(
            $found,
            "Expected an HTTP{$methodStr} request to URL containing [{$urlSubstring}], but none was recorded."
        );
    }

    /**
     * Assert no requests were made.
     */
    public function assertNoRequestsMade(): void
    {
        \PHPUnit\Framework\Assert::assertEmpty(
            $this->recorded,
            'Expected no HTTP requests, but '.$this->requestCount().' were recorded.'
        );
    }

    /**
     * Reset all recorded data and queued responses.
     */
    public function reset(): void
    {
        $this->recorded = [];
        $this->responseQueue = [];
        $this->urlResponses = [];
        $this->defaultResponse = null;
    }
}
