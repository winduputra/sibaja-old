<?php

namespace Tests\Feature\Console;

use App\Console\Commands\UpdateAllCommand;
use App\Models\ApiSyncRun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class UpdateAllSyncRunTest extends TestCase
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
        Mockery::close();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_unfiltered_unlimited_non_dry_run_records_global_success(): void
    {
        $finishedAt = Carbon::parse('2026-09-02 08:00:00', config('app.timezone'));
        Carbon::setTestNow($finishedAt);

        $this->expectModuleCalls([
            ['inaproc:sync-rup', ['--tahun' => '2026', '--type' => 'all']],
            ['inaproc:sync-tender', ['--tahun' => '2026', '--type' => 'all']],
            ['inaproc:sync-non-tender', ['--tahun' => '2026', '--type' => 'all']],
            ['inaproc:sync-pencatatan-swakelola', ['--tahun' => '2026', '--type' => 'all']],
            ['inaproc:sync-ekatalog-v6', ['--tahun' => '2026']],
            ['inaproc:sync-rekap-nasional', []],
        ]);

        $exitCode = $this->makeCommand([
            'tahun' => '2026',
            'all-years' => false,
            'dry-run' => false,
            'limit' => 0,
            'only' => null,
            'skip' => null,
        ])->handle();

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, ApiSyncRun::count());

        $recordedRun = ApiSyncRun::query()->first();
        $this->assertNotNull($recordedRun);
        $this->assertSame(ApiSyncRun::STATUS_SUCCEEDED, $recordedRun->status);
        $this->assertTrue($recordedRun->is_global);
        $this->assertSame($finishedAt->toDateTimeString(), $recordedRun->finished_at->toDateTimeString());
        $this->assertSame(
            ['rup', 'tender', 'non-tender', 'pencatatan-swakelola', 'ekatalog', 'rekap-nasional'],
            $recordedRun->modules
        );
    }

    /**
     * @dataProvider partialClassificationProvider
     *
     * @param array<string, int|string|bool|null> $options
     * @param array<int, array{0: string, 1: array<string, int|string|bool>}> $calls
     * @param array<int, string> $expectedModules
     */
    public function test_filtered_or_limited_runs_record_partial_and_non_global(
        string $caseName,
        array $options,
        array $calls,
        array $expectedModules
    ): void {
        $finishedAt = Carbon::parse('2026-09-02 09:00:00', config('app.timezone'));
        Carbon::setTestNow($finishedAt);

        $this->expectModuleCalls($calls);

        $exitCode = $this->makeCommand($options)->handle();

        $this->assertSame(0, $exitCode, $caseName);
        $this->assertSame(1, ApiSyncRun::count(), $caseName);

        $recordedRun = ApiSyncRun::query()->first();
        $this->assertNotNull($recordedRun, $caseName);
        $this->assertSame(ApiSyncRun::STATUS_PARTIAL, $recordedRun->status, $caseName);
        $this->assertFalse($recordedRun->is_global, $caseName);
        $this->assertSame($finishedAt->toDateTimeString(), $recordedRun->finished_at->toDateTimeString(), $caseName);
        $this->assertSame($expectedModules, $recordedRun->modules, $caseName);
    }

    public function partialClassificationProvider(): array
    {
        return [
            'only' => [
                'only',
                [
                    'tahun' => '2026',
                    'all-years' => false,
                    'dry-run' => false,
                    'limit' => 0,
                    'only' => 'rup,tender',
                    'skip' => null,
                ],
                [
                    ['inaproc:sync-rup', ['--tahun' => '2026', '--type' => 'all']],
                    ['inaproc:sync-tender', ['--tahun' => '2026', '--type' => 'all']],
                ],
                ['rup', 'tender'],
            ],
            'skip' => [
                'skip',
                [
                    'tahun' => '2026',
                    'all-years' => false,
                    'dry-run' => false,
                    'limit' => 0,
                    'only' => null,
                    'skip' => 'ekatalog',
                ],
                [
                    ['inaproc:sync-rup', ['--tahun' => '2026', '--type' => 'all']],
                    ['inaproc:sync-tender', ['--tahun' => '2026', '--type' => 'all']],
                    ['inaproc:sync-non-tender', ['--tahun' => '2026', '--type' => 'all']],
                    ['inaproc:sync-pencatatan-swakelola', ['--tahun' => '2026', '--type' => 'all']],
                    ['inaproc:sync-rekap-nasional', []],
                ],
                ['rup', 'tender', 'non-tender', 'pencatatan-swakelola', 'rekap-nasional'],
            ],
            'limit' => [
                'limit',
                [
                    'tahun' => '2026',
                    'all-years' => false,
                    'dry-run' => false,
                    'limit' => 1,
                    'only' => null,
                    'skip' => null,
                ],
                [
                    ['inaproc:sync-rup', ['--tahun' => '2026', '--type' => 'all', '--limit' => 1]],
                    ['inaproc:sync-tender', ['--tahun' => '2026', '--type' => 'all', '--limit' => 1]],
                    ['inaproc:sync-non-tender', ['--tahun' => '2026', '--type' => 'all', '--limit' => 1]],
                    ['inaproc:sync-pencatatan-swakelola', ['--tahun' => '2026', '--type' => 'all', '--limit' => 1]],
                    ['inaproc:sync-ekatalog-v6', ['--tahun' => '2026', '--limit' => 1]],
                    ['inaproc:sync-rekap-nasional', ['--limit' => 1]],
                ],
                ['rup', 'tender', 'non-tender', 'pencatatan-swakelola', 'ekatalog', 'rekap-nasional'],
            ],
        ];
    }

    public function test_dry_run_records_dry_run_and_non_global(): void
    {
        $finishedAt = Carbon::parse('2026-09-02 10:00:00', config('app.timezone'));
        Carbon::setTestNow($finishedAt);

        $this->expectModuleCalls([
            ['inaproc:sync-rup', ['--tahun' => '2026', '--type' => 'all', '--dry-run' => true]],
            ['inaproc:sync-tender', ['--tahun' => '2026', '--type' => 'all', '--dry-run' => true]],
            ['inaproc:sync-non-tender', ['--tahun' => '2026', '--type' => 'all', '--dry-run' => true]],
            ['inaproc:sync-pencatatan-swakelola', ['--tahun' => '2026', '--type' => 'all', '--dry-run' => true]],
            ['inaproc:sync-ekatalog-v6', ['--tahun' => '2026', '--dry-run' => true]],
            ['inaproc:sync-rekap-nasional', ['--dry-run' => true]],
        ]);

        $exitCode = $this->makeCommand([
            'tahun' => '2026',
            'all-years' => false,
            'dry-run' => true,
            'limit' => 0,
            'only' => null,
            'skip' => null,
        ])->handle();

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, ApiSyncRun::count());

        $recordedRun = ApiSyncRun::query()->first();
        $this->assertNotNull($recordedRun);
        $this->assertSame(ApiSyncRun::STATUS_DRY_RUN, $recordedRun->status);
        $this->assertFalse($recordedRun->is_global);
        $this->assertSame($finishedAt->toDateTimeString(), $recordedRun->finished_at->toDateTimeString());
    }

    public function test_child_failure_records_failed_and_keeps_prior_global_success_eligible(): void
    {
        $olderSuccess = Carbon::parse('2026-09-02 07:30:00', config('app.timezone'));
        ApiSyncRun::create([
            'command' => 'update:all',
            'status' => ApiSyncRun::STATUS_SUCCEEDED,
            'is_global' => true,
            'modules' => ['rup', 'tender', 'non-tender', 'pencatatan-swakelola', 'ekatalog', 'rekap-nasional'],
            'options' => ['tahun' => 2026, 'type' => 'all'],
            'started_at' => $olderSuccess,
            'finished_at' => $olderSuccess,
        ]);

        $finishedAt = Carbon::parse('2026-09-02 11:00:00', config('app.timezone'));
        Carbon::setTestNow($finishedAt);

        Artisan::shouldReceive('call')
            ->once()
            ->with(
                'inaproc:sync-rup',
                Mockery::on(static fn (array $options): bool => $options === ['--tahun' => '2026', '--type' => 'all'])
            )
            ->andReturn(1);

        $exitCode = $this->makeCommand([
            'tahun' => '2026',
            'all-years' => false,
            'dry-run' => false,
            'limit' => 0,
            'only' => null,
            'skip' => null,
        ])->handle();

        $this->assertSame(1, $exitCode);
        $this->assertSame(2, ApiSyncRun::count());

        $latestRun = ApiSyncRun::query()->orderByDesc('id')->first();
        $this->assertNotNull($latestRun);
        $this->assertSame(ApiSyncRun::STATUS_FAILED, $latestRun->status);
        $this->assertFalse($latestRun->is_global);
        $this->assertSame('rup', $latestRun->failed_module);
        $this->assertSame($finishedAt->toDateTimeString(), $latestRun->finished_at->toDateTimeString());

        $latestEligibleSuccess = ApiSyncRun::query()
            ->where('status', ApiSyncRun::STATUS_SUCCEEDED)
            ->where('is_global', true)
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->first();

        $this->assertNotNull($latestEligibleSuccess);
        $this->assertSame($olderSuccess->toDateTimeString(), $latestEligibleSuccess->finished_at->toDateTimeString());
    }

    /**
     * @param array<int, array{0: string, 1: array<string, int|string|bool>}> $calls
     */
    private function expectModuleCalls(array $calls): void
    {
        foreach ($calls as [$command, $options]) {
            Artisan::shouldReceive('call')
                ->once()
                ->with(
                    $command,
                    Mockery::on(static fn (array $actualOptions): bool => $actualOptions === $options)
                )
                ->andReturn(0)
                ->ordered();
        }
    }

    /**
     * @param array<string, int|string|bool|null> $options
     */
    private function makeCommand(array $options): UpdateAllCommand
    {
        return new class($options) extends UpdateAllCommand {
            /**
             * @param array<string, int|string|bool|null> $options
             */
            public function __construct(private array $options)
            {
                parent::__construct();
            }

            public function option($key = null): mixed
            {
                if ($key === null) {
                    return $this->options;
                }

                return $this->options[$key] ?? null;
            }

            public function line($string, $style = null, $verbosity = null): void
            {
            }

            public function info($string, $verbosity = null): void
            {
            }

            public function error($string, $verbosity = null): void
            {
            }

            public function warn($string, $verbosity = null): void
            {
            }

            protected function printHeader(): void
            {
            }

            protected function printModuleHeader(string $name, int $current, int $total): void
            {
            }

            protected function printSummary(): void
            {
            }
        };
    }
}
