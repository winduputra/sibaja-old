<?php

namespace App\Http\Controllers;

use App\Services\KatalogBulananReportService;
use App\Services\EKontrakTenderBulananReportService;
use App\Services\LaporanBulananWorkbookService;
use App\Services\PencatatanNonTenderBulananReportService;
use App\Services\PencatatanSwakelolaBulananReportService;
use App\Services\RupBulananReportService;
use Illuminate\Http\Request;

class LaporanBulananController extends Controller
{
    public function show(
        Request $request,
        string $sheet,
        LaporanBulananWorkbookService $workbookService,
        KatalogBulananReportService $katalogService,
        EKontrakTenderBulananReportService $eKontrakTenderService,
        PencatatanNonTenderBulananReportService $pencatatanNonTenderService,
        PencatatanSwakelolaBulananReportService $pencatatanSwakelolaService,
        RupBulananReportService $rupService
    ) {
        $validated = $request->validate([
            'tahun' => ['nullable', 'regex:/^\d{4}$/', 'integer', 'between:2000,2099'],
            'bulan' => ['nullable', 'in:' . implode(',', LaporanBulananWorkbookService::months())],
        ]);

        $tahun = isset($validated['tahun']) ? (int) $validated['tahun'] : 2026;
        $bulan = isset($validated['bulan']) ? $validated['bulan'] : 'Agustus';

        $isDynamicRup = $sheet === 'rup';
        $isDynamicKatalog = $sheet === 'katalog';
        $isDynamicEKontrak = $sheet === 'e-kontrak';
        $isDynamicPencatatanNonTender = $sheet === 'pencatatan-non-tender';
        $isDynamicPencatatanSwakelola = $sheet === 'pencatatan-swakelola';

        if ($isDynamicRup) {
            $report = $rupService->report($tahun, $bulan);
        } elseif ($isDynamicKatalog) {
            $report = $katalogService->report($tahun, $bulan);
        } elseif ($isDynamicEKontrak) {
            $report = $eKontrakTenderService->report($tahun, $bulan);
        } elseif ($isDynamicPencatatanNonTender) {
            $report = $pencatatanNonTenderService->report($tahun, $bulan);
        } elseif ($isDynamicPencatatanSwakelola) {
            $report = $pencatatanSwakelolaService->report($tahun, $bulan);
        } else {
            $report = $workbookService->project($sheet, $tahun, $bulan);
        }

        return view('laporan-bulanan.show', [
            'report' => $report,
            'isDynamicRup' => $isDynamicRup,
            'isDynamicKatalog' => $isDynamicKatalog,
            'isDynamicEKontrak' => $isDynamicEKontrak,
            'isDynamicPencatatanNonTender' => $isDynamicPencatatanNonTender,
            'isDynamicPencatatanSwakelola' => $isDynamicPencatatanSwakelola,
            'filters' => [
                'tahun' => $tahun,
                'bulan' => $bulan,
                'months' => LaporanBulananWorkbookService::months(),
            ],
        ]);
    }
}
