<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$name = 'Biro Pengadaan Barang dan Jasa';
$year = 2026;

$tender = DB::table('tender_pengumuman_data')->where('nama_satker', $name)->where('tahun', $year)->sum('pagu');
$nt = DB::table('non_tender_pengumuman')->where('nama_satker', $name)->where('tahun_anggaran', $year)->sum('pagu');
$v6 = DB::table('ekatalog_v6_pakets')->where('nama_satker', $name)->where('tahun_anggaran', $year)->sum('total_harga');
$swakelola = DB::table('swakelola_realisasi')->where('nama_satker', $name)->where('tahun_anggaran', $year)->sum('nilai_realisasi');

$total = $tender + $nt + $v6 + $swakelola;

echo "Total By Name: " . number_format($total, 2, '.', '') . "\n";
echo "Breakdown:\n";
echo "- Tender: $tender\n";
echo "- Non-Tender: $nt\n";
echo "- E-Kat V6: $v6\n";
echo "- Swakelola: $swakelola\n";
