<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class InaprocinaproApiClient
{
    protected $baseUrl;
    protected $bearerToken;
    protected $rateLimiter;
    protected $timeout = 60; // seconds

    public function __construct(InaprocinaproRateLimiter $rateLimiter = null)
    {
        $this->baseUrl = config('api.inaproc.base_url');
        $this->bearerToken = config('api.inaproc.bearer_token');
        $this->rateLimiter = $rateLimiter ?? new InaprocinaproRateLimiter();
    }

    /**
     * Make a single HTTP request to the API
     *
     * @param string $endpoint The endpoint path (e.g., 'tender/pengumuman')
     * @param array $params Query parameters
     * @param array $options Additional request options
     * @return array Decoded JSON response
     * @throws Exception
     */
    public function request(string $endpoint, array $params = [], array $options = []): array
    {
        // Check rate limit before making request
        if (!$this->rateLimiter->hasCapacity()) {
            $this->rateLimiter->waitForCapacity();
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $headers = [
            'Authorization' => 'Bearer ' . $this->bearerToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        try {
            Log::debug("API Request: GET $url", ['params' => $params]);

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->get($url, $params);

            // Increment rate limit counter
            $this->rateLimiter->incrementRequestCount();

            if (!$response->successful()) {
                $error = "API request failed: Status {$response->status()}\n" . $response->body();
                Log::error("API Error: $endpoint", ['status' => $response->status(), 'body' => $response->body()]);
                throw new Exception($error);
            }

            $data = $response->json();

            Log::debug("API Response received", [
                'endpoint' => $endpoint,
                'items_count' => \is_array($data) ? \count($data) : 'N/A'
            ]);

            return $data ?? [];

        } catch (Exception $e) {
            Log::error("API Request Exception: $endpoint", [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * Handle cursor-based pagination automatically
     *
     * @param string $endpoint The endpoint path
     * @param array $params Query parameters
     * @param callable $callback Callback function to process each batch
     * @param int $maxBatches Maximum number of batches to fetch (0 = unlimited)
     * @return int Total items processed
     * @throws Exception
     */
    public function paginate(string $endpoint, array $params = [], callable $callback = null, int $maxBatches = 0): int
    {
        $totalItems = 0;
        $batchCount = 0;
        $cursor = $params['cursor'] ?? null;

        // Remove cursor from params if provided (will be set per iteration)
        unset($params['cursor']);

        while (true) {
            // Add cursor to current request if provided
            if ($cursor) {
                $params['cursor'] = $cursor;
            }

            // Make request
            $response = $this->request($endpoint, $params);

            // Handle response - could be array or object with nested data
            if (!\is_array($response)) {
                $response = (array) $response;
            }

            // Extract items and cursor from response
            // API response structure: { success, data: [...], meta: { cursor, has_more, limit } }
            $items = $response['data'] ?? $response['items'] ?? $response;
            $meta = $response['meta'] ?? [];
            $nextCursor = $meta['cursor'] ?? $response['next_cursor'] ?? $response['cursor'] ?? null;
            $hasMore = $meta['has_more'] ?? false;

            if (!\is_array($items)) {
                $items = [];
            }

            // Process batch with callback
            if ($callback && !empty($items)) {
                $callback($items);
            }

            $totalItems += \count($items);
            $batchCount++;

            // Stop if no more results (has_more=false) or max batches reached
            if (!$hasMore || empty($nextCursor) || ($maxBatches > 0 && $batchCount >= $maxBatches)) {
                break;
            }

            // Store cursor for resume capability
            Cache::put("inaproc_cursor_{$endpoint}", $nextCursor, now()->addDay());

            $cursor = $nextCursor;

            // Rate limiting between batches
            if ($this->rateLimiter->isLimitApproaching()) {
                Log::warning("Rate limit approaching, waiting before next batch", [
                    'endpoint' => $endpoint,
                    'batches_processed' => $batchCount
                ]);
                $this->rateLimiter->waitForCapacity();
            }
        }

        Log::info("Pagination completed", [
            'endpoint' => $endpoint,
            'total_items' => $totalItems,
            'batches_processed' => $batchCount
        ]);

        return $totalItems;
    }

    /**
     * Get rate limit status
     *
     * @return array Rate limit information
     */
    public function getRateLimitStatus(): array
    {
        return $this->rateLimiter->getStatus();
    }

    /**
     * Check if rate limit has capacity
     *
     * @return bool
     */
    public function hasRateLimitCapacity(): bool
    {
        return $this->rateLimiter->hasCapacity();
    }

    /**
     * Wait for rate limit capacity to be available
     *
     * @return void
     */
    public function waitForRateLimitCapacity(): void
    {
        $this->rateLimiter->waitForCapacity();
    }

    /**
     * Reset rate limiters
     *
     * @return void
     */
    public function resetRateLimits(): void
    {
        $this->rateLimiter->reset();
    }

    /**
     * Set request timeout
     *
     * @param int $seconds Timeout in seconds
     * @return void
     */
    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }
}
