<?php

namespace Tests\Unit\Console;

use App\Console\Commands\RupSyncCommand;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RupSyncCommandSatkerPruningTest extends TestCase
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

        Schema::create('satkers', function (Blueprint $table): void {
            $table->id();
            $table->string('tahun_anggaran');
            $table->string('kd_satker');
        });

        foreach (['penyedias', 'swakelolas'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('kd_satker');
                $table->boolean('status_aktif_rup')->default(false);
                $table->string('status_umumkan_rup')->nullable();
            });
        }

        DB::table('satkers')->insert([
            ['tahun_anggaran' => '2026', 'kd_satker' => '1001'],
            ['tahun_anggaran' => '2026', 'kd_satker' => '1002'],
            ['tahun_anggaran' => '2026', 'kd_satker' => '1003'],
            ['tahun_anggaran' => '2026', 'kd_satker' => '1004'],
            ['tahun_anggaran' => '2025', 'kd_satker' => '1005'],
        ]);
    }

    /** @test */
    public function full_successful_sync_prunes_only_2026_satkers_without_active_announced_packages(): void
    {
        $exitCode = $this->createCommand()->handle();

        $this->assertSame(0, $exitCode);
        $this->assertSame(
            ['1001', '1002', '1005'],
            DB::table('satkers')->orderBy('kd_satker')->pluck('kd_satker')->all()
        );
    }

    /**
     * @test
     * @dataProvider unsafeRunOptionsProvider
     */
    public function incomplete_runs_do_not_prune_satkers(array $options): void
    {
        $exitCode = $this->createCommand($options)->handle();

        $this->assertSame(0, $exitCode);
        $this->assertSame(5, DB::table('satkers')->count());
    }

    /** @test */
    public function sync_with_item_errors_fails_without_pruning_satkers(): void
    {
        $exitCode = $this->createCommand([], true)->handle();

        $this->assertSame(1, $exitCode);
        $this->assertSame(5, DB::table('satkers')->count());
    }

    public static function unsafeRunOptionsProvider(): array
    {
        return [
            'limited sync' => [['limit' => 1]],
            'dry run' => [['dry-run' => true]],
            'partial sync' => [['type' => 'penyedia']],
        ];
    }

    private function createCommand(array $options = [], bool $hasErrors = false): RupSyncCommand
    {
        $resolvedOptions = array_merge([
            'dry-run' => false,
            'limit' => 0,
            'type' => 'all',
            'all-years' => false,
            'tahun' => '2026',
        ], $options);

        return new class($resolvedOptions, $hasErrors) extends RupSyncCommand {
            private array $resolvedOptions;
            private bool $hasErrors;

            public function __construct(array $resolvedOptions, bool $hasErrors)
            {
                parent::__construct();
                $this->resolvedOptions = $resolvedOptions;
                $this->hasErrors = $hasErrors;
            }

            public function option($key = null): mixed
            {
                return $this->resolvedOptions[$key] ?? null;
            }

            public function info($string, $verbosity = null): void
            {
            }

            public function line($string, $style = null, $verbosity = null): void
            {
            }

            public function warn($string, $verbosity = null): void
            {
            }

            public function error($string, $verbosity = null): void
            {
            }

            protected function getRefreshTargets(string $type): array
            {
                return [];
            }

            protected function syncMasterSatker(): void
            {
            }

            protected function syncPaketPenyedia(): void
            {
                if ($this->hasErrors) {
                    $this->errors++;

                    return;
                }

                if ($this->dryRun) {
                    return;
                }

                DB::table('penyedias')->insert([
                    [
                        'kd_satker' => '1001',
                        'status_aktif_rup' => true,
                        'status_umumkan_rup' => 'Terumumkan',
                    ],
                    [
                        'kd_satker' => '1003',
                        'status_aktif_rup' => false,
                        'status_umumkan_rup' => 'Terumumkan',
                    ],
                ]);
            }

            protected function syncPaketSwakelola(): void
            {
                if ($this->dryRun) {
                    return;
                }

                DB::table('swakelolas')->insert([
                    'kd_satker' => '1002',
                    'status_aktif_rup' => true,
                    'status_umumkan_rup' => 'Terumumkan',
                ]);
            }

            protected function syncHistoryKajiUlang(): void
            {
            }
        };
    }
}
