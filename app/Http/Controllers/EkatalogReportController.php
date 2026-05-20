<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EkatalogV5Paket;
use App\Models\EkatalogV6Paket;
use App\Models\Satker;  // Menggunakan model Satker
use Spipu\Html2Pdf\Html2Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EkatalogReportController extends Controller
{
    public function index(Request $request)
    {
        $tahun  = $request->input('tahun', 2026);
        $versi  = $request->input('versi', 'V6');
        $satker = $request->input('satker', 'Semua');
        $status = $request->input('status', 'Semua');

        $daftarSatker = $this->getEkatalogSatkerMap($tahun, $versi);

        $data = $this->collectEkatalogData($tahun, $versi, $status, $daftarSatker);

        // Filter satker jika dipilih
        if ($satker !== 'Semua') {
            $data = $data->filter(fn($d) => $d['nama_satker'] === $satker)->values();
        }

        $totalPaket = $data->count();
        $totalNilai = $data->sum('nilai_kontrak');

        // Dropdown satkerList
        $satkerList = $this->getSatkerList($tahun, $versi);

        // Fetch available years from both V5 and V6 tables
        $tahunTersedia = DB::table('ekatalog_v5_pakets')
            ->select(DB::raw('tahun_anggaran as tahun'))
            ->distinct()
            ->union(
                DB::table('ekatalog_v6_pakets')
                    ->select(DB::raw('tahun_anggaran as tahun'))
                    ->distinct()
            )
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');
        if (auth()->user()->role_id == 2) {
            return view('users.E-purchasing.ekatalog', compact(
                'data', 'tahun', 'versi', 'satker', 'status',
                'totalPaket', 'totalNilai', 'tahunTersedia', 'satkerList'
            ));
        }

        return view('E-purchasing.ekatalog', compact(
            'data', 'tahun', 'versi', 'satker', 'status',
            'totalPaket', 'totalNilai', 'tahunTersedia', 'satkerList'
        ));
    }

    public function exportPdf(Request $request)
    {
        $tahun  = $request->input('tahun', date('Y'));
        $versi  = $request->input('versi', 'V5');
        $satker = $request->input('satker', 'Semua');
        $status = $request->input('status', 'Semua');
        $mode   = $request->input('mode', 'I');

        $daftarSatker = $this->getEkatalogSatkerMap($tahun, $versi);

        $data = $this->collectEkatalogData($tahun, $versi, $status, $daftarSatker);

        // Filter Satker jika dipilih
        if ($satker !== 'Semua') {
            $data = $data->filter(fn($d) => $d['nama_satker'] === $satker)->values();
        }

        $dataRekap = $data->groupBy('nama_satker')
            ->map(fn($items) => [
                'total_transaksi' => $items->count(),
                'nilai_transaksi' => $items->sum('nilai_kontrak'),
            ])
            ->sortKeys()
            ->toArray();

        $tanggal = $tahun == 2024
            ? '31 Desember 2024'
            : Carbon::now()->locale('id')->translatedFormat('d F Y');

        $html = view('E-purchasing.ekatalog-pdf', [
            'data' => $dataRekap,
            'tanggal' => $tanggal,
            'tahun' => $tahun,
            'versi' => $versi,
            'satker' => $satker,
            'status' => $status,
        ])->render();

        $html = '<style>
            body { font-family: sans-serif; font-size: 11px; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 6px; text-align: left; }
            th { background-color: #eee; }
        </style>' . $html;

        $pdf = new Html2Pdf('P', 'A4', 'fr');
        $pdf->writeHTML($html);
        return $pdf->output("laporan-ekatalog-{$tahun}-{$versi}.pdf", $mode);
    }

    private function collectEkatalogData($tahun, $versi, $status, $daftarSatker)
    {
        $data = collect();

        if ($versi === 'V5' || $versi === 'Semua') {
            $data = $data->concat($this->collectEkatalogDataByVersion('V5', $tahun, $status, $daftarSatker));
        }

        if ($versi === 'V6' || $versi === 'Semua') {
            $data = $data->concat($this->collectEkatalogDataByVersion('V6', $tahun, $status, $daftarSatker));
        }

        return $data->values();
    }

    private function collectEkatalogDataByVersion($version, $tahun, $status, $daftarSatker)
    {
        $model = $version === 'V6' ? new EkatalogV6Paket() : new EkatalogV5Paket();
        $query = $model->newQuery()->where('tahun_anggaran', $tahun);

        if ($version === 'V6') {
            $query->whereNotNull('nama_satker');
        }

        $this->applyStatusFilter($query, $version, $status);

        return $query->get()
            ->groupBy('kd_paket')
            ->map(fn($items) => $this->normalizeEkatalogGroup($items, $version, $daftarSatker))
            ->values();
    }

    private function applyStatusFilter($query, $version, $status)
    {
        if ($status === 'Semua') {
            return;
        }

        if ($version === 'V5') {
            $query->where('paket_status_str', 'Paket ' . $status);
            return;
        }

        if (strtoupper($status) === 'PROSES') {
            $query->where('status_pkt', 'ON_PROCESS');
        } elseif (in_array(strtoupper($status), ['SELESAI', 'COMPLETED'])) {
            $query->whereIn('status_pkt', ['COMPLETED', 'PAYMENT_OUTSIDE_SYSTEM']);
        }
    }

    private function normalizeEkatalogGroup($items, $version, $daftarSatker)
    {
        $item = $items->first();
        $isV6 = $version === 'V6';

        $namaSatker = $isV6
            ? $item->nama_satker
            : ($item->nama_satker ?: ($daftarSatker[$item->satker_id] ?? '-'));

        return [
            'id_rup'        => $isV6 ? ($item->rup_code ?? '-') : ($item->kd_rup ?? '-'),
            'nama_satker'   => $namaSatker,
            'nama_paket'    => $isV6 ? ($item->rup_name ?? '-') : ($item->nama_paket ?? '-'),
            'status'        => $this->getStatusLabel($isV6 ? $item->status_pkt : $item->paket_status_str, $version),
            'nilai_kontrak' => $items->sum('total_harga'),
        ];
    }

    private function getStatusLabel($status, $version)
    {
        if ($version === 'V5') {
            return $status;
        }

        if (strtoupper($status) === 'ON_PROCESS') {
            return 'Paket Proses';
        }

        if (in_array(strtoupper($status), ['COMPLETED', 'PAYMENT_OUTSIDE_SYSTEM'])) {
            return 'Paket Selesai';
        }

        return $status;
    }

    private function getSatkerList($tahun, $versi)
    {
        $satkerList = Satker::where('tahun_anggaran', $tahun)
            ->where('kd_satker', '<>', '350504')
            ->pluck('nama_satker');

        if ($versi === 'V5' || $versi === 'Semua') {
            $satkerList = $satkerList->concat(
                EkatalogV5Paket::where('tahun_anggaran', $tahun)
                    ->whereNotNull('nama_satker')
                    ->distinct()
                    ->pluck('nama_satker')
            );
        }

        if ($versi === 'V6' || $versi === 'Semua') {
            $satkerList = $satkerList->concat(
                EkatalogV6Paket::where('tahun_anggaran', $tahun)
                    ->whereNotNull('nama_satker')
                    ->distinct()
                    ->pluck('nama_satker')
            );
        }

        return $satkerList->filter()->unique()->sort()->values();
    }

    private function getEkatalogSatkerMap($tahun, $versi)
    {
        $satkers = Satker::where('tahun_anggaran', $tahun)
            ->where('kd_satker', '<>', '350504')
            ->pluck('nama_satker', 'kd_satker');

        if ($versi === 'V5' || $versi === 'Semua') {
            $v5Satkers = EkatalogV5Paket::where('tahun_anggaran', $tahun)
                ->whereNotNull('satker_id')
                ->whereNotNull('nama_satker')
                ->pluck('nama_satker', 'satker_id');

            $satkers = $satkers->merge($v5Satkers);
        }

        return $satkers;
    }
}
