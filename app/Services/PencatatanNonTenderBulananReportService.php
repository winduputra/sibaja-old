<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PencatatanNonTenderBulananReportService
{
    private const KLPD_CODE = 'D264';
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

    public function __construct(private KatalogBulananReportService $katalogService)
    {
    }

    public function report(int $tahun, string $bulan): array
    {
        $selectedMonthStart = CarbonImmutable::create($tahun, self::MONTHS[$bulan], 1)->startOfDay();
        $nextMonthStart = $selectedMonthStart->addMonth();
        $yearStart = CarbonImmutable::create($tahun, 1, 1)->startOfDay();
        $katalogReport = $this->katalogService->report($tahun, $bulan);
        $planning = $this->planningByOrganization($tahun);
        $transactions = $this->transactionsByOrganization($tahun, $yearStart, $selectedMonthStart, $nextMonthStart);

        return [
            'sheetSlug' => 'pencatatan-non-tender',
            'sheetTitle' => 'Pencatatan Non Tender',
            'tahun' => $tahun,
            'bulan' => $bulan,
            'priorPeriodLabel' => $this->priorPeriodLabel($selectedMonthStart),
            'selectedPeriodLabel' => "{$bulan} {$tahun}",
            'rows' => $this->mergeRows($katalogReport['rows'], $planning, $transactions),
        ];
    }

    private function planningByOrganization(int $tahun): array
    {
        return DB::table('non_tender_pencatatan')
            ->select('nama_satker')
            ->selectRaw('COALESCE(SUM(pagu), 0) AS planning_budget')
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', self::KLPD_CODE)
            ->whereNotNull('nama_satker')
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
        $amountExpression = 'COALESCE(nilai_realisasi, pagu, 0)';

        return DB::table('non_tender_realisasi')
            ->select('nama_satker')
            ->selectRaw(
                "SUM(CASE WHEN tgl_realisasi >= ? AND tgl_realisasi < ? THEN {$amountExpression} ELSE 0 END) AS prior_amount",
                [$yearStartDate, $selectedMonthDate]
            )
            ->selectRaw(
                "SUM(CASE WHEN tgl_realisasi >= ? AND tgl_realisasi < ? THEN {$amountExpression} ELSE 0 END) AS selected_amount",
                [$selectedMonthDate, $nextMonthDate]
            )
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', self::KLPD_CODE)
            ->whereNotNull('nama_satker')
            ->whereNotNull('tgl_realisasi')
            ->where('tgl_realisasi', '>=', $yearStartDate)
            ->where('tgl_realisasi', '<', $nextMonthDate)
            ->groupBy('nama_satker')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->nama_satker => [
                'priorAmount' => (float) $row->prior_amount,
                'selectedAmount' => (float) $row->selected_amount,
            ]])
            ->all();
    }

    private function mergeRows(array $katalogRows, array $planning, array $transactions): array
    {
        return array_map(function (array $katalogRow, int $index) use ($planning, $transactions): array {
            $organizationName = $katalogRow['organizationName'];
            $planningBudget = $planning[$organizationName] ?? 0.0;
            $priorAmount = $transactions[$organizationName]['priorAmount'] ?? 0.0;
            $selectedAmount = $transactions[$organizationName]['selectedAmount'] ?? 0.0;
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
        }, array_values($katalogRows), array_keys(array_values($katalogRows)));
    }

    private function priorPeriodLabel(CarbonImmutable $selectedMonthStart): string
    {
        $priorMonth = $selectedMonthStart->subMonth();
        $monthName = array_search($priorMonth->month, self::MONTHS, true);

        return "s.d {$monthName} {$priorMonth->year}";
    }
}
