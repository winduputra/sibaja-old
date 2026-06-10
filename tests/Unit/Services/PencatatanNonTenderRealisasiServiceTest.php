<?php

namespace Tests\Unit\Services;

use App\Services\PencatatanNonTenderRealisasiService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PencatatanNonTenderRealisasiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'api.inaproc.kode_klpd' => 'D264',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('non_tender_pencatatan', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun_anggaran')->nullable();
            $table->string('kd_klpd')->nullable();
            $table->string('kd_satker')->nullable();
            $table->string('nama_satker')->nullable();
            $table->string('kd_nontender_pct')->nullable();
            $table->string('kd_rup')->nullable();
            $table->string('kd_pkt_dce')->nullable();
            $table->decimal('pagu', 20, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('non_tender_realisasi', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun_anggaran')->nullable();
            $table->string('kd_klpd')->nullable();
            $table->string('kd_satker')->nullable();
            $table->string('nama_satker')->nullable();
            $table->string('kd_nontender_pct')->nullable();
            $table->string('kd_rup_paket')->nullable();
            $table->string('no_realisasi')->nullable();
            $table->decimal('pagu', 20, 2)->nullable();
            $table->decimal('nilai_realisasi', 20, 2)->nullable();
            $table->timestamps();
        });
    }

    /** @test */
    public function it_aggregates_planning_and_realization_by_satker_from_database()
    {
        DB::table('non_tender_pencatatan')->insert([
            [
                'tahun_anggaran' => 2026,
                'kd_klpd' => 'D264',
                'kd_satker' => '1',
                'nama_satker' => 'Badan Kepegawaian Daerah',
                'kd_nontender_pct' => 'PCT-1',
                'pagu' => 1000000,
            ],
            [
                'tahun_anggaran' => 2026,
                'kd_klpd' => 'D264',
                'kd_satker' => '1',
                'nama_satker' => 'Badan Kepegawaian Daerah',
                'kd_nontender_pct' => 'PCT-2',
                'pagu' => 500000,
            ],
            [
                'tahun_anggaran' => 2026,
                'kd_klpd' => 'D264',
                'kd_satker' => '2',
                'nama_satker' => 'Badan Kesatuan Bangsa dan Politik',
                'kd_nontender_pct' => 'PCT-3',
                'pagu' => 2000000,
            ],
        ]);

        DB::table('non_tender_realisasi')->insert([
            [
                'tahun_anggaran' => 2026,
                'kd_klpd' => 'D264',
                'kd_satker' => '1',
                'nama_satker' => 'Badan Kepegawaian Daerah',
                'kd_nontender_pct' => 'PCT-1',
                'pagu' => null,
                'nilai_realisasi' => 750000,
            ],
            [
                'tahun_anggaran' => 2026,
                'kd_klpd' => 'D264',
                'kd_satker' => '1',
                'nama_satker' => 'Badan Kepegawaian Daerah',
                'kd_nontender_pct' => 'PCT-2',
                'pagu' => 500000,
                'nilai_realisasi' => 0,
            ],
            [
                'tahun_anggaran' => 2026,
                'kd_klpd' => 'D264',
                'kd_satker' => '2',
                'nama_satker' => 'Badan Kesatuan Bangsa dan Politik',
                'kd_nontender_pct' => 'PCT-3',
                'pagu' => null,
                'nilai_realisasi' => 2000000,
            ],
        ]);

        $report = (new PencatatanNonTenderRealisasiService())->report(2026);

        $this->assertCount(2, $report['rows']);

        $first = $report['rows'][0];
        $this->assertSame('Badan Kepegawaian Daerah', $first['nama_opd']);
        $this->assertSame(2, $first['perencanaan_paket']);
        $this->assertSame(1500000.0, $first['perencanaan_pagu']);
        $this->assertSame(2, $first['tercatat_paket']);
        $this->assertSame(750000.0, $first['tercatat_pagu']);
        $this->assertSame(0, $first['belum_tercatat_paket']);
        $this->assertSame(750000.0, $first['belum_tercatat_pagu']);
        $this->assertSame(50.0, $first['persentase_tercatat']);

        $this->assertSame(3, $report['summary']['perencanaan_paket']);
        $this->assertSame(3500000.0, $report['summary']['perencanaan_pagu']);
        $this->assertSame(3, $report['summary']['tercatat_paket']);
        $this->assertSame(2750000.0, $report['summary']['tercatat_pagu']);
        $this->assertSame(78.57, $report['summary']['persentase_tercatat']);
        $this->assertNull($report['apiError']);
    }
}
