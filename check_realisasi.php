<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$satkerName = 'Biro Pengadaan Barang dan Jasa';
$satker = DB::table('satkers')->where('nama_satker', 'LIKE', "%$satkerName%")->first();
$year = 2026;

if ($satker) {
    $kdSatker = $satker->kd_satker;
    
    $nilaiTender = DB::table('tender_pengumuman_data')
        ->select('kd_tender', 'pagu')
        ->where('kd_satker', $kdSatker)->where('tahun', $year)
        ->groupBy('kd_tender', 'pagu')->get()->sum('pagu');

    $nilaiNonTender = DB::table('non_tender_pengumuman')
        ->select('kd_nontender', 'pagu')
        ->where('kd_satker', $kdSatker)->where('tahun_anggaran', $year)
        ->groupBy('kd_nontender', 'pagu')->get()->sum('pagu');

    $nilaiV5 = DB::table('ekatalog_v5_pakets as v5')
        ->join('satkers as s', 'v5.satker_id', '=', 's.kd_satker')
        ->where('s.kd_satker', $kdSatker)->where('v5.tahun_anggaran', $year)
        ->sum('v5.total_harga');

    $nilaiV6 = DB::table('ekatalog_v6_pakets')
        ->where('nama_satker', $satker->nama_satker)->where('tahun_anggaran', $year)
        ->sum('total_harga');

    $nilaiToko = DB::table('toko_darings')
        ->where('kd_satker', $kdSatker)->where('tahun', $year)
        ->sum('valuasi');

    $nilaiSwakelola = DB::table('swakelola_realisasi')
        ->where('kd_satker', $kdSatker)->where('tahun_anggaran', $year)
        ->sum('nilai_realisasi');

    echo "Satker: " . $satker->nama_satker . " ($kdSatker) Year $year\n";
    echo "1. Nilai Tender: " . number_format($nilaiTender, 0, ',', '.') . "\n";
    echo "2. Nilai Non-Tender: " . number_format($nilaiNonTender, 0, ',', '.') . "\n";
    echo "3. Nilai E-Kat V5: " . number_format($nilaiV5, 0, ',', '.') . "\n";
    echo "4. Nilai E-Kat V6: " . number_format($nilaiV6, 0, ',', '.') . "\n";
    echo "5. Nilai Toko Daring: " . number_format($nilaiToko, 0, ',', '.') . "\n";
    echo "6. Nilai Swakelola: " . number_format($nilaiSwakelola, 0, ',', '.') . "\n";
    echo "-----------------------------------\n";
    echo "Total Realisasi: " . number_format($nilaiTender + $nilaiNonTender + $nilaiV5 + $nilaiV6 + $nilaiToko + $nilaiSwakelola, 0, ',', '.') . "\n";
}
