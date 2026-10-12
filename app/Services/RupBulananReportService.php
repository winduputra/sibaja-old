<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RupBulananReportService
{
    private const KLPD_CODE = 'D264';
    private const KLPD_NAME = 'Provinsi Lampung';
    private const EXCLUDED_SATKER_CODE = '350504';

    public function report(int $tahun, string $bulan): array
    {
        $provider = $this->planningByOrganization('penyedias', $tahun);
        $selfManaged = $this->planningByOrganization('swakelolas', $tahun);
        $rows = DB::table('satkers')
            ->select('kd_satker', 'nama_satker', 'belanja_pengadaan')
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', self::KLPD_CODE)
            ->where('kd_satker', '!=', self::EXCLUDED_SATKER_CODE)
            ->whereNotNull('nama_satker')
            ->orderBy('nama_satker')
            ->get()
            ->values()
            ->map(function ($satker, int $index) use ($provider, $selfManaged): array {
                $organizationCode = (string) $satker->kd_satker;
                $providerMetrics = $provider[$organizationCode] ?? $this->emptyPlanning();
                $selfManagedMetrics = $selfManaged[$organizationCode] ?? $this->emptyPlanning();

                return $this->reportRow(
                    $index + 1,
                    (string) $satker->nama_satker,
                    (float) ($satker->belanja_pengadaan ?? 0),
                    $providerMetrics,
                    $selfManagedMetrics
                );
            })
            ->all();

        return [
            'sheetSlug' => 'rup',
            'sheetTitle' => 'RUP',
            'tahun' => $tahun,
            'bulan' => $bulan,
            'selectedPeriodLabel' => "{$bulan} {$tahun}",
            'rows' => $rows,
            'summary' => $this->summarize($rows),
        ];
    }

    private function planningByOrganization(string $table, int $tahun): array
    {
        return DB::table($table)
            ->select('kd_satker')
            ->selectRaw('COUNT(*) AS package_count, COALESCE(SUM(pagu), 0) AS budget')
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', self::KLPD_CODE)
            ->where('nama_klpd', self::KLPD_NAME)
            ->where('status_aktif_rup', true)
            ->whereNotNull('kd_satker')
            ->groupBy('kd_satker')
            ->get()
            ->mapWithKeys(function ($row): array {
                return [
                    (string) $row->kd_satker => [
                        'packageCount' => (int) $row->package_count,
                        'budget' => (float) $row->budget,
                    ],
                ];
            })
            ->all();
    }

    private function reportRow(int $number, string $organizationName, float $procurementBudget, array $provider, array $selfManaged): array
    {
        $totalPackageCount = $provider['packageCount'] + $selfManaged['packageCount'];
        $totalBudget = $provider['budget'] + $selfManaged['budget'];

        return [
            'number' => $number,
            'organizationName' => $organizationName,
            'procurementBudget' => $procurementBudget,
            'providerPackageCount' => $provider['packageCount'],
            'providerBudget' => $provider['budget'],
            'providerPercentage' => $this->percentage($provider['budget'], $procurementBudget),
            'selfManagedPackageCount' => $selfManaged['packageCount'],
            'selfManagedBudget' => $selfManaged['budget'],
            'selfManagedPercentage' => $this->percentage($selfManaged['budget'], $procurementBudget),
            'totalPackageCount' => $totalPackageCount,
            'totalBudget' => $totalBudget,
            'totalPercentage' => $this->percentage($totalBudget, $procurementBudget),
        ];
    }

    private function summarize(array $rows): array
    {
        $summary = [
            'procurementBudget' => 0.0,
            'providerPackageCount' => 0,
            'providerBudget' => 0.0,
            'selfManagedPackageCount' => 0,
            'selfManagedBudget' => 0.0,
            'totalPackageCount' => 0,
            'totalBudget' => 0.0,
        ];

        foreach ($rows as $row) {
            foreach (array_keys($summary) as $key) {
                $summary[$key] += $row[$key];
            }
        }

        $summary['providerPercentage'] = $this->percentage($summary['providerBudget'], $summary['procurementBudget']);
        $summary['selfManagedPercentage'] = $this->percentage($summary['selfManagedBudget'], $summary['procurementBudget']);
        $summary['totalPercentage'] = $this->percentage($summary['totalBudget'], $summary['procurementBudget']);

        return $summary;
    }

    private function percentage(float $budget, float $procurementBudget): float
    {
        return $procurementBudget > 0 ? round(($budget / $procurementBudget) * 100, 2) : 0.0;
    }

    private function emptyPlanning(): array
    {
        return ['packageCount' => 0, 'budget' => 0.0];
    }
}
