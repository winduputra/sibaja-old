<?php

namespace App\Http\Controllers;

use App\Services\PencatatanNonTenderRealisasiService;
use Illuminate\Http\Request;

class PencatatanNonTenderRealisasiController extends Controller
{
    public function index(Request $request, PencatatanNonTenderRealisasiService $service)
    {
        $tahun = (int) $request->query('tahun', config('api.inaproc.default_tahun', date('Y')));
        $report = $service->report($tahun);

        return view('realisasi.pencatatan-non-tender', [
            'title' => 'Evaluasi Data Pencatatan Non Tender',
            'tahun' => $tahun,
            'tahunOptions' => $service->availableYears($tahun),
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'apiError' => $report['apiError'],
        ]);
    }
}
