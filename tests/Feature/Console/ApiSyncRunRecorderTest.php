<?php

namespace Tests\Feature\Console;

use App\Models\ApiSyncRun;
use App\Services\ApiSyncRunRecorder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiSyncRunRecorderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('api_sync_runs');
        Schema::create('api_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->boolean('is_global')->default(false);
            $table->json('modules')->nullable();
            $table->json('options')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_successful_global_completion_is_eligible_and_persists_finished_timestamp_and_array_payloads(): void
    {
        $finishedAt = Carbon::parse('2026-09-02 10:15:00', 'UTC');
        Carbon::setTestNow($finishedAt);

        app(ApiSyncRunRecorder::class)->record([
            'status' => 'succeeded',
            'is_global' => true,
            'modules' => ['rup', 'tender'],
            'options' => ['tahun' => 2026, 'type' => 'all'],
            'finished_at' => $finishedAt,
        ]);

        $eligibleRuns = ApiSyncRun::query()
            ->where('status', 'succeeded')
            ->where('is_global', true)
            ->whereNotNull('finished_at')
            ->get();

        $this->assertCount(1, $eligibleRuns);

        $storedRun = $eligibleRuns->first();
        $this->assertNotNull($storedRun);
        $this->assertSame('succeeded', $storedRun->status);
        $this->assertTrue((bool) $storedRun->is_global);
        $this->assertSame(['rup', 'tender'], $storedRun->modules);
        $this->assertSame(['tahun' => 2026, 'type' => 'all'], $storedRun->options);
        $this->assertSame($finishedAt->toDateTimeString(), $storedRun->finished_at->toDateTimeString());
        $this->assertDatabaseHas('api_sync_runs', [
            'status' => 'succeeded',
            'is_global' => 1,
            'finished_at' => $finishedAt->toDateTimeString(),
        ]);
    }

    /**
     * @dataProvider nonEligibleStatusProvider
     */
    public function test_partial_failed_and_dry_run_are_not_eligible(string $status): void
    {
        $this->insertApiSyncRun([
            'status' => $status,
            'is_global' => true,
            'modules' => ['non-eligible'],
            'options' => ['tahun' => 2026, 'type' => 'all'],
            'finished_at' => Carbon::parse('2026-09-02 11:00:00', 'UTC'),
        ]);

        $eligibleRuns = ApiSyncRun::query()
            ->where('status', 'succeeded')
            ->where('is_global', true)
            ->whereNotNull('finished_at')
            ->get();

        $this->assertCount(0, $eligibleRuns);
    }

    public function nonEligibleStatusProvider(): array
    {
        return [
            'partial' => ['partial'],
            'failed' => ['failed'],
            'dry run' => ['dry_run'],
        ];
    }

    public function test_newest_eligible_success_wins_over_newer_ineligible_runs(): void
    {
        $olderSuccess = Carbon::parse('2026-09-02 09:00:00', 'UTC');
        $newerIneligible = Carbon::parse('2026-09-02 11:00:00', 'UTC');
        $newerSuccess = Carbon::parse('2026-09-02 12:00:00', 'UTC');

        $this->insertApiSyncRun([
            'status' => 'succeeded',
            'is_global' => true,
            'modules' => ['older-success'],
            'options' => ['tahun' => 2026, 'type' => 'all'],
            'finished_at' => $olderSuccess,
        ]);

        $this->insertApiSyncRun([
            'status' => 'partial',
            'is_global' => true,
            'modules' => ['newer-partial'],
            'options' => ['tahun' => 2026, 'type' => 'all'],
            'finished_at' => $newerIneligible,
        ]);

        $this->insertApiSyncRun([
            'status' => 'succeeded',
            'is_global' => true,
            'modules' => ['newer-success'],
            'options' => ['tahun' => 2026, 'type' => 'all'],
            'finished_at' => $newerSuccess,
        ]);

        $latestEligibleRun = ApiSyncRun::query()
            ->where('status', 'succeeded')
            ->where('is_global', true)
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->first();

        $this->assertNotNull($latestEligibleRun);
        $this->assertSame(['newer-success'], $latestEligibleRun->modules);
        $this->assertSame($newerSuccess->toDateTimeString(), $latestEligibleRun->finished_at->toDateTimeString());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function insertApiSyncRun(array $attributes): void
    {
        DB::table('api_sync_runs')->insert([
            'status' => $attributes['status'],
            'is_global' => $attributes['is_global'] ? 1 : 0,
            'modules' => json_encode($attributes['modules'], JSON_THROW_ON_ERROR),
            'options' => json_encode($attributes['options'], JSON_THROW_ON_ERROR),
            'finished_at' => $attributes['finished_at'] instanceof Carbon
                ? $attributes['finished_at']->toDateTimeString()
                : $attributes['finished_at'],
            'created_at' => Carbon::now('UTC')->toDateTimeString(),
            'updated_at' => Carbon::now('UTC')->toDateTimeString(),
        ]);
    }
}
