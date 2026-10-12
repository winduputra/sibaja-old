<?php

namespace Tests\Unit\Services;

use App\Services\KatalogBulananReportService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KatalogBulananReportServiceTest extends TestCase
{
    private bool $temporaryTablesCreated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Katalog bulanan aggregation regression requires MySQL or MariaDB.');
        }

        DB::statement('DROP TEMPORARY TABLE IF EXISTS penyedias');
        DB::statement('DROP TEMPORARY TABLE IF EXISTS ekatalog_v6_pakets');
        DB::statement('CREATE TEMPORARY TABLE penyedias (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tahun_anggaran INT NOT NULL,
            kd_klpd VARCHAR(20) NOT NULL,
            nama_klpd VARCHAR(255) NULL,
            nama_satker VARCHAR(255) NULL,
            metode_pengadaan VARCHAR(255) NULL,
            pagu DECIMAL(20, 2) NULL
        )');
        DB::statement('CREATE TEMPORARY TABLE ekatalog_v6_pakets (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tahun_anggaran INT NOT NULL,
            kd_klpd VARCHAR(20) NOT NULL,
            nama_satker VARCHAR(255) NULL,
            total_harga DECIMAL(20, 2) NULL,
            tgl_order DATE NULL,
            status_pkt VARCHAR(100) NULL
        )');
        $this->temporaryTablesCreated = true;

        $this->seedPlanningRows();
        $this->seedTransactionRows();
    }

    protected function tearDown(): void
    {
        if ($this->temporaryTablesCreated) {
            DB::statement('DROP TEMPORARY TABLE IF EXISTS penyedias');
            DB::statement('DROP TEMPORARY TABLE IF EXISTS ekatalog_v6_pakets');
        }

        parent::tearDown();
    }

    public function test_august_report_uses_dashboard_sources_and_half_open_periods(): void
    {
        $report = app(KatalogBulananReportService::class)->report(2026, 'Agustus');

        $this->assertSame('katalog', $report['sheetSlug']);
        $this->assertSame('Katalog', $report['sheetTitle']);
        $this->assertSame('s.d Juli 2026', $report['priorPeriodLabel']);
        $this->assertSame('Agustus 2026', $report['selectedPeriodLabel']);
        $this->assertSame(['Badan A', 'Biro B', 'Dinas C'], array_column($report['rows'], 'organizationName'));
        $this->assertSame([1, 2, 3], array_column($report['rows'], 'number'));

        $this->assertSame([
            'number' => 1,
            'organizationName' => 'Badan A',
            'planningBudget' => 2000.0,
            'priorAmount' => 0.0,
            'selectedAmount' => 0.0,
            'cumulativeAmount' => 0.0,
            'percentage' => 0.0,
        ], $report['rows'][0]);
        $this->assertSame(1500.0, $report['rows'][1]['planningBudget']);
        $this->assertSame(300.0, $report['rows'][1]['priorAmount']);
        $this->assertSame(700.0, $report['rows'][1]['selectedAmount']);
        $this->assertSame(1000.0, $report['rows'][1]['cumulativeAmount']);
        $this->assertSame(66.67, $report['rows'][1]['percentage']);
        $this->assertSame(0.0, $report['rows'][2]['planningBudget']);
        $this->assertSame(250.0, $report['rows'][2]['selectedAmount']);
        $this->assertSame(0.0, $report['rows'][2]['percentage']);
    }

    public function test_september_report_moves_august_to_prior_and_excludes_october_boundary(): void
    {
        $report = app(KatalogBulananReportService::class)->report(2026, 'September');
        $biro = $report['rows'][1];

        $this->assertSame('s.d Agustus 2026', $report['priorPeriodLabel']);
        $this->assertSame('September 2026', $report['selectedPeriodLabel']);
        $this->assertSame(1000.0, $biro['priorAmount']);
        $this->assertSame(1100.0, $biro['selectedAmount']);
        $this->assertSame(2100.0, $biro['cumulativeAmount']);
        $this->assertSame(140.0, $biro['percentage']);
    }

    private function seedPlanningRows(): void
    {
        DB::table('penyedias')->insert([
            $this->planningRow('Biro B', 'E-Purchasing', 1000),
            $this->planningRow('Biro B', 'Katalog Elektronik', 500),
            $this->planningRow('Badan A', 'Purchasing', 2000),
            $this->planningRow('Biro B', 'Tender', 9000),
            $this->planningRow('Tahun Lain', 'E-Purchasing', 9000, 2025),
            $this->planningRow('KLPD Lain', 'E-Purchasing', 9000, 2026, 'X001'),
            $this->planningRow('Nama KLPD Lain', 'E-Purchasing', 9000, 2026, 'D264', 'Lampung'),
            $this->planningRow(null, 'E-Purchasing', 9000),
        ]);
    }

    private function seedTransactionRows(): void
    {
        DB::table('ekatalog_v6_pakets')->insert([
            $this->transactionRow('Biro B', 100, '2026-01-15', 'ON_PROCESS'),
            $this->transactionRow('Biro B', 200, '2026-07-31', 'COMPLETED'),
            $this->transactionRow('Biro B', 300, '2026-08-01', 'PAYMENT_OUTSIDE_SYSTEM'),
            $this->transactionRow('Biro B', 400, '2026-08-31', 'ON_ADDENDUM'),
            $this->transactionRow('Biro B', 500, '2026-09-01', 'COMPLETED'),
            $this->transactionRow('Biro B', 600, '2026-09-30', 'ON_PROCESS'),
            $this->transactionRow('Biro B', 700, '2026-10-01', 'COMPLETED'),
            $this->transactionRow('Dinas C', 250, '2026-08-15', 'COMPLETED'),
            $this->transactionRow('Biro B', 9000, '2026-08-20', 'CANCELLED'),
            $this->transactionRow('Biro B', 9000, '2025-08-20', 'COMPLETED', 2025),
            $this->transactionRow('Biro B', 9000, '2026-08-20', 'COMPLETED', 2026, 'X001'),
            $this->transactionRow(null, 9000, '2026-08-20', 'COMPLETED'),
        ]);
    }

    private function planningRow(
        ?string $organizationName,
        string $method,
        float $budget,
        int $year = 2026,
        string $klpdCode = 'D264',
        string $klpdName = 'Provinsi Lampung'
    ): array {
        return [
            'tahun_anggaran' => $year,
            'kd_klpd' => $klpdCode,
            'nama_klpd' => $klpdName,
            'nama_satker' => $organizationName,
            'metode_pengadaan' => $method,
            'pagu' => $budget,
        ];
    }

    private function transactionRow(
        ?string $organizationName,
        float $amount,
        string $date,
        string $status,
        int $year = 2026,
        string $klpdCode = 'D264'
    ): array {
        return [
            'tahun_anggaran' => $year,
            'kd_klpd' => $klpdCode,
            'nama_satker' => $organizationName,
            'total_harga' => $amount,
            'tgl_order' => $date,
            'status_pkt' => $status,
        ];
    }
}
