<?php

namespace Tests\Unit\Services;

use App\Services\InaprocinaproRateLimiter;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InaprocinaproRateLimiterTest extends TestCase
{
    protected $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = new InaprocinaproRateLimiter();
        Cache::flush();
    }

    /** @test */
    public function it_tracks_requests_per_minute()
    {
        $this->limiter->incrementRequestCount();
        $this->limiter->incrementRequestCount();

        $status = $this->limiter->getStatus();

        $this->assertEquals(2, $status['minute']['count']);
        $this->assertLessThan(1000, $status['minute']['remaining']);
    }

    /** @test */
    public function it_tracks_requests_per_hour()
    {
        $this->limiter->incrementRequestCount();
        $this->limiter->incrementRequestCount();
        $this->limiter->incrementRequestCount();

        $status = $this->limiter->getStatus();

        $this->assertEquals(3, $status['hour']['count']);
        $this->assertLessThan(5000, $status['hour']['remaining']);
    }

    /** @test */
    public function it_has_capacity_when_under_limit()
    {
        $this->assertTrue($this->limiter->hasCapacity());
    }

    /** @test */
    public function it_calculates_usage_percent()
    {
        // Simulate 100 requests
        for ($i = 0; $i < 100; $i++) {
            $this->limiter->incrementRequestCount();
        }

        $status = $this->limiter->getStatus();

        $this->assertEquals(100, $status['minute']['count']);
        $this->assertGreater(0, $status['minute']['usage_percent']);
        $this->assertLessThanOrEqual(100, $status['minute']['usage_percent']);
    }

    /** @test */
    public function it_detects_approaching_limit()
    {
        // Simulate 80% of minute limit (800 requests)
        Cache::put('inaproc_ratelimit_minute_' . now()->format('Y-m-d H:i'), 800, 60);

        $this->assertTrue($this->limiter->isLimitApproaching());
    }

    /** @test */
    public function it_does_not_detect_approaching_limit_when_below_threshold()
    {
        // Simulate 50% of minute limit (500 requests)
        Cache::put('inaproc_ratelimit_minute_' . now()->format('Y-m-d H:i'), 500, 60);

        $this->assertFalse($this->limiter->isLimitApproaching());
    }

    /** @test */
    public function it_can_reset_counters()
    {
        $this->limiter->incrementRequestCount();
        $this->limiter->incrementRequestCount();

        $statusBefore = $this->limiter->getStatus();
        $this->assertEquals(2, $statusBefore['minute']['count']);

        $this->limiter->reset();

        $statusAfter = $this->limiter->getStatus();
        $this->assertEquals(0, $statusAfter['minute']['count']);
        $this->assertEquals(0, $statusAfter['hour']['count']);
    }

    /** @test */
    public function it_includes_timestamp_in_status()
    {
        $status = $this->limiter->getStatus();

        $this->assertArrayHasKey('timestamp', $status);
        $this->assertNotEmpty($status['timestamp']);
    }

    /** @test */
    public function it_returns_remaining_capacity()
    {
        $this->limiter->incrementRequestCount();

        $status = $this->limiter->getStatus();

        $expectedRemaining = 1000 - 1; // limit - count
        $this->assertEquals($expectedRemaining, $status['minute']['remaining']);
    }

    /** @test */
    public function it_handles_zero_remaining_gracefully()
    {
        // Simulate maximum requests
        $minuteKey = 'inaproc_ratelimit_minute_' . now()->format('Y-m-d H:i');
        Cache::put($minuteKey, 1000, 60);

        $status = $this->limiter->getStatus();

        $this->assertEquals(0, $status['minute']['remaining']);
        $this->assertEquals(100, $status['minute']['usage_percent']);
    }

    /** @test */
    public function it_waits_for_capacity_with_timeout()
    {
        // This test just verifies the method exists and works
        // Actual timeout behavior is difficult to test without slowing down tests
        $result = $this->limiter->waitForCapacity(1);

        $this->assertTrue($result); // Should be true since we have capacity
    }
}
