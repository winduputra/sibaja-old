<?php

namespace Tests\Unit\Services;

use App\Services\PencatatanSwakelolaRealisasiService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PencatatanSwakelolaRealisasiServiceTest extends TestCase
{
    /** @test */
    public function it_aggregates_planning_and_realization_by_satker()
    {
        config([
            'api.inaproc.kode_klpd' => 'D264',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->createTables();

        DB::table('swakelolas')->insert([
            ['tahun_anggaran' => 2026, 'kd_klpd' => 'D264', 'kd_satker' => '1', 'nama_satker' => 'Badan Kepegawaian Daerah', 'kd_rup' => 1, 'pagu' => 1000000, 'status_umumkan_rup' => 'Terumumkan', 'status_aktif_rup' => 1],
            ['tahun_anggaran' => 2026, 'kd_klpd' => 'D264', 'kd_satker' => '1', 'nama_satker' => 'Badan Kepegawaian Daerah', 'kd_rup' => 2, 'pagu' => 500000, 'status_umumkan_rup' => 'Terumumkan', 'status_aktif_rup' => 1],
            ['tahun_anggaran' => 2026, 'kd_klpd' => 'D264', 'kd_satker' => '2', 'nama_satker' => 'Badan Kesatuan Bangsa dan Politik', 'kd_rup' => 3, 'pagu' => 2000000, 'status_umumkan_rup' => 'Terumumkan', 'status_aktif_rup' => 1],
        ]);

        DB::table('swakelola_realisasi')->insert([
            ['tahun_anggaran' => 2026, 'kd_klpd' => 'D264', 'kd_satker' => '1', 'nama_satker' => 'Badan Kepegawaian Daerah', 'kd_swakelola_pct' => 1, 'nilai_realisasi' => 750000],
            ['tahun_anggaran' => 2026, 'kd_klpd' => 'D264', 'kd_satker' => '2', 'nama_satker' => 'Badan Kesatuan Bangsa dan Politik', 'kd_swakelola_pct' => 3, 'nilai_realisasi' => 2000000],
        ]);

        $report = (new PencatatanSwakelolaRealisasiService())->report(2026);

        $this->assertCount(2, $report['rows']);

        $first = $report['rows'][0];
        $this->assertSame('Badan Kepegawaian Daerah', $first['nama_opd']);
        $this->assertSame(2, $first['perencanaan_paket']);
        $this->assertSame(1500000.0, $first['perencanaan_pagu']);
        $this->assertSame(1, $first['tercatat_paket']);
        $this->assertSame(750000.0, $first['tercatat_pagu']);
        $this->assertSame(1, $first['belum_tercatat_paket']);
        $this->assertSame(750000.0, $first['belum_tercatat_pagu']);
        $this->assertSame(50.0, $first['persentase_tercatat']);

        $this->assertSame(3, $report['summary']['perencanaan_paket']);
        $this->assertSame(3500000.0, $report['summary']['perencanaan_pagu']);
        $this->assertSame(2, $report['summary']['tercatat_paket']);
        $this->assertSame(2750000.0, $report['summary']['tercatat_pagu']);
        $this->assertSame(78.57, $report['summary']['persentase_tercatat']);
    }

    private function createTables(): void
    {
        Schema::create('swakelolas', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun_anggaran')->nullable();
            $table->string('kd_klpd')->nullable();
            $table->string('kd_satker')->nullable();
            $table->string('nama_satker')->nullable();
            $table->bigInteger('kd_rup')->nullable();
            $table->decimal('pagu', 20, 2)->nullable();
            $table->string('status_umumkan_rup')->nullable();
            $table->boolean('status_aktif_rup')->nullable();
            $table->timestamps();
        });

        Schema::create('swakelola_realisasi', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun_anggaran')->nullable();
            $table->string('kd_klpd')->nullable();
            $table->string('kd_satker')->nullable();
            $table->string('nama_satker')->nullable();
            $table->bigInteger('kd_swakelola_pct')->nullable();
            $table->string('no_realisasi')->nullable();
            $table->decimal('nilai_realisasi', 20, 2)->nullable();
            $table->timestamps();
        });
    }
}
