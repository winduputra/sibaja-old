<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Swakelola;

class SwakelolaUpdate extends Command
{
    protected $signature = 'update:swakelola';
    protected $description = 'Ambil data Swakelola tahun 2024 dan 2025 dari API SiBAJA dan simpan ke database';

    public function handle()
    {
        $lpse = 'D264';
        $tahunList = ['2024', '2025', '2026'];

        $syncStartTime = now();

        foreach ($tahunList as $tahun) {
            $url = "https://isb.lkpp.go.id/isb-2/api/c8c88c34-d2f9-4250-8381-246c1180ec55/json/9600/RUP-PaketSwakelola-Terumumkan/tipe/4:12/parameter/{$tahun}:{$lpse}";

            $this->info("Mengambil data Swakelola tahun $tahun dari:");
            $this->line($url);

            $response = Http::get($url);

            if (!$response->ok()) {
                $this->error("Gagal mengambil data dari API untuk tahun $tahun. Status: " . $response->status());
                continue;
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                $this->warn("Data tahun $tahun kosong atau format tidak sesuai.");
                continue;
            }

            $this->info("Menyimpan " . count($data) . " data Swakelola tahun $tahun ke database...");

            foreach ($data as $item) {
                if (($item['kd_klpd'] ?? '') !== 'D264') {
                    continue;
                }

                $cleanDate = function($date) {
                    if (empty($date) || strpos($date, '-0001') !== false || strpos($date, '0000-00-00') !== false) {
                        return null;
                    }
                    return $date;
                };

                Swakelola::updateOrCreate(
                    ['kd_rup' => $item['kd_rup'], 'tahun_anggaran' => $tahun], 
                    [
                        'tahun_anggaran' => $tahun,
                        'kd_klpd' => $item['kd_klpd'],
                        'nama_klpd' => $item['nama_klpd'],
                        'jenis_klpd' => $item['jenis_klpd'],
                        'kd_satker' => $item['kd_satker'],
                        'kd_satker_str' => $item['kd_satker_str'],
                        'nama_satker' => $item['nama_satker'],
                        'nama_paket' => $item['nama_paket'],
                        'pagu' => $item['pagu'],
                        'tipe_swakelola' => $item['tipe_swakelola'],
                        'volume_pekerjaan' => $item['volume_pekerjaan'],
                        'uraian_pekerjaan' => $item['uraian_pekerjaan'],
                        'tgl_awal_pelaksanaan_kontrak' => $cleanDate($item['tgl_awal_pelaksanaan_kontrak'] ?? null),
                        'tgl_akhir_pelaksanaan_kontrak' => $cleanDate($item['tgl_akhir_pelaksanaan_kontrak'] ?? null),
                        'tgl_buat_paket' => $cleanDate($item['tgl_buat_paket'] ?? null),
                        'tgl_pengumuman_paket' => $cleanDate($item['tgl_pengumuman_paket'] ?? null),
                        'nip_ppk' => $item['nip_ppk'],
                        'nama_ppk' => $item['nama_ppk'],
                        'status_aktif_rup' => $item['status_aktif_rup'],
                        'status_delete_rup' => $item['status_delete_rup'],
                        'status_umumkan_rup' => $item['status_umumkan_rup'],
                        'last_synced_at' => now(),
                    ]
                );
            }

            // Cleanup
            $this->line("> Cleaning up stale Swakelola data for year $tahun...");
            $deleted = Swakelola::where('tahun_anggaran', $tahun)
                ->where('kd_klpd', 'D264')
                ->where(function ($query) use ($syncStartTime) {
                    $query->where('last_synced_at', '<', $syncStartTime)
                          ->orWhereNull('last_synced_at');
                })
                ->delete();

            if ($deleted > 0) {
                $this->warn("  Deleted $deleted stale paket swakelola.");
            }

            $this->info("Selesai menyimpan data Swakelola tahun $tahun.");
        }

        return 0;
    }
}
