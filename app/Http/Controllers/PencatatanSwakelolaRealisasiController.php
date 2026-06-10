<?php

namespace App\Http\Controllers;

use App\Services\PencatatanSwakelolaRealisasiService;
use Illuminate\Http\Request;

class PencatatanSwakelolaRealisasiController extends Controller
{
    public function index(Request $request, PencatatanSwakelolaRealisasiService $service)
    {
        $tahun = (int) $request->query('tahun', config('api.inaproc.default_tahun', date('Y')));
        $report = $service->report($tahun);

        return view('realisasi.pencatatan-swakelola', [
            'title' => 'Evaluasi Data Pencatatan Swakelola',
            'tahun' => $tahun,
            'tahunOptions' => $service->availableYears($tahun),
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'apiError' => $report['apiError'],
        ]);
    }
}
