<?php

namespace App\Transformers;

/**
 * Transform RUP (Rencana Umum Pengadaan) data from INAPROC API to internal format
 */
class RupTransformer
{
    /**
     * Transform master satker data
     */
    public static function masterSatker(array $item): array
    {
        return [
            'kd_satker' => $item['kd_satker'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'tahun_anggaran' => $item['tahun'] ?? date('Y'),
            'tahun_aktif_json' => $item['tahun_aktif'] ?? [], // Array of years [2024, 2025, 2026]
            'kd_satker_str' => $item['kd_satker_str'] ?? null,
            'alamat' => $item['alamat'] ?? null,
            'telepon' => $item['telepon'] ?? null,
            'fax' => $item['fax'] ?? null,
            'kodepos' => $item['kodepos'] ?? null,
            'status_satker' => $item['status_satker'] ?? null,
            'ket_satker' => $item['ket_satker'] ?? null,
            'jenis_satker' => $item['jenis_satker'] ?? null,
            'kd_klpd' => $item['kd_klpd'] ?? 'D264',
            'nama_klpd' => $item['nama_klpd'] ?? null,
            'jenis_klpd' => $item['jenis_klpd'] ?? null,
            'kode_eselon' => $item['kode_eselon'] ?? null,
            'sync_source' => 'inaproc_v1',
            'last_synced_at' => now(),
        ];
    }

    /**
     * Transform paket penyedia data
     */
    public static function paketPenyedia(array $item, $fallbackYear = null): array
    {
        return [
            'kd_rup' => $item['kd_rup'] ?? null,
            'tahun_anggaran' => $item['tahun'] ?? ($item['tahun_anggaran'] ?? ($fallbackYear ?? date('Y'))),
            'kd_klpd' => $item['kd_klpd'] ?? 'D264',
            'nama_klpd' => $item['nama_klpd'] ?? null,
            'kd_satker' => $item['kd_satker'] ?? null,
            'kd_satker_str' => $item['kd_satker_str'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'nama_paket' => $item['nama_paket'] ?? null,
            'jenis_pengadaan' => $item['jenis_pengadaan'] ?? null,
            'metode_pengadaan' => $item['metode_pengadaan'] ?? null,
            'pagu' => self::parseNumber($item['pagu'] ?? 0),
            'tipe_paket' => $item['tipe_paket'] ?? null,
            'urarian_pekerjaan' => $item['uraian_pekerjaan'] ?? null,
            'tgl_pengumuman_paket' => self::parseDateTime($item['tgl_pengumuman_paket'] ?? null),
            'tgl_awal_pemilihan' => self::parseDateTime($item['tgl_awal_pemilihan'] ?? null),
            'tgl_akhir_pemilihan' => self::parseDateTime($item['tgl_akhir_pemilihan'] ?? null),
            'tgl_awal_kontrak' => self::parseDateTime($item['tgl_awal_kontrak'] ?? null),
            'tgl_akhir_kontrak' => self::parseDateTime($item['tgl_akhir_kontrak'] ?? null),
            'status_aktif_rup' => (bool) ($item['status_aktif_rup'] ?? false),
            'status_umumkan_rup' => $item['status_umumkan_rup'] ?? null,
            'status_konsolidasi' => $item['status_konsolidasi'] ?? null,
            'sync_source' => 'inaproc_v1',
            'last_synced_at' => now(),
        ];
    }

    /**
     * Transform paket swakelola data
     */
    public static function paketSwakelola(array $item, $fallbackYear = null): array
    {
        return [
            'kd_rup' => $item['kd_rup'] ?? null,
            'tahun_anggaran' => $item['tahun'] ?? ($item['tahun_anggaran'] ?? ($fallbackYear ?? date('Y'))),
            'kd_klpd' => $item['kd_klpd'] ?? 'D264',
            'nama_klpd' => $item['nama_klpd'] ?? null,
            'jenis_klpd' => $item['jenis_klpd'] ?? null,
            'kd_satker' => $item['kd_satker'] ?? null,
            'kd_satker_str' => $item['kd_satker_str'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'nama_paket' => $item['nama_paket'] ?? null,
            'pagu' => self::parseNumber($item['pagu'] ?? 0),
            'tipe_swakelola' => $item['tipe_swakelola'] ?? null,
            'volume_pekerjaan' => $item['volume_pekerjaan'] ?? null,
            'uraian_pekerjaan' => $item['uraian_pekerjaan'] ?? null,
            'tgl_buat_paket' => self::parseDateTime($item['tgl_buat_paket'] ?? null),
            'tgl_pengumuman_paket' => self::parseDateTime($item['tgl_pengumuman_paket'] ?? null),
            'tgl_awal_pelaksanaan_kontrak' => self::parseDateTime($item['tgl_awal_pelaksanaan_kontrak'] ?? null),
            'tgl_akhir_pelaksanaan_kontrak' => self::parseDateTime($item['tgl_akhir_pelaksanaan_kontrak'] ?? null),
            'nip_ppk' => $item['nip_ppk'] ?? null,
            'nama_ppk' => $item['nama_ppk'] ?? null,
            'status_aktif_rup' => (bool) ($item['status_aktif_rup'] ?? false),
            'status_delete_rup' => (bool) ($item['status_delete_rup'] ?? false),
            'status_umumkan_rup' => $item['status_umumkan_rup'] ?? null,
            'sync_source' => 'inaproc_v1',
            'last_synced_at' => now(),
        ];
    }

    /**
     * Transform RUP kaji ulang history metadata.
     */
    public static function historyKajiUlang(array $item, $fallbackYear = null): array
    {
        return [
            'tahun_anggaran' => $item['tahun_anggaran'] ?? ($item['tahun'] ?? ($fallbackYear ?? date('Y'))),
            'kd_klpd' => $item['kd_klpd'] ?? 'D264',
            'nama_klpd' => $item['nama_klpd'] ?? null,
            'jenis_klpd' => $item['jenis_klpd'] ?? null,
            'jenis_paket' => trim((string) ($item['jenis_paket'] ?? '')),
            'jenis_revisi' => $item['jenis_revisi'] ?? null,
            'kd_rup_baru' => self::parseInteger($item['kd_rup_baru'] ?? null),
            'kd_rup_lama' => self::parseInteger($item['kd_rup_lama'] ?? null),
            'kd_satker' => $item['kd_satker'] ?? null,
            'kd_satker_str' => $item['kd_satker_str'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'tgl_kaji_ulang' => self::parseDateTime($item['tgl_kaji_ulang'] ?? null),
            'alasan_kajiulang' => $item['alasan_kajiulang'] ?? null,
            'last_update_ref' => self::parseDateTime($item['last_update_ref'] ?? null),
            'payload_hash' => self::historyPayloadHash($item, $fallbackYear),
            'raw_payload' => $item,
            'sync_source' => 'inaproc_v1',
            'last_synced_at' => now(),
        ];
    }

    /**
     * Parse number string that might have formatting
     */
    protected static function parseNumber($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (\is_numeric($value)) {
            return (float) $value;
        }

        // Remove thousands separator and replace decimal comma with period
        $value = \str_replace('.', '', $value);
        $value = \str_replace(',', '.', $value);

        return (float) $value ?: 0;
    }

    protected static function parseInteger($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected static function historyPayloadHash(array $item, $fallbackYear = null): string
    {
        $key = [
            'kd_klpd' => $item['kd_klpd'] ?? 'D264',
            'tahun_anggaran' => $item['tahun_anggaran'] ?? ($item['tahun'] ?? ($fallbackYear ?? date('Y'))),
            'jenis_paket' => trim((string) ($item['jenis_paket'] ?? '')),
            'jenis_revisi' => $item['jenis_revisi'] ?? null,
            'kd_rup_baru' => $item['kd_rup_baru'] ?? null,
            'kd_rup_lama' => $item['kd_rup_lama'] ?? null,
            'tgl_kaji_ulang' => $item['tgl_kaji_ulang'] ?? null,
            'last_update_ref' => $item['last_update_ref'] ?? null,
        ];

        return hash('sha256', json_encode($key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Convert ISO 8601 datetime to MySQL datetime format
     */
    protected static function parseDateTime($value)
    {
        if (!$value) {
            return null;
        }

        try {
            $dt = new \DateTime($value);

            if ((int) $dt->format('Y') < 1000) {
                return null;
            }

            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
