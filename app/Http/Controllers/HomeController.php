<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NonTenderPengumuman;
use App\Models\TenderPengumumanData;
use App\Models\TokoDaring;
use App\Models\Tender;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\EkatalogV5Paket;
use App\Models\EkatalogV6Paket;
use App\Models\StrukturAnggaran;
use App\Models\Swakelola;
use App\Models\Penyedia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $tahun = $request->input('tahun', Carbon::now()->year);
        $kategoriChart2 = $request->input('kategori_chart2', 'non_tender'); // default: non_tender
        $dashboardFilter = $this->resolveDashboardWeekFilter($request, $tahun);
        $monthOptions = $this->dashboardMonthOptions();
        $selectedMonth = $dashboardFilter['month'];
        $selectedWeek = $dashboardFilter['week'];
        $availableWeeks = $dashboardFilter['weeks'];
        $activeWeekRange = $dashboardFilter['range'];
        $customDateRange = $this->resolveDashboardCustomDateRange($request, $tahun);
        $activeDashboardRange = $customDateRange ?: $activeWeekRange;
        $selectedStartDate = $request->input('tanggal_mulai');
        $selectedEndDate = $request->input('tanggal_selesai');
        $isCustomDateRangeActive = (bool) $customDateRange;

        
        // ✅ Box summary count
        $nonTenderCount = getNonTenderCount();
        $tenderCount    = getTenderCount();
        $ekatalogCount  = getEkatalogCount();
        $belaCount      = getBelaCount();
        
        // ✅ CHART 1: Perbandingan Total Data Tender vs Non Tender per Tahun
        $totalNonTender = NonTenderPengumuman::where('tahun_anggaran', $tahun)
            ->whereIn('status_nontender', ['Selesai', 'Berlangsung'])
            ->count();        

        $totalTender = TenderPengumumanData::where('tahun', $tahun)
            ->whereIn('status_tender', ['Selesai', 'Berlangsung'])
            ->count();  ;

        $chart1Data = [
            'Non Tender' => $totalNonTender,
            'Tender' => $totalTender,
        ];
        
        // ✅ CHART 2: Distribusi Jenis Pengadaan Berdasarkan Kategori (Tender / Non Tender)
        if ($kategoriChart2 == 'tender') {
            $chart2Data = TenderPengumumanData::where('tahun', $tahun)

                ->whereIn('status_tender', ['Selesai', 'Berlangsung'])
                ->select('jenis_pengadaan', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('jenis_pengadaan')
                ->pluck('jumlah', 'jenis_pengadaan');
        } else {
            $chart2Data = NonTenderPengumuman::where('tahun_anggaran', $tahun)
                ->whereIn('status_nontender', ['Selesai', 'Berlangsung'])
                ->select('jenis_pengadaan', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('jenis_pengadaan')
                ->pluck('jumlah', 'jenis_pengadaan');

        }        

        // ✅ Menghitung Total untuk Non Tender
        $totalNonTenderData = NonTenderPengumuman::where('tahun_anggaran', $tahun)
            ->whereIn('status_nontender', ['Selesai', 'Berlangsung'])
            ->select(
                DB::raw('COUNT(*) as package_count'),
                DB::raw('SUM(pagu) as total_pagu'),
                DB::raw('SUM(hps) as total_hps'),
                DB::raw('SUM(pagu - hps) as total_efficiency') // Calculate efficiency from pagu - hps
            )
            ->first();

        $totalNonTender = [
            'package_count' => $totalNonTenderData->package_count,
            'pagu' => $totalNonTenderData->total_pagu,
            'hps' => $totalNonTenderData->total_hps,
            'efficiency' => $totalNonTenderData->total_efficiency,
        ];

        // ✅ Menghitung Total untuk Tender
        $totalTenderData = TenderPengumumanData::where('tahun', $tahun)
            ->whereIn('status_tender', ['Selesai', 'Berlangsung'])
            ->select(
                DB::raw('COUNT(*) as package_count'),
                DB::raw('SUM(pagu) as total_pagu'),
                DB::raw('SUM(hps) as total_hps'),
                DB::raw('SUM(pagu - hps) as total_efficiency')
            )
            ->first();

        $totalTender = [
            'package_count' => $totalTenderData->package_count,
            'pagu' => $totalTenderData->total_pagu,
            'hps' => $totalTenderData->total_hps,
            'efficiency' => $totalTenderData->total_efficiency,
        ];


        // ✅ Menghitung Total untuk e-Katalog V5
        $totalEkatalogV5Data = EkatalogV5Paket::where('tahun_anggaran', $tahun)
            ->select(
                DB::raw('COUNT(DISTINCT kd_paket) as package_count'),  // DISTINCT untuk menghitung paket yang unik
                DB::raw('SUM(total_harga) as total_transaksi') // Total transaksi untuk V5
            )
            ->first();

        $totalEkatalogV5 = [
            'package_count' => $totalEkatalogV5Data->package_count,
            'total_transaksi' => $totalEkatalogV5Data->total_transaksi,
        ];

        // ✅ Menghitung Total untuk e-Katalog V6
        $totalEkatalogV6Data = EkatalogV6Paket::where('tahun_anggaran', $tahun)
            ->select(
                DB::raw('COUNT(DISTINCT kd_paket) as package_count'),  // DISTINCT untuk menghitung paket yang unik
                DB::raw('SUM(total_harga) as total_transaksi') // Total transaksi untuk V6
            )
            ->first();

        $totalEkatalogV6 = [
            'package_count' => $totalEkatalogV6Data->package_count,
            'total_transaksi' => $totalEkatalogV6Data->total_transaksi,
        ];

        // ✅ Menghitung Total untuk RUP (StrukturAnggaran, Swakelola, Penyedia)
        $strukturAnggaran = StrukturAnggaran::where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->where('nama_klpd', 'Provinsi Lampung')
            ->get();
        
        $swakelola = Swakelola::where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->where('nama_klpd', 'Provinsi Lampung')
            ->get();

        $penyedia = Penyedia::where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->where('nama_klpd', 'Provinsi Lampung')
            ->get();

        // Menghitung total untuk Penyedia, Swakelola, Penyedia dalam Swakelola
        $totalRup = [
            'paket_penyedia' => $penyedia->count(),
            'pagu_penyedia' => $penyedia->sum('pagu'),
            'paket_swakelola' => $swakelola->count(),
            'pagu_swakelola' => $swakelola->sum('pagu'),
            'paket_dalam' => 0, // Placeholder, jika ada data lain terkait Penyedia dalam Swakelola
            'pagu_dalam' => 0,  // Placeholder
            'paket_total' => $penyedia->count() + $swakelola->count(),
            'pagu_total' => $penyedia->sum('pagu') + $swakelola->sum('pagu'),
        ];

        // ✅ Menghitung Total untuk Toko Daring (similar to TokoDaringReportController logic)
        $dataTokoDaring = TokoDaring::where('tahun', $tahun)
            ->get();

        // Gabungkan nama satker dari StrukturAnggaran
        $satkerFromStruktur = StrukturAnggaran::where('tahun_anggaran', $tahun)
            ->pluck('nama_satker', 'kd_satker');
        
        // Gabungkan nama satker dari TokoDaring
        $satkerFromData = $dataTokoDaring->pluck('nama_satker', 'kd_satker');
        
        // Gabungkan dan filter nama satker unik berdasarkan kd_satker
        $allSatker = $satkerFromStruktur->merge($satkerFromData)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Rekap data Toko Daring
        $rekapTokoDaring = collect();
        foreach ($dataTokoDaring->groupBy('kd_satker') as $kdSatker => $transactions) {
            $namaSatker = $satkerFromStruktur->get($kdSatker, $transactions->first()->nama_satker);

            $rekapTokoDaring[$kdSatker] = [
                'nama_satker' => $namaSatker,
                'total_transaksi' => $transactions->count(),
                'nilai_transaksi' => $transactions->sum('valuasi'),
            ];
        }

        // Menghitung total transaksi dan nilai transaksi
        $totalTokoDaringTransaksi = $rekapTokoDaring->sum('total_transaksi');
        $totalTokoDaringNilai = $rekapTokoDaring->sum('nilai_transaksi');

        // ✅ Tahun tersedia
        $availableYears = collect()
            ->merge(DB::table('struktur_anggarans')->distinct()->pluck('tahun_anggaran'))
            ->merge(DB::table('penyedias')->distinct()->pluck('tahun_anggaran'))
            ->merge(DB::table('swakelolas')->distinct()->pluck('tahun_anggaran'))
            ->merge(DB::table('tender_selesai_data')->distinct()->pluck('tahun'))
            ->merge(DB::table('ekatalog_v5_pakets')->distinct()->pluck('tahun_anggaran'))
            ->merge(DB::table('ekatalog_v6_pakets')->distinct()->pluck('tahun_anggaran'))
            ->merge(DB::table('swakelola_realisasi')->distinct()->pluck('tahun_anggaran'))
            ->push($tahun)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        if ($availableYears->isEmpty()) {
            $availableYears = collect([$tahun]);
        }

// ✅ Definisikan tahun yang ingin digabung
$tahunList = [2024, 2025];

// ✅ Ambil total belanja pengadaan (RUP Struktur Anggaran) untuk tahun 2024 & 2025
$totalBelanja = DB::table('struktur_anggarans')
    ->where('kd_klpd', 'D264')
    ->whereIn('tahun_anggaran', $tahunList)
    ->sum('belanja_pengadaan');

// ✅ Ambil semua satker di struktur anggaran tahun 2024 & 2025
$satkers = DB::table('struktur_anggarans')
    ->select('kd_satker', 'nama_satker')
    ->whereIn('tahun_anggaran', $tahunList)
    ->where('kd_klpd', 'D264')
    ->distinct() // kalau perlu supaya satker tidak duplikat dari dua tahun
    ->get();

// ✅ Looping total transaksi per Satker
$totalTransaksi = 0;

foreach ($satkers as $satker) {
    $nilaiTender = DB::table('tender_pengumuman_data')
        ->where('kd_satker', $satker->kd_satker)
        ->whereIn('tahun', $tahunList)
        ->whereNotNull('pagu')
        ->where('pagu', '>', 0)
        ->sum('pagu');

    $nilaiNonTender = DB::table('non_tender_selesai')
        ->where('kd_satker', $satker->kd_satker)
        ->whereIn('tahun_anggaran', $tahunList)
        ->whereNotNull('pagu')
        ->where('pagu', '>', 0)
        ->sum('pagu');

    $nilaiV5 = DB::table('ekatalog_v5_pakets as v5')
        ->join('struktur_anggarans as s', 'v5.satker_id', '=', 's.kd_satker')
        ->where('s.kd_satker', $satker->kd_satker)
        ->whereIn('v5.tahun_anggaran', $tahunList)
        ->whereNotNull('v5.total_harga')
        ->where('v5.total_harga', '>', 0)
        ->sum('v5.total_harga');

    $nilaiV6 = DB::table('ekatalog_v6_pakets')
        ->where('nama_satker', $satker->nama_satker)
        ->whereIn('tahun_anggaran', $tahunList)
        ->whereNotNull('total_harga')
        ->where('total_harga', '>', 0)
        ->sum('total_harga');

    $nilaiToko = DB::table('toko_darings')
        ->where('kd_satker', $satker->kd_satker)
        ->whereIn('tahun', $tahunList)
        ->whereNotNull('valuasi')
        ->where('valuasi', '>', 0)
        ->sum('valuasi');

    $totalTransaksi += $nilaiTender + $nilaiNonTender + $nilaiV5 + $nilaiV6 + $nilaiToko;
}

// ✅ Hitung persentase realisasi total
$totalPersen = $totalBelanja > 0 ? round(($totalTransaksi / $totalBelanja) * 100, 2) : 0;

$today = Carbon::now();
$dashboardRecaps = $this->buildDashboardRecaps($tahun, $activeDashboardRange);
$methodDetailRows = $this->buildMethodDetailRows($tahun, $activeDashboardRange);

return view('users.home', compact(
    'today',
    'tahun',
    'kategoriChart2',
    'nonTenderCount',
    'tenderCount',
    'ekatalogCount',
    'belaCount',
    'chart1Data',
    'chart2Data',
    'totalNonTender',
    'totalTender',
    'totalEkatalogV5',
    'totalEkatalogV6',
    'totalRup',
    'rekapTokoDaring',
    'totalTokoDaringTransaksi',
    'totalTokoDaringNilai',
    'availableYears',
    'totalBelanja' ,
    'totalTransaksi',
    'totalPersen' ,
    'dashboardRecaps',
    'monthOptions',
    'selectedMonth',
    'selectedWeek',
    'availableWeeks',
    'activeWeekRange',
    'activeDashboardRange',
    'selectedStartDate',
    'selectedEndDate',
    'isCustomDateRangeActive',
    'methodDetailRows',
));

    }

    public function updateChartData(Request $request)
{
    $tahun = $request->input('tahun', Carbon::now()->year);
    $kategoriChart2 = $request->input('kategori_chart2', 'non_tender');

    // CHART 2: Update data berdasarkan kategori
    if ($kategoriChart2 == 'tender') {
        $chart2Data = TenderPengumumanData::where('tahun', $tahun)
            ->whereIn('status_tender', ['Selesai', 'Berlangsung'])
            ->select('jenis_pengadaan', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('jenis_pengadaan')
            ->pluck('jumlah', 'jenis_pengadaan');
    } else {
        $chart2Data = NonTenderPengumuman::where('tahun_anggaran', $tahun)
            ->whereIn('status_nontender', ['Selesai', 'Berlangsung'])
            ->select('jenis_pengadaan', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('jenis_pengadaan')
            ->pluck('jumlah', 'jenis_pengadaan');
    }

    // Mengembalikan data dalam format JSON untuk digunakan oleh frontend
    return response()->json(['chart2Data' => $chart2Data]);
}

    private function resolveDashboardWeekFilter(Request $request, $tahun)
    {
        $year = (int) $tahun;
        $requestedMonth = $request->input('bulan');
        $month = $requestedMonth === 'all' ? 'all' : (int) $request->input('bulan', Carbon::now()->year === $year ? Carbon::now()->month : 1);

        if ($month !== 'all' && ($month < 1 || $month > 12)) {
            $month = Carbon::now()->year === $year ? Carbon::now()->month : 1;
        }

        $weeks = $this->dashboardWeeksForMonth($year, $month);
        $defaultWeek = $month === 'all' ? 'all' : $this->dashboardDefaultWeek($weeks, $year, $month);
        $requestedWeek = $request->input('minggu', $defaultWeek);
        $week = $weeks->has($requestedWeek) ? $requestedWeek : $defaultWeek;

        return [
            'month' => $month,
            'week' => $week,
            'weeks' => $weeks,
            'range' => $weeks->get($week),
        ];
    }

    private function dashboardWeeksForMonth($tahun, $bulan)
    {
        if ($bulan === 'all') {
            $start = Carbon::create((int) $tahun, 1, 1)->startOfDay();
            $end = Carbon::create((int) $tahun, 12, 31)->endOfDay();

            return collect([
                'all' => [
                    'number' => 'all',
                    'start' => $start,
                    'end' => $end,
                    'label' => 'Semua Minggu',
                    'range_label' => 'Semua tahun ' . $tahun,
                ],
            ]);
        }

        $monthStart = Carbon::create((int) $tahun, (int) $bulan, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $weekStart = $monthStart->copy();

        $weeks = collect([
            'all' => [
                'number' => 'all',
                'start' => $monthStart->copy(),
                'end' => $monthEnd->copy(),
                'label' => 'Semua Minggu',
                'range_label' => 'Semua minggu bulan ' . $this->dashboardMonthOptions()->get((int) $bulan) . ' ' . $tahun,
            ],
        ]);

        while (!$weekStart->isFriday()) {
            $weekStart->addDay();
        }

        $weekNumber = 1;

        while ($weekStart->lte($monthEnd)) {
            $start = $weekStart->copy()->startOfDay();
            $end = $weekStart->copy()->addDays(6)->endOfDay();

            if ($end->gt($monthEnd)) {
                $end = $monthEnd->copy();
            }

            $weeks->put((string) $weekNumber, [
                'number' => $weekNumber,
                'start' => $start,
                'end' => $end,
                'label' => 'Minggu ' . $weekNumber,
                'range_label' => $this->dashboardDateLabel($start, $end),
            ]);

            $weekStart->addWeek();
            $weekNumber++;
        }

        return $weeks;
    }

    private function resolveDashboardCustomDateRange(Request $request, $tahun)
    {
        $startInput = $request->input('tanggal_mulai');
        $endInput = $request->input('tanggal_selesai');

        if (!$startInput && !$endInput) {
            return null;
        }

        $yearStart = Carbon::create((int) $tahun, 1, 1)->startOfDay();
        $yearEnd = Carbon::create((int) $tahun, 12, 31)->endOfDay();

        try {
            $start = $startInput ? Carbon::createFromFormat('Y-m-d', $startInput)->startOfDay() : $yearStart;
            $end = $endInput ? Carbon::createFromFormat('Y-m-d', $endInput)->endOfDay() : $yearEnd;
        } catch (\Exception $exception) {
            return null;
        }

        if ($end->lt($start)) {
            $swapStart = $end->copy()->startOfDay();
            $end = $start->copy()->endOfDay();
            $start = $swapStart;
        }

        return [
            'number' => 'custom',
            'start' => $start,
            'end' => $end,
            'label' => 'Tanggal Custom',
            'range_label' => $this->dashboardDateLabel($start, $end),
        ];
    }

    private function dashboardDefaultWeek($weeks, $tahun, $bulan)
    {
        $today = Carbon::now();

        if ($today->year === (int) $tahun && $today->month === (int) $bulan) {
            foreach ($weeks as $weekNumber => $range) {
                if ($weekNumber === 'all') {
                    continue;
                }

                if ($today->gte($range['start']) && $today->lte($range['end'])) {
                    return $weekNumber;
                }
            }
        }

        return $weeks->keys()->first(fn ($weekNumber) => $weekNumber !== 'all') ?? 'all';
    }

    private function dashboardMonthOptions()
    {
        return collect([
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ]);
    }

    private function dashboardDateLabel(Carbon $start, Carbon $end)
    {
        return $start->format('d') . ' ' . $this->dashboardMonthOptions()->get($start->month) . ' ' . $start->year
            . ' - ' . $end->format('d') . ' ' . $this->dashboardMonthOptions()->get($end->month) . ' ' . $end->year;
    }

    private function applyDashboardDateRange($query, $column, $dateRange, $tahun, $includeNullDates = false)
    {
        if (!$dateRange) {
            return $query;
        }

        $rangeStart = $dateRange['start']->copy();
        $rangeEnd = $dateRange['end']->copy();

        return $query->where(function ($query) use ($column, $rangeStart, $rangeEnd, $includeNullDates) {
            $query->where(function ($query) use ($column, $rangeStart, $rangeEnd) {
                $query->whereNotNull($column)
                    ->whereBetween($column, [$rangeStart, $rangeEnd]);
            });

            if ($includeNullDates) {
                $query->orWhereNull($column);
            }
        });
    }

    private function applyDashboardDateRangeExpression($query, $dateExpression, $dateRange, $tahun)
    {
        if (!$dateRange) {
            return $query;
        }

        $rangeStart = $dateRange['start']->copy();
        $rangeEnd = $dateRange['end']->copy();

        return $query->where(function ($query) use ($dateExpression, $rangeStart, $rangeEnd) {
            $query->whereRaw("{$dateExpression} IS NOT NULL")
                ->whereBetween(DB::raw($dateExpression), [$rangeStart, $rangeEnd]);
        });
    }

    private function applyDashboardDateRangeWithYearFloor($query, $column, $dateRange, $tahun)
    {
        if (!$dateRange) {
            return $query;
        }

        $yearStart = Carbon::create((int) $tahun, 1, 1)->startOfDay()->toDateTimeString();
        $rangeStart = $dateRange['start']->copy()->toDateTimeString();
        $rangeEnd = $dateRange['end']->copy()->toDateTimeString();
        $flooredDateExpression = "CASE WHEN {$column} < ? THEN ? ELSE {$column} END";

        return $query->where(function ($query) use ($column, $flooredDateExpression, $yearStart, $rangeStart, $rangeEnd) {
            $query->whereNotNull($column)
                ->whereRaw("{$flooredDateExpression} BETWEEN ? AND ?", [
                    $yearStart,
                    $yearStart,
                    $rangeStart,
                    $rangeEnd,
                ]);
        });
    }

    private function isFullBudgetYearRange($dateRange, $tahun)
    {
        if (!$dateRange) {
            return false;
        }

        return $dateRange['start']->isSameDay(Carbon::create($tahun, 1, 1)->startOfDay())
            && $dateRange['end']->isSameDay(Carbon::create($tahun, 12, 31)->endOfDay());
    }

    private function nonTenderRealizationAmountExpression($methodColumn, $tableAlias)
    {
        return "CASE
            WHEN {$methodColumn} LIKE '%Pengadaan Langsung%' THEN ROUND(COALESCE({$tableAlias}.nilai_negosiasi, 0), 0)
            WHEN {$methodColumn} LIKE '%Penunjukan Langsung%' THEN ROUND(COALESCE({$tableAlias}.nilai_kontrak, 0), 0)
            WHEN COALESCE({$tableAlias}.nilai_negosiasi, 0) > 0 THEN ROUND({$tableAlias}.nilai_negosiasi, 0)
            ELSE ROUND(COALESCE({$tableAlias}.nilai_kontrak, 0), 0)
        END";
    }

    private function buildDashboardRecaps($tahun, $dateRange = null)
    {
        $satkers = $this->dashboardSatkers($tahun);
        $penyediaPlanning = $this->penyediaPlanningBySatker($tahun, null, $dateRange);
        $tenderPlanning = $this->penyediaPlanningBySatker($tahun, 'tender', $dateRange);
        $epurchasingPlanning = $this->penyediaPlanningBySatker($tahun, 'epurchasing', $dateRange);
        $swakelolaPlanning = $this->swakelolaPlanningBySatker($tahun, $dateRange);

        $tenderQuery = DB::table('tender_selesai_nilai_data as nilai')
            ->join('tender_pengumuman_data as pengumuman', 'pengumuman.kd_tender', '=', 'nilai.kd_tender')
            ->select('pengumuman.nama_satker', DB::raw('COUNT(DISTINCT nilai.kd_tender) as paket'), DB::raw('COALESCE(SUM(ROUND(nilai.nilai_kontrak, 0)), 0) as nilai'))
            ->where('nilai.tahun', $tahun)
            ->where('nilai.kd_klpd', 'D264')
            ->whereNotNull('pengumuman.nama_satker')
            ->groupBy('pengumuman.nama_satker');

        $nonTenderAmount = $this->nonTenderRealizationAmountExpression('pengumuman.mtd_pemilihan', 'selesai');
        $nonTenderQuery = DB::table('non_tender_selesai as selesai')
            ->join('non_tender_pengumuman as pengumuman', 'pengumuman.kd_nontender', '=', 'selesai.kd_nontender')
            ->select('pengumuman.nama_satker', DB::raw('COUNT(DISTINCT selesai.kd_nontender) as paket'), DB::raw("COALESCE(SUM({$nonTenderAmount}), 0) as nilai"))
            ->where('selesai.tahun_anggaran', $tahun)
            ->where('pengumuman.kd_klpd', 'D264')
            ->whereNotNull('pengumuman.nama_satker')
            ->groupBy('pengumuman.nama_satker');


        $ekatalogV6Query = DB::table('ekatalog_v6_pakets')
            ->select('nama_satker', DB::raw('COUNT(*) as paket'), DB::raw('COALESCE(SUM(total_harga), 0) as nilai'))
            ->where('tahun_anggaran', $tahun)
            ->whereIn('status_pkt', ['ON_PROCESS', 'COMPLETED', 'PAYMENT_OUTSIDE_SYSTEM', 'ON_ADDENDUM'])
            ->whereNotNull('nama_satker')
            ->groupBy('nama_satker');

        $this->applyDashboardDateRangeWithYearFloor($tenderQuery, 'nilai.tgl_penetapan_pemenang', $dateRange, $tahun);
        $this->applyDashboardDateRangeWithYearFloor($nonTenderQuery, 'selesai.tgl_selesai_nontender', $dateRange, $tahun);
        if (!$this->isFullBudgetYearRange($dateRange, $tahun)) {
            $this->applyDashboardDateRangeWithYearFloor($ekatalogV6Query, 'tgl_order', $dateRange, $tahun);
        }

        $swakelolaRealisasiQuery = DB::table('swakelola_realisasi')
            ->select('nama_satker', DB::raw('COUNT(*) as paket'), DB::raw('COALESCE(SUM(nilai_realisasi), 0) as nilai'))
            ->where('tahun_anggaran', $tahun)
            ->whereNotNull('nama_satker')
            ->groupBy('nama_satker');

        $this->applyDashboardDateRangeWithYearFloor($swakelolaRealisasiQuery, 'tgl_realisasi', $dateRange, $tahun);

        $tenderRealisasi = $this->combineDashboardAggregates([
            $tenderQuery->get(),
        ]);

        $nonTenderRealisasi = $this->combineDashboardAggregates([
            $nonTenderQuery->get(),
        ]);

        $epurchasingRealisasi = $this->combineDashboardAggregates([
            $ekatalogV6Query->get(),
        ]);

        $swakelolaRealisasi = $this->combineDashboardAggregates([
            $swakelolaRealisasiQuery->get(),
        ]);

        $totalPlanning = $this->combineDashboardAggregates([$penyediaPlanning->values(), $swakelolaPlanning->values()]);
        $totalRealisasi = $this->combineDashboardAggregates([$tenderRealisasi->values(), $nonTenderRealisasi->values(), $epurchasingRealisasi->values(), $swakelolaRealisasi->values()]);

        return [
            'overall' => [
                'title' => 'Rekapitulasi Pengadaan Pada Pemerintah Provinsi Lampung',
                'subtitle' => 'Rencana pengadaan RUP dibandingkan realisasi tender, e-purchasing, dan swakelola.',
                'rows' => $this->dashboardRecapRows($satkers, $totalPlanning, $totalRealisasi),
            ],
            'tender' => [
                'title' => 'Rekapitulasi Pengadaan Melalui Tender',
                'subtitle' => 'Rencana penyedia metode tender dibandingkan tender selesai.',
                'rows' => $this->dashboardRecapRows($satkers, $tenderPlanning, $tenderRealisasi),
            ],
            'epurchasing' => [
                'title' => 'Rekapitulasi Pengadaan Melalui E-Purchasing',
                'subtitle' => 'Rencana penyedia metode e-purchasing dibandingkan paket e-katalog selesai.',
                'rows' => $this->dashboardRecapRows($satkers, $epurchasingPlanning, $epurchasingRealisasi),
            ],
            'swakelola' => [
                'title' => 'Rekapitulasi Pengadaan Melalui Swakelola',
                'subtitle' => 'Rencana swakelola dibandingkan realisasi swakelola.',
                'rows' => $this->dashboardRecapRows($satkers, $swakelolaPlanning, $swakelolaRealisasi),
            ],
        ];
    }

    private function buildMethodDetailRows($tahun, $dateRange = null)
    {
        $rows = collect($this->dashboardMethodDetailOrder())->mapWithKeys(function ($method) {
            return [$method => [
                'metode' => $method,
                'rencana_pagu' => 0,
                'rencana_paket' => 0,
                'realisasi_pagu' => 0,
                'realisasi_paket' => 0,
                'is_total' => false,
            ]];
        });

        $penyediaPlanningQuery = DB::table('penyedias')
            ->select('metode_pengadaan as metode', DB::raw('COUNT(*) as paket'), DB::raw('COALESCE(SUM(pagu), 0) as nilai'))
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->where('nama_klpd', 'Provinsi Lampung')
            ->groupBy('metode_pengadaan');

        $this->applyRupPlanningSnapshotCutoff($penyediaPlanningQuery, 'penyedias', 'PENYEDIA', $dateRange);

        $penyediaPlanning = $penyediaPlanningQuery->get();

        foreach ($penyediaPlanning as $row) {
            $this->addMethodDetailBucket($rows, $row->metode, 'planning', $row->paket, $row->nilai);
        }

        $swakelolaPlanning = DB::table('swakelolas')
            ->select(DB::raw('COUNT(*) as paket'), DB::raw('COALESCE(SUM(pagu), 0) as nilai'))
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->where('nama_klpd', 'Provinsi Lampung');

        $this->applyRupPlanningSnapshotCutoff($swakelolaPlanning, 'swakelolas', 'SWAKELOLA', $dateRange);

        $swakelolaPlanning = $swakelolaPlanning->first();

        $this->addMethodDetailBucket($rows, 'Swakelola', 'planning', $swakelolaPlanning->paket ?? 0, $swakelolaPlanning->nilai ?? 0);

        $tenderQuery = DB::table('tender_selesai_nilai_data as nilai')
            ->join('tender_pengumuman_data as pengumuman', 'pengumuman.kd_tender', '=', 'nilai.kd_tender')
            ->select('pengumuman.mtd_pemilihan as metode', DB::raw('COUNT(DISTINCT nilai.kd_tender) as paket'), DB::raw('COALESCE(SUM(ROUND(nilai.nilai_kontrak, 0)), 0) as nilai'))
            ->where('nilai.tahun', $tahun)
            ->where('nilai.kd_klpd', 'D264')
            ->groupBy('pengumuman.mtd_pemilihan');

        $nonTenderAmount = $this->nonTenderRealizationAmountExpression('pengumuman.mtd_pemilihan', 'selesai');
        $nonTenderQuery = DB::table('non_tender_selesai as selesai')
            ->join('non_tender_pengumuman as pengumuman', 'pengumuman.kd_nontender', '=', 'selesai.kd_nontender')
            ->select('pengumuman.mtd_pemilihan as metode', DB::raw('COUNT(DISTINCT selesai.kd_nontender) as paket'), DB::raw("COALESCE(SUM({$nonTenderAmount}), 0) as nilai"))
            ->where('selesai.tahun_anggaran', $tahun)
            ->where('pengumuman.kd_klpd', 'D264')
            ->groupBy('pengumuman.mtd_pemilihan');

        $ekatalogV6Query = DB::table('ekatalog_v6_pakets')
            ->select(DB::raw('COUNT(*) as paket'), DB::raw('COALESCE(SUM(total_harga), 0) as nilai'))
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->whereIn('status_pkt', ['ON_PROCESS', 'COMPLETED', 'PAYMENT_OUTSIDE_SYSTEM', 'ON_ADDENDUM']);

        $swakelolaRealisasiQuery = DB::table('swakelola_realisasi')
            ->select(DB::raw('COUNT(*) as paket'), DB::raw('COALESCE(SUM(nilai_realisasi), 0) as nilai'))
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264');

        $this->applyDashboardDateRangeWithYearFloor($tenderQuery, 'nilai.tgl_penetapan_pemenang', $dateRange, $tahun);
        $this->applyDashboardDateRangeWithYearFloor($nonTenderQuery, 'selesai.tgl_selesai_nontender', $dateRange, $tahun);
        if (!$this->isFullBudgetYearRange($dateRange, $tahun)) {
            $this->applyDashboardDateRangeWithYearFloor($ekatalogV6Query, 'tgl_order', $dateRange, $tahun);
        }
        $this->applyDashboardDateRangeWithYearFloor($swakelolaRealisasiQuery, 'tgl_realisasi', $dateRange, $tahun);

        foreach ($tenderQuery->get() as $row) {
            $this->addMethodDetailBucket($rows, $row->metode, 'realization', $row->paket, $row->nilai);
        }

        foreach ($nonTenderQuery->get() as $row) {
            $this->addMethodDetailBucket($rows, $row->metode, 'realization', $row->paket, $row->nilai);
        }

        $ekatalogV6Realisasi = $ekatalogV6Query->first();
        $this->addMethodDetailBucket($rows, 'E-Purchasing', 'realization', $ekatalogV6Realisasi->paket ?? 0, $ekatalogV6Realisasi->nilai ?? 0);

        $swakelolaRealisasi = $swakelolaRealisasiQuery->first();
        $this->addMethodDetailBucket($rows, 'Swakelola', 'realization', $swakelolaRealisasi->paket ?? 0, $swakelolaRealisasi->nilai ?? 0);

        $detailRows = $rows->values();

        return $detailRows->push([
            'metode' => 'Total',
            'rencana_pagu' => $detailRows->sum('rencana_pagu'),
            'rencana_paket' => $detailRows->sum('rencana_paket'),
            'realisasi_pagu' => $detailRows->sum('realisasi_pagu'),
            'realisasi_paket' => $detailRows->sum('realisasi_paket'),
            'is_total' => true,
        ])->values()->all();
    }

    private function dashboardMethodDetailOrder()
    {
        return [
            'E-Purchasing',
            'Kontes',
            'Pengadaan Langsung',
            'Pengecualian',
            'Penunjukan Langsung',
            'Seleksi',
            'Tender',
            'Swakelola',
        ];
    }

    private function addMethodDetailBucket($rows, $method, $section, $packageCount, $amount)
    {
        $method = $this->normalizeProcurementMethod($method);

        if (!$rows->has($method)) {
            return;
        }

        $row = $rows->get($method);

        if ($section === 'planning') {
            $row['rencana_paket'] += (int) $packageCount;
            $row['rencana_pagu'] += (float) $amount;
        }

        if ($section === 'realization') {
            $row['realisasi_paket'] += (int) $packageCount;
            $row['realisasi_pagu'] += (float) $amount;
        }

        $rows->put($method, $row);
    }

    private function normalizeProcurementMethod($method)
    {
        $method = trim((string) $method);
        $normalized = strtolower(preg_replace('/[\s_-]+/', ' ', $method));

        if ($normalized === '') {
            return '';
        }

        if (strpos($normalized, 'swakelola') !== false) {
            return 'Swakelola';
        }

        if (strpos($normalized, 'purchasing') !== false || strpos($normalized, 'katalog') !== false) {
            return 'E-Purchasing';
        }

        if (strpos($normalized, 'kontes') !== false) {
            return 'Kontes';
        }

        if (strpos($normalized, 'pengadaan langsung') !== false) {
            return 'Pengadaan Langsung';
        }

        if (strpos($normalized, 'pengecualian') !== false || strpos($normalized, 'dikecualikan') !== false || strpos($normalized, 'kecuali') !== false) {
            return 'Pengecualian';
        }

        if (strpos($normalized, 'penunjukan langsung') !== false) {
            return 'Penunjukan Langsung';
        }

        if (strpos($normalized, 'seleksi') !== false) {
            return 'Seleksi';
        }

        if (strpos($normalized, 'tender') !== false) {
            return 'Tender';
        }

        return $method;
    }

    private function dashboardSatkers($tahun)
    {
        $satkers = DB::table('struktur_anggarans')
            ->select('kd_satker', 'nama_satker')
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->whereNotNull('nama_satker')
            ->orderBy('nama_satker')
            ->get();

        if ($satkers->isEmpty()) {
            $satkers = DB::table('penyedias')
                ->select('kd_satker', 'nama_satker')
                ->where('tahun_anggaran', $tahun)
                ->where('kd_klpd', 'D264')
                ->whereNotNull('nama_satker')
                ->distinct()
                ->orderBy('nama_satker')
                ->get();
        }

        return $satkers->unique('nama_satker')->values();
    }

    private function penyediaPlanningBySatker($tahun, $metode = null, $dateRange = null)
    {
        $query = DB::table('penyedias')
            ->select('nama_satker', DB::raw('COUNT(*) as paket'), DB::raw('COALESCE(SUM(pagu), 0) as nilai'))
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->whereNotNull('nama_satker');

        if ($metode === 'tender') {
            $query->where(function ($query) {
                $query->where('metode_pengadaan', 'like', '%Tender%')
                    ->orWhere('metode_pengadaan', 'like', '%Seleksi%');
            });
        }

        if ($metode === 'epurchasing') {
            $query->where(function ($query) {
                $query->where('metode_pengadaan', 'like', '%Purchasing%')
                    ->orWhere('metode_pengadaan', 'like', '%Katalog%');
            });
        }

        $this->applyRupPlanningSnapshotCutoff($query, 'penyedias', 'PENYEDIA', $dateRange);

        return $query->groupBy('nama_satker')->get()->keyBy('nama_satker');
    }

    private function swakelolaPlanningBySatker($tahun, $dateRange = null)
    {
        $query = DB::table('swakelolas')
            ->select('nama_satker', DB::raw('COUNT(*) as paket'), DB::raw('COALESCE(SUM(pagu), 0) as nilai'))
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->whereNotNull('nama_satker');

        $this->applyRupPlanningSnapshotCutoff($query, 'swakelolas', 'SWAKELOLA', $dateRange);

        return $query->groupBy('nama_satker')->get()->keyBy('nama_satker');
    }

    private function applyRupPlanningSnapshotCutoff($query, $table, $jenisPaket, $dateRange)
    {
        if (!$dateRange || ($dateRange['number'] ?? null) !== 'custom') {
            return $query;
        }

        $cutoff = $dateRange['end']->copy();
        $revisionTypes = ['PENGAKTIFAN', 'SATUKESATU', 'SATUKEBANYAK'];

        return $query
            ->where(function ($query) use ($table, $cutoff) {
                $query->whereNull("{$table}.tgl_pengumuman_paket")
                    ->orWhere("{$table}.tgl_pengumuman_paket", '<=', $cutoff);
            })
            ->whereNotExists(function ($query) use ($table, $jenisPaket, $cutoff, $revisionTypes) {
                $query->select(DB::raw(1))
                    ->from('rup_history_kaji_ulang as history')
                    ->whereColumn('history.kd_rup_baru', "{$table}.kd_rup")
                    ->where('history.jenis_paket', $jenisPaket)
                    ->whereIn('history.jenis_revisi', $revisionTypes)
                    ->where('history.tgl_kaji_ulang', '>', $cutoff);
            });
    }

    private function combineDashboardAggregates($collections)
    {
        $combined = collect();

        foreach ($collections as $collection) {
            foreach ($collection as $row) {
                $namaSatker = $row->nama_satker;
                $current = $combined->get($namaSatker, (object) [
                    'nama_satker' => $namaSatker,
                    'paket' => 0,
                    'nilai' => 0,
                ]);

                $current->paket += (int) ($row->paket ?? 0);
                $current->nilai += (float) ($row->nilai ?? 0);

                $combined->put($namaSatker, $current);
            }
        }

        return $combined;
    }

    private function dashboardRecapRows($satkers, $planning, $realisasi)
    {
        return $satkers->map(function ($satker) use ($planning, $realisasi) {
            $namaSatker = $satker->nama_satker;
            $rencana = $planning->get($namaSatker, (object) ['paket' => 0, 'nilai' => 0]);
            $realisasiSatker = $realisasi->get($namaSatker, (object) ['paket' => 0, 'nilai' => 0]);
            $pagu = (float) $rencana->nilai;
            $nilaiRealisasi = (float) $realisasiSatker->nilai;

            return [
                'nama_satker' => $namaSatker,
                'rencana_paket' => (int) $rencana->paket,
                'rencana_pagu' => $pagu,
                'realisasi_paket' => (int) $realisasiSatker->paket,
                'realisasi_nilai' => $nilaiRealisasi,
                'persentase' => $pagu > 0 ? round(($nilaiRealisasi / $pagu) * 100, 2) : 0,
            ];
        })->values();
    }



}
