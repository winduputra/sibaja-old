<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View; // ✅ BENAR di sini
use Spipu\Html2Pdf\Html2Pdf;

// use Barryvdh\DomPDF\Facade\Pdf; // ❌ Kalau kamu pakai Html2Pdf, hapus ini


class MonitoringController extends Controller
{
 

    public function presentaseRealisasi(Request $request)
    {
        $tahunListOptions = $this->getRealisasiTahunList();
        $selectedTahun = (int) ($request->get('tahun') ?: ($tahunListOptions[0] ?? date('Y')));
        $tahunList = [$selectedTahun];

        $satkers = $this->getRealisasiSatkers($tahunList);

        $data = $this->buildRealisasiSatkerData($satkers, $tahunList);

        // Ambil list nama satker dari seluruh sumber realisasi/RUP.
        $listSatker = $satkers->pluck('nama_satker')->unique()->sort()->values();

        // Filter data jika satker dipilih
        $data = $data->filter(function ($item) use ($request) {
            if ($request->filled('satker')) {
                return $item->nama_satker === $request->get('satker');
            }
            return true;
        })->values();

        $summary = $this->buildRealisasiSummary($data);
        $view = (auth()->user()->role_id == 2)
        ? 'users.monitoring.presentase-realisasi'
        : 'monitoring.presentase-realisasi';

    return view($view, [
        'data' => $data,
        'tahun' => implode(', ', $tahunList),
        'tahunListOptions' => $tahunListOptions,
        'listSatker' => $listSatker,
        'summary' => $summary,
    ]);


    }
    
public function exportRealisasiToPDF(Request $request)
{
    $tahunParam = $request->get('tahun');
    $satkerFilter = $request->get('satker');
    $mode = $request->get('mode', 'V'); // 'V' = view, 'D' = download

    $tahunListOptions = $this->getRealisasiTahunList();
    $selectedTahun = (int) ($tahunParam ?: ($tahunListOptions[0] ?? date('Y')));
    $tahunList = [$selectedTahun];

    $satkers = $this->getRealisasiSatkers($tahunList);
    $data = $this->buildRealisasiSatkerData($satkers, $tahunList);

    // Filter by satker if provided
    if ($satkerFilter) {
        $data = $data->filter(function ($item) use ($satkerFilter) {
            return $item->nama_satker === $satkerFilter;
        })->values();
    }

    $summary = $this->buildRealisasiSummary($data);

    // Render HTML to PDF
    $html = View::make('exports.realisasi-presentase', [
        'data' => $data,
        'tahun' => implode(', ', $tahunList),
        'summary' => $summary,
    ])->render();

    $pdf = new Html2Pdf('L', 'A4', 'en');
    $pdf->writeHTML($html);

    $fileName = 'Realisasi_Pengadaan_' . implode('_', $tahunList) . '.pdf';

    if ($mode === 'D') {
        return response($pdf->output($fileName, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    } else {
        $pdf->output($fileName);
        exit;
    }
}

private function getRealisasiSatkers(array $tahunList)
{
    return collect([
        DB::table('satkers')
            ->whereIn('tahun_anggaran', $tahunList)
            ->pluck('nama_satker'),
        DB::table('penyedias')
            ->whereIn('tahun_anggaran', $tahunList)
            ->pluck('nama_satker'),
        DB::table('swakelolas')
            ->whereIn('tahun_anggaran', $tahunList)
            ->pluck('nama_satker'),
        DB::table('tender_pengumuman_data')
            ->whereIn('tahun', $tahunList)
            ->pluck('nama_satker'),
        DB::table('non_tender_pengumuman')
            ->whereIn('tahun_anggaran', $tahunList)
            ->pluck('nama_satker'),
        DB::table('ekatalog_v5_pakets')
            ->whereIn('tahun_anggaran', $tahunList)
            ->pluck('nama_satker'),
        DB::table('ekatalog_v6_pakets')
            ->whereIn('tahun_anggaran', $tahunList)
            ->pluck('nama_satker'),
        DB::table('swakelola_realisasi')
            ->whereIn('tahun_anggaran', $tahunList)
            ->pluck('nama_satker'),
    ])
        ->flatten()
        ->filter()
        ->unique()
        ->sort()
        ->values()
        ->map(function ($namaSatker) {
            return (object) ['nama_satker' => $namaSatker];
        });
}

private function buildRealisasiSatkerData($satkers, array $tahunList)
{
    return $satkers->map(function ($satker) use ($tahunList) {
        return $this->buildRealisasiSatkerRow($satker->nama_satker, $tahunList);
    });
}

private function buildRealisasiSatkerRow($namaSatker, array $tahunList)
{
    $paguPenyedia = DB::table('penyedias')
        ->where('nama_satker', $namaSatker)
        ->whereIn('tahun_anggaran', $tahunList)
        ->sum('pagu');

    $paguSwakelola = DB::table('swakelolas')
        ->where('nama_satker', $namaSatker)
        ->whereIn('tahun_anggaran', $tahunList)
        ->sum('pagu');

    $nilaiTender = DB::table('tender_pengumuman_data')
        ->where('nama_satker', $namaSatker)
        ->whereIn('tahun', $tahunList)
        ->where('status_tender', 'Selesai')
        ->sum('pagu');

    $nilaiNonTender = DB::table('non_tender_pengumuman')
        ->where('nama_satker', $namaSatker)
        ->whereIn('tahun_anggaran', $tahunList)
        ->where('status_nontender', 'Selesai')
        ->sum('pagu');

    $nilaiEPurchasing = DB::table('ekatalog_v5_pakets')
        ->where('nama_satker', $namaSatker)
        ->whereIn('tahun_anggaran', $tahunList)
        ->sum('total_harga')
        + DB::table('ekatalog_v6_pakets')
            ->where('nama_satker', $namaSatker)
            ->whereIn('tahun_anggaran', $tahunList)
            ->sum('total_harga');

    $realisasiPenyedia = $nilaiTender + $nilaiNonTender + $nilaiEPurchasing;

    $realisasiSwakelola = DB::table('swakelola_realisasi')
        ->where('nama_satker', $namaSatker)
        ->whereIn('tahun_anggaran', $tahunList)
        ->sum('nilai_realisasi');

    $paguGlobal = $paguPenyedia + $paguSwakelola;
    $realisasiGlobal = $realisasiPenyedia + $realisasiSwakelola;

    return (object) [
        'nama_satker' => $namaSatker,
        'pagu_penyedia' => $paguPenyedia,
        'realisasi_penyedia' => $realisasiPenyedia,
        'persentase_penyedia' => $this->percent($realisasiPenyedia, $paguPenyedia),
        'pagu_swakelola' => $paguSwakelola,
        'realisasi_swakelola' => $realisasiSwakelola,
        'persentase_swakelola' => $this->percent($realisasiSwakelola, $paguSwakelola),
        'pagu_global' => $paguGlobal,
        'realisasi_global' => $realisasiGlobal,
        'persentase_global' => $this->percent($realisasiGlobal, $paguGlobal),
    ];
}

private function buildRealisasiSummary($data)
{
    $paguPenyedia = $data->sum('pagu_penyedia');
    $realisasiPenyedia = $data->sum('realisasi_penyedia');
    $paguSwakelola = $data->sum('pagu_swakelola');
    $realisasiSwakelola = $data->sum('realisasi_swakelola');
    $paguGlobal = $paguPenyedia + $paguSwakelola;
    $realisasiGlobal = $realisasiPenyedia + $realisasiSwakelola;

    return (object) [
        'pagu_penyedia' => $paguPenyedia,
        'realisasi_penyedia' => $realisasiPenyedia,
        'persentase_penyedia' => $this->percent($realisasiPenyedia, $paguPenyedia),
        'pagu_swakelola' => $paguSwakelola,
        'realisasi_swakelola' => $realisasiSwakelola,
        'persentase_swakelola' => $this->percent($realisasiSwakelola, $paguSwakelola),
        'pagu_global' => $paguGlobal,
        'realisasi_global' => $realisasiGlobal,
        'persentase_global' => $this->percent($realisasiGlobal, $paguGlobal),
    ];
}

private function percent($realisasi, $pagu)
{
    return $pagu > 0 ? round(($realisasi / $pagu) * 100, 2) : 0;
}

private function getRealisasiTahunList()
{
    return collect([
        DB::table('satkers')->pluck('tahun_anggaran'),
        DB::table('penyedias')->pluck('tahun_anggaran'),
        DB::table('swakelolas')->pluck('tahun_anggaran'),
        DB::table('tender_pengumuman_data')->pluck('tahun'),
        DB::table('non_tender_pengumuman')->pluck('tahun_anggaran'),
        DB::table('ekatalog_v5_pakets')->pluck('tahun_anggaran'),
        DB::table('ekatalog_v6_pakets')->pluck('tahun_anggaran'),
        DB::table('swakelola_realisasi')->pluck('tahun_anggaran'),
    ])
        ->flatten()
        ->filter()
        ->map(function ($tahun) {
            return (int) $tahun;
        })
        ->filter()
        ->unique()
        ->sortDesc()
        ->values()
        ->all();
}


    
public function rekapRealisasi(Request $request)
{
    $tahun = $request->get('tahun', date('Y'));
    $filterSatker = $request->get('satker');
  

    // Ambil daftar satker (Gunakan satkers table, fallback ke penyedias jika kosong)
    $satkerQuery = DB::table('satkers')
        ->select('kd_satker', 'nama_satker')
        ->where('tahun_anggaran', $tahun)
        ->where('kd_klpd', 'D264');

    if ($satkerQuery->count() == 0) {
        $satkerQuery = DB::table('penyedias')
            ->select('kd_satker', 'nama_satker')
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->distinct();
    }

    $satkerList = $satkerQuery->when($filterSatker, fn($q) => $q->where('nama_satker', $filterSatker))
        ->get();

    // Daftar satker untuk filter
    $listSatker = DB::table('satkers')
        ->where('tahun_anggaran', $tahun)
        ->pluck('nama_satker')
        ->unique();

    if ($listSatker->isEmpty()) {
        $listSatker = DB::table('penyedias')
            ->where('tahun_anggaran', $tahun)
            ->pluck('nama_satker')
            ->unique();
    }
    
    $listSatker = $listSatker->sort()->values();

    $data = $satkerList->map(function ($satker) use ($tahun) {
        $namaSatker = $satker->nama_satker;
        $kdSatker = $satker->kd_satker;

        return $this->getSatkerRealisasi($tahun, $kdSatker, $namaSatker);
    });

    $view = (auth()->user()->role_id == 2)
    ? 'users.monitoring.rekap-realisasi'
    : 'monitoring.rekap-realisasi';

return view($view, [
    'data' => $data,
    'tahun' => $tahun,
    'listSatker' => $listSatker,
]);

}
public function exportRekapRealisasiToPDF(Request $request)
{
    $tahun = $request->get('tahun', date('Y'));
    $mode = $request->get('mode', 'V'); // 'V' = view / stream, 'D' = download

    // Ambil daftar satker (Gunakan satkers table, fallback ke penyedias jika kosong)
    $satkerQuery = DB::table('satkers')
        ->select('kd_satker', 'nama_satker')
        ->where('tahun_anggaran', $tahun)
        ->where('kd_klpd', 'D264');

    if ($satkerQuery->count() == 0) {
        $satkerQuery = DB::table('penyedias')
            ->select('kd_satker', 'nama_satker')
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->distinct();
    }

    $satkerList = $satkerQuery->get();

    $data = $satkerList->map(function ($satker) use ($tahun) {
        return $this->getSatkerRealisasi($tahun, $satker->kd_satker, $satker->nama_satker);
    });

    // Render Blade ke HTML biasa
    $html = View('exports.rekap-realisasi', compact('data', 'tahun'))->render();

    // Generate PDF dengan HTML2PDF
    $html2pdf = new Html2Pdf('L', 'A4', 'en');
    $html2pdf->writeHTML($html);

    $fileName = "rekap-realisasi-{$tahun}.pdf";

    if ($mode === 'D') {
        $html2pdf->output($fileName, 'D'); // Download
    } else {
        $html2pdf->output($fileName, 'I'); // Inline / View
    }

    return; // Agar tidak ada response tambahan dari Laravel
}

protected function getSatkerRealisasi($tahun, $kdSatker, $namaSatker)
{
    return [
        'nama_satker' => $namaSatker,
        'total_paket_tender' => DB::table('tender_selesai_data')->where('tahun', $tahun)->where('nama_satker', $namaSatker)->where('status_tender', 'Selesai')->count(),
        'total_nilai_tender' => DB::table('tender_selesai_nilai_data')->where('tahun', $tahun)->where('nama_satker', $namaSatker)->sum('nilai_kontrak'),

        'total_paket_nontender' => DB::table('non_tender_selesai')->where('tahun_anggaran', $tahun)->where('nama_satker', $namaSatker)->where('status_nontender', 'Selesai')->count(),
        'total_nilai_nontender' => DB::table('non_tender_selesai')->where('tahun_anggaran', $tahun)->where('nama_satker', $namaSatker)->sum('nilai_kontrak'),

        'total_paket_ekatalog' => DB::table('ekatalog_v5_pakets')->where('tahun_anggaran', $tahun)->where('nama_satker', $namaSatker)->where('paket_status_str', 'Paket Selesai')->count()
            + DB::table('ekatalog_v6_pakets')->where('tahun_anggaran', $tahun)->where('nama_satker', $namaSatker)->where('status_pkt', 'COMPLETED')->count(),
        'total_nilai_ekatalog' => DB::table('ekatalog_v5_pakets')->where('tahun_anggaran', $tahun)->where('nama_satker', $namaSatker)->where('paket_status_str', 'Paket Selesai')->sum('total_harga')
            + DB::table('ekatalog_v6_pakets')->where('tahun_anggaran', $tahun)->where('nama_satker', $namaSatker)->where('status_pkt', 'COMPLETED')->sum('total_harga'),

        'total_paket_tokodaring' => DB::table('toko_darings')->where('tahun', $tahun)->where('nama_satker', $namaSatker)->where('status_verif', 'verified')->count(),
        'total_nilai_tokodaring' => DB::table('toko_darings')->where('tahun', $tahun)->where('nama_satker', $namaSatker)->where('status_verif', 'verified')->sum('valuasi'),

        'total_paket_swakelola' => DB::table('swakelola_realisasi')->where('tahun_anggaran', $tahun)->where('nama_satker', $namaSatker)->count(),
        'total_nilai_swakelola' => DB::table('swakelola_realisasi')->where('tahun_anggaran', $tahun)->where('nama_satker', $namaSatker)->sum('nilai_realisasi'),
    ];
}

    public function rekapRealisasiBerlangsung(Request $request)
    {
        $tahunListOptions = $this->getRealisasiBerlangsungTahunList();
        $tahun = (int) ($request->get('tahun') ?: ($tahunListOptions[0] ?? date('Y')));
        $filterSatker = $request->get('satker');

        $satkerList = $this->getRealisasiBerlangsungSatkers($tahun);
        $listSatker = $satkerList->pluck('nama_satker')->unique()->sort()->values();

        // Loop per Satker
        $data = $satkerList
            ->when($filterSatker, fn($items) => $items->where('nama_satker', $filterSatker))
            ->values()
            ->map(function ($satker) use ($tahun) {
                $namaSatker = $satker->nama_satker;

                // Hitung data tender, non-tender, dan e-katalog (sesuai sumber ongoing report)
                $totalPaketTender = DB::table('tender_pengumuman_data')
                    ->where('tahun', $tahun)
                    ->where('nama_satker', $namaSatker)
                    ->where('status_tender', 'Berlangsung')
                    ->count();

                $totalNilaiTender = DB::table('tender_pengumuman_data')
                    ->where('tahun', $tahun)
                    ->where('nama_satker', $namaSatker)
                    ->where('status_tender', 'Berlangsung')
                    ->sum('pagu');

                $totalPaketNonTender = DB::table('non_tender_pengumuman')
                    ->where('tahun_anggaran', $tahun)
                    ->where('nama_satker', $namaSatker)
                    ->where('status_nontender', 'Berlangsung')
                    ->count();

                $totalNilaiNonTender = DB::table('non_tender_pengumuman')
                    ->where('tahun_anggaran', $tahun)
                    ->where('nama_satker', $namaSatker)
                    ->where('status_nontender', 'Berlangsung')
                    ->sum('pagu');

                $totalPaketEkatalogV5 = DB::table('ekatalog_v5_pakets')
                    ->where('tahun_anggaran', $tahun)
                    ->where('nama_satker', $namaSatker)
                    ->where('paket_status_str', 'Paket Proses')
                    ->count();

                $totalNilaiEkatalogV5 = DB::table('ekatalog_v5_pakets')
                    ->where('tahun_anggaran', $tahun)
                    ->where('nama_satker', $namaSatker)
                    ->where('paket_status_str', 'Paket Proses')
                    ->sum('total_harga');

                $totalPaketEkatalogV6 = DB::table('ekatalog_v6_pakets')
                    ->where('tahun_anggaran', $tahun)
                    ->where('nama_satker', $namaSatker)
                    ->where('status_pkt', 'PROGRESS')
                    ->count();

                $totalNilaiEkatalogV6 = DB::table('ekatalog_v6_pakets')
                    ->where('tahun_anggaran', $tahun)
                    ->where('nama_satker', $namaSatker)
                    ->where('status_pkt', 'PROGRESS')
                    ->sum('total_harga');

                return [
                    'nama_satker' => $namaSatker,
                    'total_paket_tender' => $totalPaketTender,
                    'total_nilai_tender' => $totalNilaiTender,
                    'total_paket_nontender' => $totalPaketNonTender,
                    'total_nilai_nontender' => $totalNilaiNonTender,
                    'total_paket_ekatalog' => $totalPaketEkatalogV5 + $totalPaketEkatalogV6,
                    'total_nilai_ekatalog' => $totalNilaiEkatalogV5 + $totalNilaiEkatalogV6,
                ];
            });

        $view = (auth()->user()->role_id == 2)
            ? 'users.monitoring.rekap-realisasi-berlangsung'
            : 'monitoring.rekap-realisasi-berlangsung';

        return view($view, [
            'data' => $data,
            'tahun' => $tahun,
            'listSatker' => $listSatker,
            'tahunListOptions' => $tahunListOptions,
        ]);

}
public function exportRealisasiBerlangsungPdf(Request $request)
{
    $tahun = $request->get('tahun', date('Y'));
    $mode = $request->get('mode', 'V'); // 'V' = view, 'D' = download

    // Ambil data realisasi berlangsung
    $data = $this->getRealisasiBerlangsungData($tahun); // return Collection

    // Render Blade ke HTML
    $html = View('exports.rekap-realisasi-berlangsung', compact('data', 'tahun'))->render();

    // Generate PDF pakai HTML2PDF
    $html2pdf = new Html2Pdf('L', 'A3', 'en', true, 'UTF-8', [5, 5, 5, 5]); // kiri, atas, kanan, bawah

    $html2pdf->writeHTML($html);

    $fileName = "rekap-realisasi-berlangsung-{$tahun}.pdf";

    // Output sesuai mode
    if ($mode === 'D') {
        $html2pdf->output($fileName, 'D'); // Download
    } else {
        $html2pdf->output($fileName, 'I'); // Inline view
    }

    return; // Pastikan tidak return double response
}
    protected function getRealisasiBerlangsungData($tahun)
    {
        return $this->getRealisasiBerlangsungSatkers($tahun)->map(function ($satker) use ($tahun) {
            $namaSatker = $satker->nama_satker;
    
            // Tender Pengumuman
            $totalPaketTender = DB::table('tender_pengumuman_data')
                ->where('tahun', $tahun)
                ->where('nama_satker', $namaSatker)
                ->where('status_tender', 'Berlangsung')
                ->count();
    
            $totalNilaiTender = DB::table('tender_pengumuman_data')
                ->where('tahun', $tahun)
                ->where('nama_satker', $namaSatker)
                ->where('status_tender', 'Berlangsung')
                ->sum('pagu');
    
            // Non-Tender Pengumuman
            $totalPaketNonTender = DB::table('non_tender_pengumuman')
                ->where('tahun_anggaran', $tahun)
                ->where('nama_satker', $namaSatker)
                ->where('status_nontender', 'Berlangsung')
                ->count();
    
            $totalNilaiNonTender = DB::table('non_tender_pengumuman')
                ->where('tahun_anggaran', $tahun)
                ->where('nama_satker', $namaSatker)
                ->where('status_nontender', 'Berlangsung')
                ->sum('pagu');
    
            // E-Katalog V5
            $totalPaketEkatalogV5 = DB::table('ekatalog_v5_pakets')
                ->where('tahun_anggaran', $tahun)
                ->where('nama_satker', $namaSatker)
                ->where('paket_status_str', 'Paket Proses')
                ->count();
    
            $totalNilaiEkatalogV5 = DB::table('ekatalog_v5_pakets')
                ->where('tahun_anggaran', $tahun)
                ->where('nama_satker', $namaSatker)
                ->where('paket_status_str', 'Paket Proses')
                ->sum('total_harga');
    
            // E-Katalog V6
            $totalPaketEkatalogV6 = DB::table('ekatalog_v6_pakets')
                ->where('tahun_anggaran', $tahun)
                ->where('nama_satker', $namaSatker)
                ->where('status_pkt', 'PROGRESS')
                ->count();
    
            $totalNilaiEkatalogV6 = DB::table('ekatalog_v6_pakets')
                ->where('tahun_anggaran', $tahun)
                ->where('nama_satker', $namaSatker)
                ->where('status_pkt', 'PROGRESS')
                ->sum('total_harga');
    
            return [
                'nama_satker' => $namaSatker,
                'total_paket_tender' => $totalPaketTender,
                'total_nilai_tender' => $totalNilaiTender,
                'total_paket_nontender' => $totalPaketNonTender,
                'total_nilai_nontender' => $totalNilaiNonTender,
                'total_paket_ekatalog' => $totalPaketEkatalogV5 + $totalPaketEkatalogV6,
                'total_nilai_ekatalog' => $totalNilaiEkatalogV5 + $totalNilaiEkatalogV6,
            ];
        });
    }

    private function getRealisasiBerlangsungTahunList()
    {
        return collect([
            DB::table('tender_pengumuman_data')->distinct()->pluck('tahun'),
            DB::table('non_tender_pengumuman')->distinct()->pluck('tahun_anggaran'),
            DB::table('ekatalog_v5_pakets')->distinct()->pluck('tahun_anggaran'),
            DB::table('ekatalog_v6_pakets')->distinct()->pluck('tahun_anggaran'),
        ])
            ->flatten()
            ->filter()
            ->map(fn($tahun) => (int) $tahun)
            ->push(2026)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    private function getRealisasiBerlangsungSatkers(int $tahun)
    {
        $sources = [
            ['table' => 'satkers', 'year_column' => 'tahun_anggaran', 'kd_column' => 'kd_satker', 'needs_kd_klpd' => true],
            ['table' => 'penyedias', 'year_column' => 'tahun_anggaran', 'kd_column' => 'kd_satker', 'needs_kd_klpd' => true],
            ['table' => 'tender_pengumuman_data', 'year_column' => 'tahun', 'kd_column' => 'kd_satker'],
            ['table' => 'non_tender_pengumuman', 'year_column' => 'tahun_anggaran', 'kd_column' => 'kd_satker'],
            ['table' => 'ekatalog_v5_pakets', 'year_column' => 'tahun_anggaran', 'kd_column' => 'satker_id'],
            ['table' => 'ekatalog_v6_pakets', 'year_column' => 'tahun_anggaran', 'kd_column' => 'kd_satker_str'],
        ];

        return collect($sources)
            ->flatMap(function ($source) use ($tahun) {
                $kdColumn = $source['kd_column'] ?? null;
                $query = DB::table($source['table'])
                    ->selectRaw(($kdColumn ? "$kdColumn as kd_satker" : 'NULL as kd_satker') . ', nama_satker')
                    ->where($source['year_column'], $tahun)
                    ->distinct();

                if (!empty($source['needs_kd_klpd'])) {
                    $query->where('kd_klpd', 'D264');
                }

                return $query->get();
            })
            ->filter(function ($satker) {
                return filled($satker->nama_satker);
            })
            ->unique(function ($satker) {
                return trim((string) $satker->nama_satker);
            })
            ->sortBy('nama_satker')
            ->values();
    }
    public function kontrak(Request $request)
    {
        $tahun = $request->get('tahun', $request->get('tahun_anggaran', date('Y')));
        $filterSatker = $request->get('nama_satker', '');

        $tahunList = config('api.inaproc.endpoints.tender_selesai_nilai.supported_years', [2025, 2026]);

        $allSatker = DB::table('struktur_anggarans')
            ->select('nama_satker')
            ->where('tahun_anggaran', $tahun)
            ->union(
                DB::table('tender_selesai_nilai_data')
                    ->select('nama_satker')
                    ->where('tahun', $tahun)
            )
            ->orderBy('nama_satker')
            ->pluck('nama_satker')
            ->unique()
            ->toArray();

        $targetSatker = empty($filterSatker) ? $allSatker : [$filterSatker];

        $tenderCount = DB::table('tender_selesai_nilai_data')
            ->select('nama_satker', DB::raw('count(distinct kd_tender) as total_paket'))
            ->where('tahun', $tahun)
            ->whereIn('nama_satker', $targetSatker)
            ->whereNotNull('kd_tender')
            ->groupBy('nama_satker')
            ->pluck('total_paket', 'nama_satker');

        $paguPerSatker = DB::table('tender_selesai_nilai_data')
            ->select('nama_satker', DB::raw('sum(pagu) as total_pagu'))
            ->where('tahun', $tahun)
            ->whereIn('nama_satker', $targetSatker)
            ->whereNotNull('kd_tender')
            ->groupBy('nama_satker')
            ->pluck('total_pagu', 'nama_satker');

        $belumKontrakPerSatker = DB::table('tender_selesai_nilai_data as ts')
            ->leftJoin('kontrak_data as k', 'ts.kd_tender', '=', 'k.kd_tender')
            ->select('ts.nama_satker', DB::raw('count(distinct ts.kd_tender) as total_belum_kontrak'))
            ->where('ts.tahun', $tahun)
            ->whereIn('ts.nama_satker', $targetSatker)
            ->whereNotNull('ts.kd_tender')
            ->whereNull('k.kd_tender')
            ->groupBy('ts.nama_satker')
            ->pluck('total_belum_kontrak', 'ts.nama_satker');

        $result = [];
        foreach ($targetSatker as $satker) {
            $result[] = [
                'nama_satker' => $satker,
                'total_paket' => $tenderCount[$satker] ?? 0,
                'total_pagu' => $paguPerSatker[$satker] ?? 0,
                'total_kontrak' => $belumKontrakPerSatker[$satker] ?? 0,
            ];
        }

        $totals = [
            'total_paket' => array_sum(array_column($result, 'total_paket')),
            'total_pagu' => array_sum(array_column($result, 'total_pagu')),
            'total_kontrak' => array_sum(array_column($result, 'total_kontrak')),
        ];

        $totalTenderSelesai = DB::table('tender_selesai_nilai_data')
            ->where('tahun', $tahun)
            ->whereNotNull('kd_tender')
            ->distinct('kd_tender')
            ->count('kd_tender');

        $totalKontrak = DB::table('tender_selesai_nilai_data as ts')
            ->join('kontrak_data as k', 'ts.kd_tender', '=', 'k.kd_tender')
            ->where('ts.tahun', $tahun)
            ->whereNotNull('ts.kd_tender')
            ->distinct('ts.kd_tender')
            ->count('ts.kd_tender');

        $selisih = DB::table('tender_selesai_nilai_data as ts')
            ->leftJoin('kontrak_data as k', 'ts.kd_tender', '=', 'k.kd_tender')
            ->where('ts.tahun', $tahun)
            ->whereNotNull('ts.kd_tender')
            ->whereNull('k.kd_tender')
            ->distinct('ts.kd_tender')
            ->count('ts.kd_tender');

        $view = auth()->user()->role_id == 2
        ? 'users.monitoring.kontrak'
        : 'monitoring.kontrak';

        return view($view, [
            'data' => $result,
            'tahun' => $tahun,
            'tahunList' => $tahunList,
            'satkerList' => $allSatker,
            'namaSatkerList' => $allSatker,
            'filterSatker' => $filterSatker,
            'totals' => $totals,
            'totalTenderSelesai' => $totalTenderSelesai,
            'totalKontrak' => $totalKontrak,
            'selisih' => $selisih,
        ]);
    }
    public function kontrakDetail($satker, Request $request)
    {
        $tahun = $request->get('tahun', $request->get('tahun_anggaran', date('Y')));
        $satker = urldecode($satker);
    
        $data = DB::table('tender_selesai_nilai_data as ts')
            ->leftJoin('kontrak_data as k', 'ts.kd_tender', '=', 'k.kd_tender')
            ->select('ts.kd_tender', 'ts.nama_paket', 'ts.pagu', 'ts.nama_satker')
            ->where('ts.tahun', $tahun)
            ->where('ts.nama_satker', $satker)
            ->whereNotNull('ts.kd_tender')
            ->whereNull('k.kd_tender')
            ->orderBy('ts.nama_paket')
            ->get();
    
        $totalPagu = $data->sum('pagu');
    
        $view = auth()->user()->role_id == 2
            ? 'users.monitoring.kontrak-detail'
            : 'monitoring.kontrak-detail';
    
        return view($view, compact('data', 'satker', 'tahun', 'totalPagu'));
    }
    
    
    public function exportKontrakDetailPdf($satker, Request $request)
{
    $tahun = $request->get('tahun', $request->get('tahun_anggaran', date('Y')));
    $satker = urldecode($satker);
    $mode = $request->get('mode', 'V');

    $data = DB::table('tender_selesai_nilai_data as ts')
        ->leftJoin('kontrak_data as k', 'ts.kd_tender', '=', 'k.kd_tender')
        ->select('ts.kd_tender', 'ts.nama_paket', 'ts.pagu')
        ->where('ts.tahun', $tahun)
        ->where('ts.nama_satker', $satker)
        ->whereNotNull('ts.kd_tender')
        ->whereNull('k.kd_tender')
        ->orderBy('ts.nama_paket')
        ->get();

    $totalPagu = $data->sum('pagu');

    // Pilih view berdasarkan role (admin atau user biasa)
    $view = auth()->user()->role_id == 2
        ? 'monitoring.kontrak-detail-pdf'
        : 'users.monitoring.kontrak-detail-pdf';

    // Render HTML ke PDF
    $html = View::make($view, compact('data', 'satker', 'tahun', 'totalPagu'))->render();

    $pdf = new Html2Pdf('L', 'A3', 'en');
    $pdf->writeHTML($html);

    $fileName = "detail_kontrak_{$satker}_{$tahun}.pdf";

    return $mode === 'D'
        ? $pdf->output($fileName, 'D') // Download
        : $pdf->output($fileName, 'I'); // Inline/View
}

public function kontrakNonTender(Request $request)
{
    $tahun = $request->get('tahun_anggaran', date('Y'));
    $filterSatker = $request->get('nama_satker', '');

    // List tahun: 1 tahun sebelumnya + tahun sekarang
    $tahunList = range(date('Y') - 1, date('Y'));

    // Ambil semua Satker dari struktur anggaran dan non-tender selesai
    $allSatker = DB::table('struktur_anggarans')
        ->select('nama_satker')
        ->where('tahun_anggaran', $tahun)
        ->union(
            DB::table('non_tender_selesai')
                ->select('nama_satker')
                ->where('tahun_anggaran', $tahun)
        )
        ->orderBy('nama_satker')
        ->pluck('nama_satker')
        ->unique()
        ->toArray();

    // Jika filter kosong, ambil semua Satker
    $targetSatker = empty($filterSatker) ? $allSatker : [$filterSatker];

    // Jumlah paket non-tender selesai per satker
    $paketSelesai = DB::table('non_tender_selesai')
        ->select('nama_satker', DB::raw('count(*) as total_non_tender_selesai'))
        ->where('tahun_anggaran', $tahun)
        ->whereIn('nama_satker', $targetSatker)
        ->groupBy('nama_satker')
        ->pluck('total_non_tender_selesai', 'nama_satker');

    // Total pagu per satker
    $paguPerSatker = DB::table('non_tender_pengumuman')
        ->select('nama_satker', DB::raw('sum(pagu) as total_pagu'))
        ->where('tahun_anggaran', $tahun)
        ->whereIn('nama_satker', $targetSatker)
        ->groupBy('nama_satker')
        ->pluck('total_pagu', 'nama_satker');

    // Jumlah kontrak non-tender per satker
    $kontrakPerSatker = DB::table('non_tender_contract')
        ->select('nama_satker', DB::raw('count(*) as total_kontrak'))
        ->where('tahun_anggaran', $tahun)
        ->whereIn('nama_satker', $targetSatker)
        ->groupBy('nama_satker')
        ->pluck('total_kontrak', 'nama_satker');

    // Susun data per satker
    $result = [];
    foreach ($targetSatker as $namaSatker) {
        $totalSelesai = $paketSelesai[$namaSatker] ?? 0;
        $totalPagu = $paguPerSatker[$namaSatker] ?? 0;
        $jumlahKontrak = $kontrakPerSatker[$namaSatker] ?? 0;

        $result[] = [
            'nama_satker' => $namaSatker,
            'total_non_tender_selesai' => $totalSelesai,
            'total_pagu' => $totalPagu,
            'kontrak_belum_input' => $totalSelesai - $jumlahKontrak,
        ];
    }

    // Hitung total agregat
    $totals = [
        'total_non_tender_selesai' => array_sum(array_column($result, 'total_non_tender_selesai')),
        'total_pagu' => array_sum(array_column($result, 'total_pagu')),
        'kontrak_belum_input' => array_sum(array_column($result, 'kontrak_belum_input')),
    ];

    // Total keseluruhan (ringkasan global)
    $totalNonTenderSelesai = DB::table('non_tender_selesai')
        ->where('tahun_anggaran', $tahun)
        ->count();

    $totalKontrak = DB::table('non_tender_contract')
        ->where('tahun_anggaran', $tahun)
        ->count();

    $selisih = $totalNonTenderSelesai - $totalKontrak;

    // Pilih view berdasarkan role
$view = auth()->user()->role_id == 2
? 'users.monitoring.non-tender'
: 'monitoring.non-tender';


    // Kirim ke view
    return view($view, [
        'data' => $result,
        'tahun' => $tahun,
        'tahunList' => $tahunList,
        'satkerList' => $allSatker,
        'namaSatkerList' => $allSatker,
        'filterSatker' => $filterSatker,
        'totals' => $totals,
        'totalNonTenderSelesai' => $totalNonTenderSelesai,
        'totalKontrak' => $totalKontrak,
        'selisih' => $selisih,
    ]);
}
public function kontrakNonTenderDetail($satker, Request $request)
{
    $tahun = $request->get('tahun', date('Y'));
    $satker = urldecode($satker);

    $data = DB::table('non_tender_selesai as nts')
        ->join('non_tender_pengumuman as ntp', 'nts.kd_nontender', '=', 'ntp.kd_nontender')
        ->leftJoin('non_tender_contract as nk', function ($join) {
            $join->on('nts.kd_nontender', '=', 'nk.kd_nontender')
                 ->on('nts.tahun_anggaran', '=', 'nk.tahun_anggaran');
        })
        ->select('ntp.kd_nontender', 'ntp.nama_paket', 'ntp.pagu', 'ntp.nama_satker')
        ->where('nts.tahun_anggaran', $tahun)
        ->where('nts.nama_satker', $satker)
        ->whereNull('nk.kd_nontender') // hanya yang belum input kontrak
        ->orderBy('ntp.nama_paket')
        ->get();

    $totalPagu = $data->sum('pagu');

    $view = auth()->user()->role_id == 2
        ? 'users.monitoring.non-tender-detail'
        : 'monitoring.non-tender-detail';

    return view($view, compact('data', 'satker', 'tahun', 'totalPagu'));
}
public function exportNonTenderDetailPdf($satker, Request $request)
{
    $tahun = $request->get('tahun_anggaran', date('Y'));
    $satker = urldecode($satker);
    $mode = $request->get('mode', 'V'); // V = View (stream), D = Download

    // Ambil semua kd_nontender yang selesai
    $nonTenderSelesai = DB::table('non_tender_selesai')
        ->where('tahun_anggaran', $tahun)
        ->where('nama_satker', $satker)
        ->pluck('kd_nontender')
        ->toArray();

    if (empty($nonTenderSelesai)) {
        $data = collect();
        $totalPagu = 0;
    } else {
        // Ambil semua yang sudah dikontrak
        $kontrak = DB::table('non_tender_contract')
            ->where('tahun_anggaran', $tahun)
            ->where('nama_satker', $satker)
            ->whereIn('kd_nontender', $nonTenderSelesai)
            ->pluck('kd_nontender')
            ->toArray();

        // Ambil paket selesai tapi belum kontrak
        $data = DB::table('non_tender_pengumuman')
            ->select('kd_nontender', 'nama_paket', 'pagu', 'nama_satker')
            ->where('tahun_anggaran', $tahun)
            ->where('nama_satker', $satker)
            ->whereIn('kd_nontender', $nonTenderSelesai)
            ->whereNotIn('kd_nontender', $kontrak)
            ->orderBy('nama_paket')
            ->get();

        $totalPagu = $data->sum('pagu');
    }

    // Generate PDF
    $pdf = new Html2Pdf('L', 'A3', 'en', true, 'UTF-8', [5, 5, 5, 5]);
    $pdf->writeHTML(view('monitoring.non-tender-detail-pdf', compact('data', 'satker', 'tahun', 'totalPagu'))->render());
    $fileName = "detail_non_tender_{$satker}_{$tahun}.pdf";

    return $mode === 'D'
        ? $pdf->output($fileName, 'D')
        : $pdf->output($fileName, 'I');
}

public function summaryRealisasi(Request $request)
{
    // Ambil data satker + transaksi pakai presentaseRealisasi yang sudah ada
    $data = $this->presentaseRealisasi($request);

    // Hitung total belanja dan transaksi
    $totalBelanja = $data->sum('belanja_pengadaan');
    $totalTransaksi = $data->sum('total_transaksi');
    $avgPresentase = $data->count() > 0 ? round($data->avg('presentase_realisasi'), 2) : 0;

    return [
        'total_belanja' => $totalBelanja,
        'total_transaksi' => $totalTransaksi,
        'avg_presentase' => $avgPresentase,
    ];
}

}    
