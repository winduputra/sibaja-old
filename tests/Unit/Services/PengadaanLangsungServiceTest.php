<?php

namespace Tests\Unit\Services;

use App\Services\InaprocinaproApiClient;
use App\Services\PengadaanLangsungService;
use Mockery;
use Tests\TestCase;

class PengadaanLangsungServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /** @test */
    public function it_filters_finished_pengadaan_langsung_rows_and_joins_provider_data()
    {
        config(['api.inaproc.kode_klpd' => 'D264']);

        $client = Mockery::mock(InaprocinaproApiClient::class);
        $client->shouldReceive('request')
            ->once()
            ->with('tender/pencatatan-non-tender', [
                'kode_klpd' => 'D264',
                'tahun' => 2024,
                'limit' => 1000,
            ])
            ->andReturn([
                'data' => [
                    [
                        'kd_nontender_pct' => 'PCT-1',
                        'nama_satker' => 'Dinas Kesehatan',
                        'nama_paket' => 'Belanja ATK',
                        'pagu' => 1000000,
                        'mtd_pemilihan' => 'Pengadaan Langsung',
                        'status_nontender_pct_ket' => 'Paket Selesai',
                    ],
                    [
                        'kd_nontender_pct' => 'PCT-2',
                        'nama_satker' => 'Dinas Pendidikan',
                        'nama_paket' => 'Belanja Buku',
                        'pagu' => 2000000,
                        'mtd_pemilihan' => 'E-Purchasing',
                        'status_nontender_pct_ket' => 'Paket Selesai',
                    ],
                    [
                        'kd_nontender_pct' => 'PCT-3',
                        'nama_satker' => 'Dinas Sosial',
                        'nama_paket' => 'Belanja Paket',
                        'pagu' => 3000000,
                        'mtd_pemilihan' => 'Pengadaan Langsung',
                        'status_nontender_pct_ket' => 'Paket Berjalan',
                    ],
                ],
            ]);

        $client->shouldReceive('request')
            ->once()
            ->with('tender/pencatatan-non-tender-realisasi', [
                'kode_klpd' => 'D264',
                'tahun' => 2024,
                'limit' => 1000,
            ])
            ->andReturn([
                'data' => [
                    [
                        'kd_nontender_pct' => 'PCT-1',
                        'nama_penyedia' => 'CV Lampung Maju',
                        'alamat_penyedia' => 'Bandar Lampung',
                        'total_realisasi' => 900000,
                    ],
                ],
            ]);

        $report = (new PengadaanLangsungService($client))->report(2024);

        $this->assertSame(2024, $report['selectedYear']);
        $this->assertSame([2024, 2025, 2026], $report['years']);
        $this->assertNull($report['apiError']);
        $this->assertCount(1, $report['rows']);
        $this->assertSame('Dinas Kesehatan', $report['rows'][0]['perangkat_daerah']);
        $this->assertSame('Belanja ATK', $report['rows'][0]['nama_paket']);
        $this->assertSame('', $report['rows'][0]['kegiatan']);
        $this->assertSame('CV Lampung Maju', $report['rows'][0]['penyedia']);
        $this->assertSame('Bandar Lampung', $report['rows'][0]['alamat_penyedia']);
        $this->assertSame(1000000.0, $report['rows'][0]['nilai_pagu']);
        $this->assertSame(900000.0, $report['rows'][0]['nilai_final']);
    }

    /** @test */
    public function it_uses_2026_when_requested_year_is_not_supported()
    {
        $client = Mockery::mock(InaprocinaproApiClient::class);
        $client->shouldReceive('request')->twice()->andReturn(['data' => []]);

        $report = (new PengadaanLangsungService($client))->report(2030);

        $this->assertSame(2026, $report['selectedYear']);
    }
}
