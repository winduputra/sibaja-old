<?php

namespace Tests\Unit\Services;

use App\Services\InaprocinaproApiClient;
use App\Services\InaprocinaproRateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InaprocinaproApiClientTest extends TestCase
{
    protected $client;
    protected $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rateLimiter = new InaprocinaproRateLimiter();
        $this->client = new InaprocinaproApiClient($this->rateLimiter);

        // Reset rate limiters before each test
        Cache::flush();
    }

    /** @test */
    public function it_can_make_a_successful_api_request()
    {
        $mockData = [
            ['id' => 1, 'name' => 'Test 1'],
            ['id' => 2, 'name' => 'Test 2'],
        ];

        Http::fake([
            'data.inaproc.id/*' => Http::response($mockData),
        ]);

        $response = $this->client->request('tender/pengumuman', ['tahun' => '2026']);

        $this->assertIsArray($response);
        $this->assertCount(2, $response);
        $this->assertEquals('Test 1', $response[0]['name']);
    }

    /** @test */
    public function it_increments_rate_limit_counter_on_request()
    {
        Http::fake([
            'data.inaproc.id/*' => Http::response([]),
        ]);

        $statusBefore = $this->rateLimiter->getStatus();
        $this->client->request('tender/pengumuman');
        $statusAfter = $this->rateLimiter->getStatus();

        $this->assertEquals($statusBefore['minute']['count'] + 1, $statusAfter['minute']['count']);
    }

    /** @test */
    public function it_throws_exception_on_failed_request()
    {
        Http::fake([
            'data.inaproc.id/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $this->expectException(\Exception::class);
        $this->client->request('tender/pengumuman');
    }

    /** @test */
    public function it_can_paginate_with_cursor()
    {
        $page1 = [
            'data' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
            ],
            'next_cursor' => 'abc123',
        ];

        $page2 = [
            'data' => [
                ['id' => 3, 'name' => 'Item 3'],
                ['id' => 4, 'name' => 'Item 4'],
            ],
            'next_cursor' => null,
        ];

        Http::fake([
            'data.inaproc.id/*' => Http::sequence()
                ->push($page1)
                ->push($page2),
        ]);

        $items = [];
        $count = $this->client->paginate('tender/pengumuman', [], function ($batch) use (&$items) {
            $items = \array_merge($items, $batch);
        });

        $this->assertEquals(4, $count);
        $this->assertCount(4, $items);
        $this->assertEquals('Item 1', $items[0]['name']);
        $this->assertEquals('Item 4', $items[3]['name']);
    }

    /** @test */
    public function it_respects_rate_limit_capacity_check()
    {
        $status = $this->rateLimiter->getStatus();
        $this->assertTrue($this->client->hasRateLimitCapacity());
    }

    /** @test */
    public function it_can_get_rate_limit_status()
    {
        Http::fake([
            'data.inaproc.id/*' => Http::response([]),
        ]);

        // Make a request to increment counter
        $this->client->request('tender/pengumuman');

        $status = $this->client->getRateLimitStatus();

        $this->assertArrayHasKey('minute', $status);
        $this->assertArrayHasKey('hour', $status);
        $this->assertEquals(1, $status['minute']['count']);
        $this->assertGreater(0, $status['minute']['remaining']);
    }

    /** @test */
    public function it_can_reset_rate_limits()
    {
        Http::fake([
            'data.inaproc.id/*' => Http::response([]),
        ]);

        // Make multiple requests
        $this->client->request('tender/pengumuman');
        $this->client->request('tender/pengumuman');

        $statusBefore = $this->client->getRateLimitStatus();
        $this->assertEquals(2, $statusBefore['minute']['count']);

        // Reset
        $this->client->resetRateLimits();

        $statusAfter = $this->client->getRateLimitStatus();
        $this->assertEquals(0, $statusAfter['minute']['count']);
    }

    /** @test */
    public function it_sets_authorization_header()
    {
        Http::fake([
            'data.inaproc.id/*' => Http::response([]),
        ]);

        $this->client->request('tender/pengumuman');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization') &&
                   \str_starts_with($request->header('Authorization'), 'Bearer ');
        });
    }

    /** @test */
    public function it_handles_cursor_pagination_with_max_batches()
    {
        $page1 = ['data' => [['id' => 1]], 'next_cursor' => 'cursor1'];
        $page2 = ['data' => [['id' => 2]], 'next_cursor' => 'cursor2'];
        $page3 = ['data' => [['id' => 3]], 'next_cursor' => null];

        Http::fake([
            'data.inaproc.id/*' => Http::sequence()
                ->push($page1)
                ->push($page2)
                ->push($page3),
        ]);

        $items = [];
        $count = $this->client->paginate('tender/pengumuman', [], function ($batch) use (&$items) {
            $items = \array_merge($items, $batch);
        }, 2); // Max 2 batches

        // Should only fetch 2 batches, not 3
        $this->assertEquals(2, $count);
        $this->assertCount(2, $items);
    }
}
