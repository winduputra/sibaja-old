<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class LegacyApiRefreshContractTest extends TestCase
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

        foreach ([
            'swakelolas',
            'struktur_anggarans',
            'satkers',
            'tenders',
            'tender_schedules',
            'tender_participants',
            'vendors',
            'log_gets',
        ] as $tableName) {
            Schema::dropIfExists($tableName);
        }

        Schema::create('swakelolas', function (Blueprint $table): void {
            $table->id();
            $table->string('kd_rup')->nullable();
            $table->string('tahun_anggaran')->nullable();
            $table->string('kd_klpd')->nullable();
            $table->timestamps();
        });

        Schema::create('struktur_anggarans', function (Blueprint $table): void {
            $table->id();
            $table->string('tahun_anggaran')->nullable();
            $table->string('kd_satker')->nullable();
            $table->timestamps();
        });

        Schema::create('satkers', function (Blueprint $table): void {
            $table->id();
            $table->string('kd_satker')->nullable();
            $table->string('tahun_anggaran')->nullable();
            $table->string('kd_satker_lpse')->nullable();
            $table->timestamps();
        });

        Schema::create('tenders', function (Blueprint $table): void {
            $table->id();
            $table->string('kd_tender')->nullable();
            $table->timestamps();
        });

        Schema::create('tender_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('kd_lelang')->nullable();
            $table->string('kd_tahapan')->nullable();
            $table->timestamps();
        });

        Schema::create('tender_participants', function (Blueprint $table): void {
            $table->id();
            $table->string('kd_tender')->nullable();
            $table->string('kd_peserta')->nullable();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('kd_penyedia')->nullable();
            $table->timestamps();
        });

        Schema::create('log_gets', function (Blueprint $table): void {
            $table->id();
            $table->text('endpoint')->nullable();
            $table->longText('response')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function disabledLegacyCommandsProvider(): array
    {
        return [
            'swakelola update' => ['update:swakelola', ['swakelolas']],
            'struktur anggaran update' => ['update:struktur-anggaran', ['struktur_anggarans']],
            'satker update' => ['satker:update', ['satkers']],
            'sync satker api' => ['sync:satker-api', ['satkers']],
        ];
    }

    /**
     * @test
     * @dataProvider disabledLegacyCommandsProvider
     */
    public function disabled_legacy_http_commands_exit_non_zero_leave_sentinels_unchanged_and_do_not_request(
        string $commandName,
        array $tables
    ): void {
        $this->seedSentinelRows($tables);

        Http::fake(function (): void {
            $this->fail('Unexpected HTTP request.');
        });

        $exitCode = Artisan::call($commandName);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertDisabledSourceMessage($output);
        $this->assertSentinelRowsUnchanged($tables);
        Http::assertNothingSent();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function tender_update_is_disabled_without_hitting_guzzle_or_http(): void
    {
        $tables = ['tenders', 'tender_schedules', 'tender_participants', 'vendors', 'log_gets'];

        $this->seedSentinelRows($tables);

        Http::fake(function (): void {
            $this->fail('Unexpected HTTP request.');
        });

        $client = Mockery::mock('overload:GuzzleHttp\Client');
        $client->shouldNotReceive('get');

        $exitCode = Artisan::call('tender:update');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertDisabledSourceMessage($output);
        $this->assertSentinelRowsUnchanged($tables);
        Http::assertNothingSent();
    }

    /**
     * @test
     */
    public function update_all_delegates_only_modern_commands_in_order_and_skips_ekatalog_update(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with(
                'inaproc:sync-rup',
                Mockery::on(static fn (array $options): bool => $options === ['--tahun' => '2026', '--type' => 'all'])
            )
            ->andReturn(0)
            ->ordered();

        Artisan::shouldReceive('call')
            ->once()
            ->with(
                'inaproc:sync-tender',
                Mockery::on(static fn (array $options): bool => $options === ['--tahun' => '2026', '--type' => 'all'])
            )
            ->andReturn(0)
            ->ordered();

        Artisan::shouldReceive('call')
            ->once()
            ->with(
                'inaproc:sync-non-tender',
                Mockery::on(static fn (array $options): bool => $options === ['--tahun' => '2026', '--type' => 'all'])
            )
            ->andReturn(0)
            ->ordered();

        Artisan::shouldReceive('call')
            ->once()
            ->with(
                'inaproc:sync-pencatatan-swakelola',
                Mockery::on(static fn (array $options): bool => $options === ['--tahun' => '2026', '--type' => 'all'])
            )
            ->andReturn(0)
            ->ordered();

        Artisan::shouldReceive('call')
            ->once()
            ->with(
                'inaproc:sync-ekatalog-v6',
                Mockery::on(static fn (array $options): bool => $options === ['--tahun' => '2026'])
            )
            ->andReturn(0)
            ->ordered();

        Artisan::shouldNotReceive('call')->with('ekatalog:update', Mockery::any());

        $command = new class extends \App\Console\Commands\UpdateAllCommand {
            public function option($key = null): mixed
            {
                return match ($key) {
                    'tahun' => '2026',
                    'all-years' => false,
                    'dry-run' => false,
                    'limit' => 0,
                    'only' => null,
                    'skip' => null,
                    default => null,
                };
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

        $exitCode = $command->handle();

        $this->assertSame(0, $exitCode);
    }

    /**
     * @test
     */
    public function update_all_stops_on_first_failed_modern_delegate_and_shows_retry_guidance(): void
    {
        $capturedErrors = [];

        Artisan::shouldReceive('call')
            ->once()
            ->with(
                'inaproc:sync-rup',
                Mockery::on(static fn (array $options): bool => $options === ['--tahun' => '2026', '--type' => 'all'])
            )
            ->andReturn(1);

        $command = new class($capturedErrors) extends \App\Console\Commands\UpdateAllCommand {
            public function __construct(private array &$capturedErrors)
            {
                parent::__construct();
            }

            public function option($key = null): mixed
            {
                return match ($key) {
                    'tahun' => '2026',
                    'all-years' => false,
                    'dry-run' => false,
                    'limit' => 0,
                    'only' => null,
                    'skip' => null,
                    default => null,
                };
            }

            public function line($string, $style = null, $verbosity = null): void
            {
            }

            public function info($string, $verbosity = null): void
            {
            }

            public function error($string, $verbosity = null): void
            {
                $this->capturedErrors[] = (string) $string;
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

        $exitCode = $command->handle();

        $this->assertSame(1, $exitCode);
        $this->assertNotEmpty(array_filter($capturedErrors, static fn (string $message): bool => str_contains($message, 'Silakan coba lagi')));
        $this->assertNotEmpty(array_filter($capturedErrors, static fn (string $message): bool => str_contains($message, 'inaproc:sync-rup')));
    }

    /**
     * @param array<int, string> $tables
     */
    private function seedSentinelRows(array $tables): void
    {
        foreach ($tables as $tableName) {
            DB::table($tableName)->insert($this->seedRowForTable($tableName));
        }
    }

    /**
     * @param array<int, string> $tables
     */
    private function assertSentinelRowsUnchanged(array $tables): void
    {
        foreach ($tables as $tableName) {
            $expectedRow = $this->seedRowForTable($tableName);
            $actualRow = (array) DB::table($tableName)->first();

            $this->assertSame(1, DB::table($tableName)->count());

            foreach ($expectedRow as $column => $value) {
                $this->assertSame($value, $actualRow[$column] ?? null, $tableName . '.' . $column);
            }
        }
    }

    private function assertDisabledSourceMessage(string $output): void
    {
        $this->assertMatchesRegularExpression('/(?:sumber|legacy).*(?:dinonaktifkan|dimatikan|nonaktif)|(?:dinonaktifkan|dimatikan|nonaktif).*(?:sumber|legacy)/i', $output);
    }

    /**
     * @return array<string, string|int>
     */
    private function seedRowForTable(string $tableName): array
    {
        return match ($tableName) {
            'swakelolas' => ['kd_rup' => 'OLD-SWAK', 'tahun_anggaran' => '2026', 'kd_klpd' => 'D264'],
            'struktur_anggarans' => ['tahun_anggaran' => '2026', 'kd_satker' => 'OLD-STR'],
            'satkers' => ['kd_satker' => 'OLD-SAT', 'tahun_anggaran' => '2026', 'kd_satker_lpse' => 'OLD-SAT-LPSE'],
            'tenders' => ['kd_tender' => 'OLD-TENDER'],
            'tender_schedules' => ['kd_lelang' => 'OLD-TENDER', 'kd_tahapan' => 'OLD-STEP'],
            'tender_participants' => ['kd_tender' => 'OLD-TENDER', 'kd_peserta' => 'OLD-PESERTA'],
            'vendors' => ['kd_penyedia' => 'OLD-VENDOR'],
            'log_gets' => ['endpoint' => 'old', 'response' => 'old'],
            default => [],
        };
    }
}
