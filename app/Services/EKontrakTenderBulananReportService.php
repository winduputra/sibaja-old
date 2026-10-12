<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class EKontrakTenderBulananReportService
{
    private const KLPD_CODE = 'D264';
    private const KLPD_NAME = 'Provinsi Lampung';
    private const MILESTONES = ['sppbj', 'kontrak', 'spmkSpp', 'bap', 'bast'];
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
        $katalogRows = $this->katalogService->report($tahun, $bulan)['rows'];
        $organizationCodes = $this->organizationCodesByName($tahun);
        $tenderCodes = $this->tenderOrganizationCodesByPlanningCode($tahun);
        $planning = $this->planningPackagesByCode($tahun);
        $realization = $this->realizationByCode($tahun, $yearStart, $selectedMonthStart, $nextMonthStart);
        $rows = $this->mergeRows($katalogRows, $organizationCodes, $tenderCodes, $planning, $realization);

        return [
            'sheetSlug' => 'e-kontrak',
            'sheetTitle' => 'E-Kontrak Tender',
            'tahun' => $tahun,
            'bulan' => $bulan,
            'priorPeriodLabel' => $this->priorPeriodLabel($selectedMonthStart),
            'selectedPeriodLabel' => "{$bulan} {$tahun}",
            'rows' => $rows,
            'summary' => $this->summarize($rows),
        ];
    }

    private function organizationCodesByName(int $tahun): array
    {
        return DB::table('penyedias')
            ->select('nama_satker')
            ->selectRaw('MIN(kd_satker) AS organization_code')
            ->where('kd_klpd', self::KLPD_CODE)
            ->where('tahun_anggaran', $tahun)
            ->whereNotNull('nama_satker')
            ->whereNotNull('kd_satker')
            ->groupBy('nama_satker')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->nama_satker => (string) $row->organization_code])
            ->all();
    }

    private function planningPackagesByCode(int $tahun): array
    {
        return DB::table('penyedias')
            ->select('kd_satker')
            ->selectRaw('COUNT(*) AS package_count')
            ->where('kd_klpd', self::KLPD_CODE)
            ->where('nama_klpd', self::KLPD_NAME)
            ->where('tahun_anggaran', $tahun)
            ->whereNotNull('kd_satker')
            ->where($this->tenderOrSelectionMethod('metode_pengadaan'))
            ->groupBy('kd_satker')
            ->get()
            ->mapWithKeys(fn ($row): array => [(string) $row->kd_satker => (int) $row->package_count])
            ->all();
    }

    private function tenderOrganizationCodesByPlanningCode(int $tahun): array
    {
        return DB::table('penyedias as planning')
            ->join('tender_pengumuman_data as tender', 'tender.kd_rup', '=', 'planning.kd_rup')
            ->selectRaw('planning.kd_satker AS planning_code, MIN(tender.kd_satker) AS tender_code')
            ->where('planning.kd_klpd', self::KLPD_CODE)
            ->where('planning.nama_klpd', self::KLPD_NAME)
            ->where('planning.tahun_anggaran', $tahun)
            ->where('tender.kd_klpd', self::KLPD_CODE)
            ->where('tender.tahun', $tahun)
            ->whereNotNull('planning.kd_rup')
            ->whereNotNull('planning.kd_satker')
            ->whereNotNull('tender.kd_satker')
            ->where($this->tenderOrSelectionMethod('planning.metode_pengadaan'))
            ->where($this->tenderOrSelectionMethod('tender.mtd_pemilihan'))
            ->groupBy('planning.kd_satker')
            ->havingRaw('COUNT(DISTINCT tender.kd_satker) = 1')
            ->get()
            ->mapWithKeys(fn ($row): array => [(string) $row->planning_code => (string) $row->tender_code])
            ->all();
    }

    private function realizationByCode(
        int $tahun,
        CarbonImmutable $yearStart,
        CarbonImmutable $selectedMonthStart,
        CarbonImmutable $nextMonthStart
    ): array {
        $realizedTenders = $this->realizedTenderQuery($tahun, $yearStart, $nextMonthStart);
        $contracts = DB::table('kontrak_data')
            ->select('kd_satker', 'kd_tender')
            ->selectRaw('MAX(tgl_sppbj) AS tgl_sppbj')
            ->selectRaw('MAX(tgl_kontrak) AS tgl_kontrak')
            ->selectRaw('MAX(tgl_spmk_spp) AS tgl_spmk_spp')
            ->selectRaw('MAX(tgl_bap) AS tgl_bap')
            ->selectRaw('MAX(tgl_bast) AS tgl_bast')
            ->where('kd_klpd', self::KLPD_CODE)
            ->where('tahun', $tahun)
            ->whereNotNull('kd_satker')
            ->whereNotNull('kd_tender')
            ->groupBy('kd_satker', 'kd_tender');

        $query = DB::query()
            ->fromSub($realizedTenders, 'realized')
            ->leftJoinSub($contracts, 'contract', function ($join): void {
                $join->on('contract.kd_tender', '=', 'realized.kd_tender')
                    ->on('contract.kd_satker', '=', 'realized.organization_code');
            })
            ->select('realized.organization_code')
            ->selectRaw('COUNT(DISTINCT realized.kd_tender) AS realized_packages');

        $this->addMilestoneCounts($query, 'prior', $yearStart, $selectedMonthStart);
        $this->addMilestoneCounts($query, 'selected', $selectedMonthStart, $nextMonthStart);
        $this->addMilestoneCounts($query, 'cumulative', $yearStart, $nextMonthStart);

        return $query->groupBy('realized.organization_code')
            ->get()
            ->mapWithKeys(fn ($row): array => [(string) $row->organization_code => $this->realizationRow($row)])
            ->all();
    }

    private function realizedTenderQuery(int $tahun, CarbonImmutable $yearStart, CarbonImmutable $nextMonthStart): Builder
    {
        return DB::table('tender_selesai_nilai_data as nilai')
            ->join('tender_pengumuman_data as pengumuman', 'pengumuman.kd_tender', '=', 'nilai.kd_tender')
            ->selectRaw('pengumuman.kd_satker AS organization_code, nilai.kd_tender')
            ->where('nilai.kd_klpd', self::KLPD_CODE)
            ->where('nilai.tahun', $tahun)
            ->whereNotNull('pengumuman.kd_satker')
            ->whereNotNull('nilai.kd_tender')
            ->whereNotNull('nilai.tgl_penetapan_pemenang')
            ->where('nilai.tgl_penetapan_pemenang', '>=', $yearStart->toDateString())
            ->where('nilai.tgl_penetapan_pemenang', '<', $nextMonthStart->toDateString())
            ->where($this->tenderOrSelectionMethod('pengumuman.mtd_pemilihan'))
            ->groupBy('pengumuman.kd_satker', 'nilai.kd_tender');
    }

    private function tenderOrSelectionMethod(string $column): callable
    {
        return function (Builder $query) use ($column): void {
            $query->where($column, 'like', '%Tender%')
                ->orWhere($column, 'like', '%Seleksi%');
        };
    }

    private function addMilestoneCounts(
        Builder $query,
        string $period,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): void {
        $columns = [
            'sppbj' => 'tgl_sppbj',
            'kontrak' => 'tgl_kontrak',
            'spmkSpp' => 'tgl_spmk_spp',
            'bap' => 'tgl_bap',
            'bast' => 'tgl_bast',
        ];

        foreach ($columns as $key => $column) {
            $query->selectRaw(
                "COUNT(DISTINCT CASE WHEN contract.{$column} >= ? AND contract.{$column} < ? THEN realized.kd_tender END) AS {$period}_{$key}",
                [$start->toDateString(), $end->toDateString()]
            );
        }
    }

    private function realizationRow(object $row): array
    {
        $result = ['realizedPackages' => (int) $row->realized_packages];
        foreach (['prior', 'selected', 'cumulative'] as $period) {
            foreach (self::MILESTONES as $milestone) {
                $result[$period][$milestone] = (int) $row->{"{$period}_{$milestone}"};
            }
        }

        return $result;
    }

    private function mergeRows(
        array $katalogRows,
        array $organizationCodes,
        array $tenderCodes,
        array $planning,
        array $realization
    ): array {
        $rows = [];

        foreach ($katalogRows as $katalogRow) {
            $organizationName = $katalogRow['organizationName'];
            $planningCode = $organizationCodes[$organizationName] ?? null;
            $planningPackages = $planningCode === null ? 0 : ($planning[$planningCode] ?? 0);

            if ($planningPackages === 0) {
                continue;
            }

            $tenderCode = $planningCode === null ? null : ($tenderCodes[$planningCode] ?? null);
            $metrics = $tenderCode === null ? [] : ($realization[$tenderCode] ?? []);

            $rows[] = [
                'number' => count($rows) + 1,
                'organizationName' => $organizationName,
                'planningPackages' => $planningPackages,
                'realizedPackages' => $metrics['realizedPackages'] ?? 0,
                'prior' => $metrics['prior'] ?? $this->emptyMilestones(),
                'selected' => $metrics['selected'] ?? $this->emptyMilestones(),
                'cumulative' => $metrics['cumulative'] ?? $this->emptyMilestones(),
            ];
        }

        return $rows;
    }

    private function summarize(array $rows): array
    {
        $summary = [
            'planningPackages' => 0,
            'realizedPackages' => 0,
            'prior' => $this->emptyMilestones(),
            'selected' => $this->emptyMilestones(),
            'cumulative' => $this->emptyMilestones(),
        ];

        foreach ($rows as $row) {
            $summary['planningPackages'] += $row['planningPackages'];
            $summary['realizedPackages'] += $row['realizedPackages'];
            foreach (['prior', 'selected', 'cumulative'] as $period) {
                foreach (self::MILESTONES as $milestone) {
                    $summary[$period][$milestone] += $row[$period][$milestone];
                }
            }
        }

        return $summary;
    }

    private function emptyMilestones(): array
    {
        return array_fill_keys(self::MILESTONES, 0);
    }

    private function priorPeriodLabel(CarbonImmutable $selectedMonthStart): string
    {
        $priorMonth = $selectedMonthStart->subMonth();
        $monthName = array_search($priorMonth->month, self::MONTHS, true);

        return "s.d {$monthName} {$priorMonth->year}";
    }
}
