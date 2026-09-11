<?php

namespace Tests\Unit\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DestructiveApiImportRefreshContractTest extends TestCase
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
        Cache::flush();

        Schema::dropIfExists('penyedias');
        Schema::create('penyedias', function (Blueprint $table) {
            $table->id();
            $table->string('kd_rup')->nullable();
            $table->unsignedInteger('tahun_anggaran')->nullable();
            $table->string('kd_klpd')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /** @test */
    public function modern_rup_sync_empties_target_rows_before_fetching_and_fails_with_retry_guidance(): void
    {
        DB::table('penyedias')->insert([
            'kd_rup' => 'OLD-RUP-1',
            'tahun_anggaran' => 2026,
            'kd_klpd' => 'D264',
        ]);

        Http::fake([
            'https://data.inaproc.id/*' => function ($request) {
                $this->assertSame(0, DB::table('penyedias')->count());
                $this->assertStringContainsString('rup/paket-penyedia-terumumkan', $request->url());

                return Http::response(['message' => 'upstream failed'], 500);
            },
        ]);

        $exitCode = Artisan::call('inaproc:sync-rup', [
            '--type' => 'penyedia',
            '--tahun' => 2026,
        ]);

        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, DB::table('penyedias')->count());
        $this->assertMatchesRegularExpression('/(retry|coba lagi|ulangi)/i', $output);
    }

    /** @test */
    public function legacy_penyedia_update_is_disabled_and_keeps_existing_rows_intact(): void
    {
        DB::table('penyedias')->insert([
            'kd_rup' => 'OLD-LEGACY-1',
            'tahun_anggaran' => 2025,
            'kd_klpd' => 'D264',
        ]);

        Http::fake();

        $exitCode = Artisan::call('update:penyedia');

        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertSame(1, DB::table('penyedias')->count());
        $this->assertDatabaseHas('penyedias', [
            'kd_rup' => 'OLD-LEGACY-1',
            'tahun_anggaran' => 2025,
            'kd_klpd' => 'D264',
        ]);
        $this->assertMatchesRegularExpression('/dinonaktifkan/i', $output);
        Http::assertNothingSent();
    }
}
