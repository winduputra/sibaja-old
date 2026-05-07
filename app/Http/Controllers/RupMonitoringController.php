<?php

namespace App\Http\Controllers;

use App\Models\Penyedia;
use App\Models\Swakelola;
use App\Models\Tender;
use App\Models\SwakelolaRealisasi;
use App\Models\NonTender;
use App\Models\TenderPengumumanData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spipu\Html2Pdf\Html2Pdf;
use Carbon\Carbon;

class RupMonitoringController extends Controller
{
    /**
     * Display RUP Monitoring home page with tabs
     */
    public function index(Request $request)
    {
        $year = $request->get('tahun', date('Y'));
        $tab = $request->get('tab', 'penyedia');
        $metodeFilter = $request->get('metode', '');
        $konsolidasiFilter = $request->get('konsolidasi', '');

        // Get available years
        $availableYears = $this->getAvailableYears();

        // Get available procurement methods for filter dropdown
        $metodePengadaanPenyedia = collect();
        $metodePengadaanSwakelola = collect();

        if ($tab === 'penyedia' || $tab === 'all') {
            $metodePengadaanPenyedia = Penyedia::where('tahun_anggaran', $year)
                ->where('kd_klpd', 'D264')
                ->where('kd_satker', '!=', '350504')
                ->select('metode_pengadaan')
                ->distinct()
                ->pluck('metode_pengadaan')
                ->filter();
        }

        if ($tab === 'swakelola' || $tab === 'all') {
            $metodePengadaanSwakelola = Swakelola::where('tahun_anggaran', $year)
                ->where('kd_klpd', 'D264')
                ->where('kd_satker', '!=', '350504')
                ->select('tipe_swakelola')
                ->distinct()
                ->pluck('tipe_swakelola')
                ->filter();
        }

        // Get RUP data based on tab selection
        $data = $this->getRupMonitoringData($year, $tab, $metodeFilter, $konsolidasiFilter);

        $view = (auth()->user()->role_id == 2)
            ? 'users.monitoring.rup-monitoring'
            : 'monitoring.rup-monitoring';

        return view($view, compact(
            'data',
            'year',
            'tab',
            'metodeFilter',
            'konsolidasiFilter',
            'availableYears',
            'metodePengadaanPenyedia',
            'metodePengadaanSwakelola'
        ));
    }

    /**
     * Get detail of RUP by Satker (displayed in modal/collapse)
     */
    public function detail(Request $request)
    {
        $satkerStr = $request->get('kd_satker_str');
        $year = $request->get('tahun', date('Y'));
        $tab = $request->get('tab', 'penyedia');
        $metodeFilter = $request->get('metode', '');
        $konsolidasiFilter = $request->get('konsolidasi', '');

        $data = $this->getRupMonitoringData($year, $tab, $metodeFilter, $konsolidasiFilter);

        $detailData = $data->where('kd_satker_str', $satkerStr)->first();

        if (!$detailData) {
            return response()->json(['error' => 'Data not found'], 404);
        }

        return response()->json($detailData);
    }

    /**
     * Export RUP Monitoring to PDF
     */
    public function exportPdf(Request $request)
    {
        $year = $request->get('tahun', date('Y'));
        $tab = $request->get('tab', 'penyedia');
        $metodeFilter = $request->get('metode', '');
        $konsolidasiFilter = $request->get('konsolidasi', '');

        if (!$metodeFilter) {
            return redirect()->back()->with('error', 'Silakan pilih metode pengadaan terlebih dahulu');
        }

        $data = $this->getRupMonitoringData($year, $tab, $metodeFilter, $konsolidasiFilter);

        // Generate PDF
        $html = view('monitoring.rup-monitoring-pdf', compact('data', 'year', 'tab', 'metodeFilter', 'konsolidasiFilter'))->render();
        
        try {
            $pdf = new Html2Pdf('P', 'A4', 'en');
            $pdf->writeHTML($html);
            
            $filename = "RUP_Monitoring_{$tab}_{$metodeFilter}_{$year}_" . date('YmdHis') . ".pdf";
            $pdf->output($filename, 'D');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get RUP Monitoring Data based on filters
     */
    private function getRupMonitoringData($year, $tab = 'penyedia', $metodeFilter = '', $konsolidasiFilter = '')
    {
        if ($tab === 'penyedia') {
            return $this->getPenyediaMonitoringData($year, $metodeFilter, $konsolidasiFilter);
        } elseif ($tab === 'swakelola') {
            return $this->getSwakelolaMonitoringData($year, $metodeFilter);
        }

        return collect();
    }

    /**
     * Get Penyedia RUP Monitoring Data - Tidak yang sudah terealisasi
     */
    private function getPenyediaMonitoringData($year, $metodeFilter = '', $konsolidasiFilter = '')
    {
        // Get all Penyedia RUP
        $query = Penyedia::where('tahun_anggaran', $year)
            ->where('kd_klpd', 'D264')
            ->where('kd_satker', '!=', '350504')
            ->where('status_umumkan_rup', 'Terumumkan')
            ->where('status_aktif_rup', 1);

        if ($metodeFilter) {
            $query->where('metode_pengadaan', $metodeFilter);
        }

        if ($konsolidasiFilter && $konsolidasiFilter !== 'all') {
            $query->where('status_konsolidasi', $konsolidasiFilter);
        }

        $penyedia = $query->get();

        // 1. Get realized from Tender (Field: kd_rup)
        $tenderRup = DB::table('tender_pengumuman_data')
            ->where('tahun', $year)
            ->whereNotNull('kd_rup')
            ->where('kd_rup', '!=', '')
            ->distinct()->pluck('kd_rup')->toArray();

        // 2. Get realized from Non-Tender (Field: kd_rup)
        $nonTenderRup = DB::table('non_tender_pengumuman')
            ->where('tahun_anggaran', $year)
            ->whereNotNull('kd_rup')
            ->where('kd_rup', '!=', '')
            ->distinct()->pluck('kd_rup')->toArray();

        // 3. Get realized from E-Katalog V6 (Field: rup_code - as requested)
        $ekatV6Rup = DB::table('ekatalog_v6_pakets')
            ->where('tahun_anggaran', $year)
            ->whereNotNull('rup_code')
            ->where('rup_code', '!=', '')
            ->distinct()->pluck('rup_code')->toArray();

        // 4. Get realized from E-Katalog V5 (Fallback, field: kd_rup)
        $ekatV5Rup = Schema::hasTable('ekatalog_v5_pakets') 
            ? DB::table('ekatalog_v5_pakets')
                ->where('tahun_anggaran', $year)
                ->whereNotNull('kd_rup')
                ->where('kd_rup', '!=', '')
                ->distinct()->pluck('kd_rup')->toArray()
            : [];

        // Merge all realized codes
        $realiziedRupCodes = collect($tenderRup)
            ->merge($nonTenderRup)
            ->merge($ekatV6Rup)
            ->merge($ekatV5Rup)
            ->map(fn($val) => trim($val))
            ->unique()
            ->toArray();

        // Filter Penyedia yang belum terealisasi
        $unrealizedPenyedia = $penyedia->filter(function ($item) use ($realiziedRupCodes) {
            $kdRup = trim($item->kd_rup);
            return !in_array($kdRup, $realiziedRupCodes);
        });

        // Group by Satker
        $groupedData = $unrealizedPenyedia->groupBy('nama_satker')->map(function ($items, $satkerName) {
            return [
                'nama_satker' => $satkerName,
                'kd_satker_str' => $items->first()->kd_satker_str ?? '',
                'total_pagu' => $items->sum('pagu'),
                'total_paket' => $items->count(),
                'metode_pengadaan' => $items->pluck('metode_pengadaan')->unique()->join(', '),
                'rupList' => $items->map(function ($item, $index) {
                    return [
                        'no' => $index + 1,
                        'kd_rup' => $item->kd_rup,
                        'nama_paket' => $item->nama_paket,
                        'pagu' => $item->pagu,
                        'metode_pengadaan' => $item->metode_pengadaan,
                        'jenis_pengadaan' => $item->jenis_pengadaan ?? '-',
                        'status_konsolidasi' => $item->status_konsolidasi,
                        'status' => 'Belum Terealisasi',
                    ];
                })->values(),
            ];
        })->values();

        return $groupedData;
    }

    /**
     * Get Swakelola RUP Monitoring Data - Yang belum terealisasi
     */
    private function getSwakelolaMonitoringData($year, $metodeFilter = '')
    {
        // Get all Swakelola RUP
        $query = Swakelola::where('tahun_anggaran', $year)
            ->where('kd_klpd', 'D264')
            ->where('kd_satker', '!=', '350504')
            ->where('status_umumkan_rup', 'Terumumkan')
            ->where('status_aktif_rup', 1);

        if ($metodeFilter) {
            $query->where('tipe_swakelola', $metodeFilter);
        }

        $swakelola = $query->get();

        // Get kd_rup yang sudah terealisasi
        $realizedRupCodes = DB::table('swakelola_realisasi')
            ->where('tahun_anggaran', $year)
            ->where('kd_klpd', 'D264')
            ->whereNotNull('kd_swakelola_pct')
            ->where('kd_swakelola_pct', '!=', '')
            ->distinct()
            ->pluck('kd_swakelola_pct')
            ->map(fn($val) => trim($val))
            ->toArray();

        // Filter Swakelola yang belum terealisasi
        $unrealizedSwakelola = $swakelola->filter(function ($item) use ($realizedRupCodes) {
            $kdRup = trim($item->kd_rup);
            return !in_array($kdRup, $realizedRupCodes);
        });

        // Group by Satker
        $groupedData = $unrealizedSwakelola->groupBy('nama_satker')->map(function ($items, $satkerName) {
            return [
                'nama_satker' => $satkerName,
                'kd_satker_str' => $items->first()->kd_satker_str ?? '',
                'total_pagu' => $items->sum('pagu'),
                'total_paket' => $items->count(),
                'tipe_swakelola' => $items->pluck('tipe_swakelola')->unique()->join(', '),
                'rupList' => $items->map(function ($item, $index) {
                    return [
                        'no' => $index + 1,
                        'kd_rup' => $item->kd_rup,
                        'nama_paket' => $item->nama_paket,
                        'pagu' => $item->pagu,
                        'tipe_swakelola' => $item->tipe_swakelola,
                        'uraian_pekerjaan' => $item->uraian_pekerjaan ?? '-',
                        'status' => 'Belum Terealisasi',
                    ];
                })->values(),
            ];
        })->values();

        return $groupedData;
    }

    /**
     * Get available years from database
     */
    private function getAvailableYears()
    {
        $penyediaYears = Penyedia::where('kd_klpd', 'D264')
            ->distinct()
            ->pluck('tahun_anggaran')
            ->map(fn($y) => (int)$y)
            ->toArray();

        $swakelolaYears = Swakelola::where('kd_klpd', 'D264')
            ->distinct()
            ->pluck('tahun_anggaran')
            ->map(fn($y) => (int)$y)
            ->toArray();

        $years = array_unique(array_merge($penyediaYears, $swakelolaYears));
        rsort($years);

        return $years;
    }
}
