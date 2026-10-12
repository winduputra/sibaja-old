<?php

namespace Tests\Unit\Services;

use App\Services\KatalogBulananReportService;
use App\Services\PencatatanNonTenderBulananReportService;
use App\Services\PencatatanNonTenderRealisasiService;
use Mockery;
use Tests\TestCase;

class PencatatanNonTenderBulananReportServiceTest extends TestCase
{
    public function test_report_uses_katalog_roster_and_zero_fills_missing_non_tender_rows(): void
    {
        $katalogService = Mockery::mock(KatalogBulananReportService::class);
        $katalogService->shouldReceive('report')
            ->once()
            ->with(2026, 'Agustus')
            ->andReturn(['rows' => $this->katalogRows()]);

        $nonTenderService = Mockery::mock(PencatatanNonTenderRealisasiService::class);
        $nonTenderService->shouldReceive('report')
            ->once()
            ->with(2026)
            ->andReturn(['rows' => $this->annualRows()]);

        $report = (new PencatatanNonTenderBulananReportService(
            $katalogService,
            $nonTenderService
        ))->report(2026, 'Agustus');

        $this->assertSame('pencatatan-non-tender', $report['sheetSlug']);
        $this->assertSame('Pencatatan Non Tender', $report['sheetTitle']);
        $this->assertSame(2026, $report['tahun']);
        $this->assertSame('Agustus', $report['bulan']);
        $this->assertSame('Agustus 2026', $report['selectedPeriodLabel']);
        $this->assertCount(49, $report['rows']);
        $this->assertSame(range(1, 49), array_column($report['rows'], 'number'));
        $this->assertSame(array_column($this->katalogRows(), 'organizationName'), array_column($report['rows'], 'nama_opd'));
        $this->assertNotContains('Sekretariat Daerah', array_column($report['rows'], 'nama_opd'));

        $this->assertSame([
            'number' => 1,
            'nama_opd' => 'Perangkat Daerah 01',
            'perencanaan_paket' => 2,
            'perencanaan_pagu' => 1500.0,
            'tercatat_paket' => 1,
            'tercatat_pagu' => 750.0,
            'belum_tercatat_paket' => 1,
            'belum_tercatat_pagu' => 750.0,
            'persentase_tercatat' => 50.0,
        ], $report['rows'][0]);

        $this->assertSame([
            'number' => 2,
            'nama_opd' => 'Perangkat Daerah 02',
            'perencanaan_paket' => 0,
            'perencanaan_pagu' => 0.0,
            'tercatat_paket' => 0,
            'tercatat_pagu' => 0.0,
            'belum_tercatat_paket' => 0,
            'belum_tercatat_pagu' => 0.0,
            'persentase_tercatat' => 0.0,
        ], $report['rows'][1]);

        $this->assertSame([
            'perencanaan_paket' => 11,
            'perencanaan_pagu' => 8000.0,
            'tercatat_paket' => 6,
            'tercatat_pagu' => 4000.0,
            'belum_tercatat_paket' => 5,
            'belum_tercatat_pagu' => 4000.0,
            'persentase_tercatat' => 50.0,
        ], $report['summary']);
    }

    public function test_month_changes_only_report_context_not_annual_non_tender_values(): void
    {
        $katalogService = Mockery::mock(KatalogBulananReportService::class);
        $katalogService->shouldReceive('report')
            ->once()
            ->with(2026, 'September')
            ->andReturn(['rows' => $this->katalogRows()]);

        $nonTenderService = Mockery::mock(PencatatanNonTenderRealisasiService::class);
        $nonTenderService->shouldReceive('report')
            ->once()
            ->with(2026)
            ->andReturn(['rows' => $this->annualRows()]);

        $report = (new PencatatanNonTenderBulananReportService(
            $katalogService,
            $nonTenderService
        ))->report(2026, 'September');

        $this->assertSame('September 2026', $report['selectedPeriodLabel']);
        $this->assertSame(1500.0, $report['rows'][0]['perencanaan_pagu']);
        $this->assertSame(750.0, $report['rows'][0]['tercatat_pagu']);
        $this->assertSame(50.0, $report['rows'][0]['persentase_tercatat']);
        $this->assertSame(8000.0, $report['summary']['perencanaan_pagu']);
        $this->assertSame(4000.0, $report['summary']['tercatat_pagu']);
    }

    private function katalogRows(): array
    {
        return array_map(
            fn (int $number): array => ['organizationName' => sprintf('Perangkat Daerah %02d', $number)],
            range(1, 49)
        );
    }

    private function annualRows(): array
    {
        return [
            $this->annualRow('Perangkat Daerah 01', 2, 1500.0, 1, 750.0),
            $this->annualRow('Perangkat Daerah 10', 3, 2000.0, 2, 1000.0),
            $this->annualRow('Perangkat Daerah 20', 1, 500.0, 1, 250.0),
            $this->annualRow('Perangkat Daerah 49', 5, 4000.0, 2, 2000.0),
        ];
    }

    private function annualRow(
        string $organizationName,
        int $planningPackages,
        float $planningBudget,
        int $recordedPackages,
        float $recordedBudget
    ): array {
        return [
            'nama_opd' => $organizationName,
            'perencanaan_paket' => $planningPackages,
            'perencanaan_pagu' => $planningBudget,
            'tercatat_paket' => $recordedPackages,
            'tercatat_pagu' => $recordedBudget,
            'belum_tercatat_paket' => $planningPackages - $recordedPackages,
            'belum_tercatat_pagu' => $planningBudget - $recordedBudget,
            'persentase_tercatat' => round(($recordedBudget / $planningBudget) * 100, 2),
        ];
    }
}
