<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class KatalogBulananReportService
{
    private const KLPD_CODE = 'D264';
    private const KLPD_NAME = 'Provinsi Lampung';
    private const ALLOWED_STATUSES = [
        'ON_PROCESS',
        'COMPLETED',
        'PAYMENT_OUTSIDE_SYSTEM',
        'ON_ADDENDUM',
    ];
    private const MONTHS = [
        'Januari' => 1,
        'Februari' => 2,
        'Maret' => 3,
        'April' => 4,
        'Mei' => 5,
        'Juni' => 6,
        'Juli' => 7,
        'Agustus' => 8,
        'September' => 9,
        'Oktober' => 10,
        'November' => 11,
        'Desember' => 12,
    ];

    public function report(int $tahun, string $bulan): array
    {
        $selectedMonthStart = CarbonImmutable::create($tahun, self::MONTHS[$bulan], 1)->startOfDay();
        $nextMonthStart = $selectedMonthStart->addMonth();
        $yearStart = CarbonImmutable::create($tahun, 1, 1)->startOfDay();
        $planningByOrganization = $this->planningByOrganization($tahun);
        $transactionsByOrganization = $this->transactionsByOrganization(
            $tahun,
            $yearStart,
            $selectedMonthStart,
            $nextMonthStart
        );

        return [
            'sheetSlug' => 'katalog',
            'sheetTitle' => 'Katalog',
            'tahun' => $tahun,
            'bulan' => $bulan,
            'priorPeriodLabel' => $this->priorPeriodLabel($selectedMonthStart),
            'selectedPeriodLabel' => "{$bulan} {$tahun}",
            'rows' => $this->mergeRows($planningByOrganization, $transactionsByOrganization),
        ];
    }

    private function planningByOrganization(int $tahun): array
    {
        return DB::table('penyedias')
            ->select('nama_satker')
            ->selectRaw('COALESCE(SUM(pagu), 0) AS planning_budget')
            ->where('kd_klpd', self::KLPD_CODE)
            ->where('nama_klpd', self::KLPD_NAME)
            ->where('tahun_anggaran', $tahun)
            ->whereNotNull('nama_satker')
            ->where(function ($query): void {
                $query->where('metode_pengadaan', 'like', '%Purchasing%')
                    ->orWhere('metode_pengadaan', 'like', '%Katalog%');
            })
            ->groupBy('nama_satker')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->nama_satker => (float) $row->planning_budget])
            ->all();
    }

    private function transactionsByOrganization(
        int $tahun,
        CarbonImmutable $yearStart,
        CarbonImmutable $selectedMonthStart,
        CarbonImmutable $nextMonthStart
    ): array {
        $yearStartDate = $yearStart->toDateString();
        $selectedMonthDate = $selectedMonthStart->toDateString();
        $nextMonthDate = $nextMonthStart->toDateString();

        return DB::table('ekatalog_v6_pakets')
            ->select('nama_satker')
            ->selectRaw(
                'SUM(CASE WHEN tgl_order >= ? AND tgl_order < ? THEN COALESCE(total_harga, 0) ELSE 0 END) AS prior_amount',
                [$yearStartDate, $selectedMonthDate]
            )
            ->selectRaw(
                'SUM(CASE WHEN tgl_order >= ? AND tgl_order < ? THEN COALESCE(total_harga, 0) ELSE 0 END) AS selected_amount',
                [$selectedMonthDate, $nextMonthDate]
            )
            ->where('kd_klpd', self::KLPD_CODE)
            ->where('tahun_anggaran', $tahun)
            ->whereIn('status_pkt', self::ALLOWED_STATUSES)
            ->whereNotNull('nama_satker')
            ->whereNotNull('tgl_order')
            ->where('tgl_order', '>=', $yearStartDate)
            ->where('tgl_order', '<', $nextMonthDate)
            ->groupBy('nama_satker')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->nama_satker => [
                'priorAmount' => (float) $row->prior_amount,
                'selectedAmount' => (float) $row->selected_amount,
            ]])
            ->all();
    }

    private function mergeRows(array $planningByOrganization, array $transactionsByOrganization): array
    {
        $organizationNames = array_unique(array_merge(
            array_keys($planningByOrganization),
            array_keys($transactionsByOrganization)
        ));
        usort($organizationNames, 'strnatcasecmp');

        return array_map(function (string $organizationName, int $index) use (
            $planningByOrganization,
            $transactionsByOrganization
        ): array {
            $planningBudget = $planningByOrganization[$organizationName] ?? 0.0;
            $priorAmount = $transactionsByOrganization[$organizationName]['priorAmount'] ?? 0.0;
            $selectedAmount = $transactionsByOrganization[$organizationName]['selectedAmount'] ?? 0.0;
            $cumulativeAmount = $priorAmount + $selectedAmount;

            return [
                'number' => $index + 1,
                'organizationName' => $organizationName,
                'planningBudget' => $planningBudget,
                'priorAmount' => $priorAmount,
                'selectedAmount' => $selectedAmount,
                'cumulativeAmount' => $cumulativeAmount,
                'percentage' => $planningBudget > 0
                    ? round(($cumulativeAmount / $planningBudget) * 100, 2)
                    : 0.0,
            ];
        }, $organizationNames, array_keys($organizationNames));
    }

    private function priorPeriodLabel(CarbonImmutable $selectedMonthStart): string
    {
        $priorMonth = $selectedMonthStart->subMonth();
        $monthName = array_search($priorMonth->month, self::MONTHS, true);

        return "s.d {$monthName} {$priorMonth->year}";
    }
}
