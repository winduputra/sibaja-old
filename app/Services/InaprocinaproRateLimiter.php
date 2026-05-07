<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InaprocinaproRateLimiter
{
    protected $limitPerMinute;
    protected $limitPerHour;
    protected $checkInterval = 1; // seconds

    public function __construct()
    {
        $this->limitPerMinute = config('api.inaproc.rate_limit.per_minute', 1000);
        $this->limitPerHour = config('api.inaproc.rate_limit.per_hour', 5000);
    }

    /**
     * Increment request count
     *
     * @return void
     */
    public function incrementRequestCount(): void
    {
        $now = now();
        $minuteKey = $this->getMinuteKey($now);
        $hourKey = $this->getHourKey($now);

        // Increment minute counter (expires after 60 seconds)
        Cache::increment($minuteKey, 1, 60);

        // Increment hour counter (expires after 3600 seconds)
        Cache::increment($hourKey, 1, 3600);

        Log::debug("Rate limiter incremented", [
            'minute_key' => $minuteKey,
            'hour_key' => $hourKey,
            'minute_count' => Cache::get($minuteKey, 0),
            'hour_count' => Cache::get($hourKey, 0),
        ]);
    }

    /**
     * Check if there's capacity for a new request
     *
     * @return bool
     */
    public function hasCapacity(): bool
    {
        $now = now();
        $minuteCount = Cache::get($this->getMinuteKey($now), 0);
        $hourCount = Cache::get($this->getHourKey($now), 0);

        $minuteOk = $minuteCount < $this->limitPerMinute;
        $hourOk = $hourCount < $this->limitPerHour;

        return $minuteOk && $hourOk;
    }

    /**
     * Check if limit is approaching (80% usage)
     *
     * @return bool
     */
    public function isLimitApproaching(): bool
    {
        $now = now();
        $minuteCount = Cache::get($this->getMinuteKey($now), 0);
        $hourCount = Cache::get($this->getHourKey($now), 0);

        $minuteThreshold = (int) ($this->limitPerMinute * 0.8);
        $hourThreshold = (int) ($this->limitPerHour * 0.8);

        return $minuteCount >= $minuteThreshold || $hourCount >= $hourThreshold;
    }

    /**
     * Wait until capacity is available
     *
     * @param int $maxWaitSeconds Maximum time to wait (0 = unlimited)
     * @return bool True if capacity became available, false if timeout
     */
    public function waitForCapacity(int $maxWaitSeconds = 300): bool
    {
        $startTime = \time();

        while (!$this->hasCapacity()) {
            if ($maxWaitSeconds > 0 && (\time() - $startTime) > $maxWaitSeconds) {
                Log::warning("Rate limiter timeout waiting for capacity", [
                    'waited_seconds' => \time() - $startTime,
                    'max_wait_seconds' => $maxWaitSeconds
                ]);
                return false;
            }

            \sleep($this->checkInterval);

            Log::debug("Waiting for rate limit capacity", [
                'waited_seconds' => \time() - $startTime,
                'minute_count' => Cache::get($this->getMinuteKey(now()), 0),
                'hour_count' => Cache::get($this->getHourKey(now()), 0),
            ]);
        }

        Log::info("Rate limit capacity available", [
            'total_wait_seconds' => \time() - $startTime
        ]);

        return true;
    }

    /**
     * Get current rate limit status
     *
     * @return array Rate limit stats
     */
    public function getStatus(): array
    {
        $now = now();
        $minuteCount = Cache::get($this->getMinuteKey($now), 0);
        $hourCount = Cache::get($this->getHourKey($now), 0);

        return [
            'minute' => [
                'count' => $minuteCount,
                'limit' => $this->limitPerMinute,
                'remaining' => \max(0, $this->limitPerMinute - $minuteCount),
                'usage_percent' => (int) (($minuteCount / $this->limitPerMinute) * 100),
            ],
            'hour' => [
                'count' => $hourCount,
                'limit' => $this->limitPerHour,
                'remaining' => \max(0, $this->limitPerHour - $hourCount),
                'usage_percent' => (int) (($hourCount / $this->limitPerHour) * 100),
            ],
            'timestamp' => $now->toDateTimeString(),
        ];
    }

    /**
     * Reset all rate limit counters
     *
     * @return void
     */
    public function reset(): void
    {
        $now = now();
        Cache::forget($this->getMinuteKey($now));
        Cache::forget($this->getHourKey($now));

        Log::info("Rate limiters reset");
    }

    /**
     * Generate cache key for minute counter
     *
     * @param mixed $now DateTime instance
     * @return string
     */
    protected function getMinuteKey($now): string
    {
        return 'inaproc_ratelimit_minute_' . $now->format('Y-m-d H:i');
    }

    /**
     * Generate cache key for hour counter
     *
     * @param mixed $now DateTime instance
     * @return string
     */
    protected function getHourKey($now): string
    {
        return 'inaproc_ratelimit_hour_' . $now->format('Y-m-d H');
    }
}
