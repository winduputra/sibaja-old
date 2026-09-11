<?php

namespace Tests\Feature\View;

use App\Models\ApiSyncRun;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GlobalApiSyncIndicatorTest extends TestCase
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
            $table->string('command')->default('update:all');
            $table->string('status');
            $table->boolean('is_global')->default(false);
            $table->json('modules')->nullable();
            $table->json('options')->nullable();
            $table->string('failed_module')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_layout_renders_semantic_time_and_indonesian_label_value_for_latest_global_success(): void
    {
        $finishedAt = Carbon::parse('2026-09-02 10:15:00', config('app.timezone'));
        $this->seedSuccessfulGlobalRun($finishedAt);

        $rendered = $this->renderLayoutAfterBootingProvider();

        $this->assertStringContainsString('Sinkronisasi API terakhir', $rendered);
        $this->assertMatchesRegularExpression(
            '/<time\b[^>]*datetime="' . preg_quote($finishedAt->toIso8601String(), '/') . '"[^>]*>2 September 2026 10:15<\/time>/',
            $rendered
        );
        $this->assertStringContainsString($finishedAt->toIso8601String(), $rendered);
        $this->assertStringContainsString('2 September 2026 10:15', $rendered);
    }

    public function test_layout_renders_indonesian_empty_state_when_no_successful_global_run_exists(): void
    {
        $rendered = $this->renderLayoutAfterBootingProvider();

        $this->assertStringContainsString('Sinkronisasi API terakhir', $rendered);
        $this->assertStringContainsString('Belum pernah disinkronkan', $rendered);
    }

    private function renderLayoutAfterBootingProvider(): string
    {
        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        return view('layouts.user')->render();
    }

    private function seedSuccessfulGlobalRun(Carbon $finishedAt): void
    {
        ApiSyncRun::create([
            'command' => 'update:all',
            'status' => ApiSyncRun::STATUS_SUCCEEDED,
            'is_global' => true,
            'modules' => ['rup', 'tender', 'non-tender', 'pencatatan-swakelola', 'ekatalog', 'rekap-nasional'],
            'options' => ['tahun' => 2026, 'type' => 'all'],
            'failed_module' => null,
            'error_message' => null,
            'started_at' => $finishedAt,
            'finished_at' => $finishedAt,
        ]);
    }
}
