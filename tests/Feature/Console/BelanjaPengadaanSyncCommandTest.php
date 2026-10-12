<?php

namespace Tests\Feature\Console;

use App\Services\InaprocRekapNasionalBrowser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class BelanjaPengadaanSyncCommandTest extends TestCase
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
            $table->string('kd_satker_str')->nullable();
            $table->decimal('belanja_pengadaan', 20, 2)->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /** @test */
    public function it_continues_after_a_satker_failure_and_persists_digits_from_the_next_satker(): void
    {
        DB::table('satkers')->insert([
            [
                'tahun_anggaran' => '2026',
                'kd_satker' => '100001',
                'kd_satker_str' => 'SATKER-FAIL',
            ],
            [
                'tahun_anggaran' => '2026',
                'kd_satker' => '100002',
                'kd_satker_str' => 'SATKER-OK',
            ],
        ]);

        $browser = Mockery::mock(InaprocRekapNasionalBrowser::class);
        $browser->shouldReceive('extractFirst')
            ->once()
            ->ordered()
            ->with(
                'https://data.inaproc.id/profil-pengadaan?tahun=2026&jenis=3&instansi=D264&satker=SATKER-FAIL&view=Nilai',
                'p.font-bold.tabular-nums',
                5
            )
            ->andThrow(new RuntimeException('Timed out'));
        $browser->shouldReceive('extractFirst')
            ->once()
            ->ordered()
            ->with(
                'https://data.inaproc.id/profil-pengadaan?tahun=2026&jenis=3&instansi=D264&satker=SATKER-OK&view=Nilai',
                'p.font-bold.tabular-nums',
                5
            )
            ->andReturn('Rp 1.234.567.890');

        app()->instance(InaprocRekapNasionalBrowser::class, $browser);

        $exitCode = Artisan::call('inaproc:sync-belanja-pengadaan', [
            '--timeout' => 5,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertNull(DB::table('satkers')->where('kd_satker_str', 'SATKER-FAIL')->value('belanja_pengadaan'));
        $this->assertSame(
            '1234567890',
            (string) DB::table('satkers')->where('kd_satker_str', 'SATKER-OK')->value('belanja_pengadaan')
        );
        $this->assertStringContainsString('SATKER-FAIL', $output);
        $this->assertStringContainsString('SATKER-OK', $output);
    }
}
