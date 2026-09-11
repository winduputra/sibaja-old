<?php

namespace Tests\Unit\Console;

use App\Services\InaprocRekapNasionalBrowser;
use App\Services\InaprocRekapNasionalTextParser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DestructiveInaprocSyncCommandsTest extends TestCase
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

        $this->createTables([
            'tender_pengumuman_data',
            'kontrak_data',
            'tender_selesai_nilai_data',
            'non_tender_pengumuman',
            'non_tender_selesai',
            'non_tender_contract',
            'non_tender_pencatatan',
            'non_tender_realisasi',
            'swakelola_realisasi',
            'ekatalog_v6_pakets',
            'rekapitulasi_nasional',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /** @test */
    public function tender_sync_truncates_selected_table_before_request_and_surfaces_retry_guidance(): void
    {
        $this->seedTables([
            'tender_pengumuman_data',
            'kontrak_data',
            'tender_selesai_nilai_data',
        ]);

        Http::fake([
            '*' => function ($request) {
                $this->assertStringContainsString('tender/pengumuman', $request->url());
                $this->assertSame(0, DB::table('tender_pengumuman_data')->count());
                $this->assertSame(1, DB::table('kontrak_data')->count());
                $this->assertSame(1, DB::table('tender_selesai_nilai_data')->count());

                return Http::response(['message' => 'upstream failed'], 500);
            },
        ]);

        $exitCode = Artisan::call('inaproc:sync-tender', [
            '--type' => 'pengumuman',
            '--tahun' => 2026,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, DB::table('tender_pengumuman_data')->count());
        $this->assertSame(1, DB::table('kontrak_data')->count());
        $this->assertSame(1, DB::table('tender_selesai_nilai_data')->count());
        $this->assertRetryGuidance(Artisan::output());
    }

    /** @test */
    public function tender_sync_dry_run_keeps_existing_rows_intact(): void
    {
        $this->seedTables([
            'tender_pengumuman_data',
            'kontrak_data',
            'tender_selesai_nilai_data',
        ]);

        Http::fake([
            '*' => function ($request) {
                $this->assertStringContainsString('tender/pengumuman', $request->url());
                $this->assertSame(1, DB::table('tender_pengumuman_data')->count());
                $this->assertSame(1, DB::table('kontrak_data')->count());
                $this->assertSame(1, DB::table('tender_selesai_nilai_data')->count());

                return Http::response(['data' => []], 200);
            },
        ]);

        $exitCode = Artisan::call('inaproc:sync-tender', [
            '--type' => 'pengumuman',
            '--tahun' => 2026,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, DB::table('tender_pengumuman_data')->count());
        $this->assertSame(1, DB::table('kontrak_data')->count());
        $this->assertSame(1, DB::table('tender_selesai_nilai_data')->count());
        $this->assertStringContainsString('Dry run mode', Artisan::output());
    }

    /** @test */
    public function rup_sync_fails_when_item_errors_are_counted(): void
    {
        $capturedErrors = [];

        $command = new class($capturedErrors) extends \App\Console\Commands\RupSyncCommand {
            private array $capturedErrors;

            public function __construct(array &$capturedErrors)
            {
                parent::__construct();
                $this->capturedErrors =& $capturedErrors;
            }

            public function option($key = null): mixed
            {
                return match ($key) {
                    'dry-run' => true,
                    'limit' => 0,
                    'type' => 'penyedia',
                    'all-years' => false,
                    'tahun' => '2026',
                    default => null,
                };
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
                $this->capturedErrors[] = (string) $string;
            }

            protected function syncMasterSatker(): void
            {
            }

            protected function syncPaketPenyedia(): void
            {
                $this->errors++;
            }

            protected function syncPaketSwakelola(): void
            {
            }

            protected function syncHistoryKajiUlang(): void
            {
            }
        };

        $exitCode = $command->handle();

        $this->assertSame(1, $exitCode);
        $this->assertNotEmpty(array_filter($capturedErrors, static fn (string $message): bool => str_contains($message, 'Silakan coba lagi')));
    }

    /** @test */
    public function tender_sync_fails_when_item_errors_are_counted(): void
    {
        $capturedErrors = [];

        $command = new class($capturedErrors) extends \App\Console\Commands\TenderSyncCommand {
            private array $capturedErrors;

            public function __construct(array &$capturedErrors)
            {
                parent::__construct();
                $this->capturedErrors =& $capturedErrors;
            }

            public function option($key = null): mixed
            {
                return match ($key) {
                    'dry-run' => true,
                    'limit' => 0,
                    'type' => 'pengumuman',
                    'all-years' => false,
                    'tahun' => '2026',
                    default => null,
                };
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
                $this->capturedErrors[] = (string) $string;
            }

            protected function syncPengumuman(): void
            {
                $this->errors++;
            }

            protected function syncEkontrakKontrak(): void
            {
            }

            protected function syncSelesaiNilai(): void
            {
            }
        };

        $exitCode = $command->handle();

        $this->assertSame(1, $exitCode);
        $this->assertNotEmpty(array_filter($capturedErrors, static fn (string $message): bool => str_contains($message, 'Silakan coba lagi')));
    }

    /** @test */
    public function non_tender_sync_truncates_selected_table_before_request_and_surfaces_retry_guidance(): void
    {
        $this->seedTables([
            'non_tender_pengumuman',
            'non_tender_selesai',
            'non_tender_contract',
            'non_tender_pencatatan',
            'non_tender_realisasi',
        ]);

        Http::fake([
            '*' => function ($request) {
                $this->assertStringContainsString('pencatatan-non-tender', $request->url());
                $this->assertSame(1, DB::table('non_tender_pengumuman')->count());
                $this->assertSame(1, DB::table('non_tender_selesai')->count());
                $this->assertSame(1, DB::table('non_tender_contract')->count());
                $this->assertSame(0, DB::table('non_tender_pencatatan')->count());
                $this->assertSame(1, DB::table('non_tender_realisasi')->count());

                return Http::response(['message' => 'upstream failed'], 500);
            },
        ]);

        $exitCode = Artisan::call('inaproc:sync-non-tender', [
            '--type' => 'planning',
            '--tahun' => 2026,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(1, DB::table('non_tender_pengumuman')->count());
        $this->assertSame(1, DB::table('non_tender_selesai')->count());
        $this->assertSame(1, DB::table('non_tender_contract')->count());
        $this->assertSame(0, DB::table('non_tender_pencatatan')->count());
        $this->assertSame(1, DB::table('non_tender_realisasi')->count());
        $this->assertRetryGuidance(Artisan::output());
    }

    /** @test */
    public function pencatatan_swakelola_sync_truncates_realisasi_table_before_request_and_surfaces_retry_guidance(): void
    {
        $this->seedTables(['swakelola_realisasi']);

        Http::fake([
            '*' => function ($request) {
                $this->assertStringContainsString('pencatatan-swakelola-realisasi', $request->url());
                $this->assertSame(0, DB::table('swakelola_realisasi')->count());

                return Http::response(['message' => 'upstream failed'], 500);
            },
        ]);

        $exitCode = Artisan::call('inaproc:sync-pencatatan-swakelola', [
            '--type' => 'all',
            '--tahun' => 2026,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, DB::table('swakelola_realisasi')->count());
        $this->assertRetryGuidance(Artisan::output());
    }

    /** @test */
    public function ekatalog_v6_sync_truncates_paket_table_before_request_and_surfaces_retry_guidance(): void
    {
        $this->seedTables(['ekatalog_v6_pakets']);

        Http::fake([
            '*' => function ($request) {
                $this->assertStringContainsString('ekatalog/paket-e-purchasing', $request->url());
                $this->assertSame(0, DB::table('ekatalog_v6_pakets')->count());

                return Http::response(['message' => 'upstream failed'], 500);
            },
        ]);

        $exitCode = Artisan::call('inaproc:sync-ekatalog-v6', [
            '--tahun' => 2026,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, DB::table('ekatalog_v6_pakets')->count());
        $this->assertRetryGuidance(Artisan::output());
    }

    /** @test */
    public function rekap_nasional_sync_truncates_target_table_before_scrape_and_surfaces_retry_guidance(): void
    {
        $this->seedTables(['rekapitulasi_nasional']);

        $browser = Mockery::mock(InaprocRekapNasionalBrowser::class);
        $browser->shouldReceive('scrape')
            ->once()
            ->andReturnUsing(function (string $url, int $timeout): string {
                $this->assertStringContainsString('instansi=D95', $url);
                $this->assertSame(0, DB::table('rekapitulasi_nasional')->count());

                throw new RuntimeException('Scrape failed');
            });

        $parser = Mockery::mock(InaprocRekapNasionalTextParser::class);

        app()->instance(InaprocRekapNasionalBrowser::class, $browser);
        app()->instance(InaprocRekapNasionalTextParser::class, $parser);

        $exitCode = Artisan::call('inaproc:sync-rekap-nasional', [
            '--province' => 'D95',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, DB::table('rekapitulasi_nasional')->count());
        $this->assertRetryGuidance(Artisan::output());
    }

    /**
     * @param array<int, string> $tables
     */
    private function createTables(array $tables): void
    {
        foreach ($tables as $tableName) {
            Schema::dropIfExists($tableName);

            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('marker')->nullable();
            });
        }
    }

    /**
     * @param array<int, string> $tables
     */
    private function seedTables(array $tables): void
    {
        foreach ($tables as $tableName) {
            DB::table($tableName)->insert([
                'marker' => 'old-row',
            ]);
        }
    }

    private function assertRetryGuidance(string $output): void
    {
        $this->assertMatchesRegularExpression('/(coba lagi|retry|ulangi)/i', $output);
    }
}
