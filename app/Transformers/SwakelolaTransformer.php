<?php

namespace App\Transformers;

class SwakelolaTransformer
{
    public static function pencatatan(array $item, $tahun = null): array
    {
        return [
            'tahun_anggaran' => $tahun ?? $item['tahun'] ?? $item['tahun_anggaran'] ?? date('Y'),
            'kd_klpd' => $item['kd_klpd'] ?? 'D264',
            'nama_klpd' => $item['nama_klpd'] ?? null,
            'jenis_klpd' => $item['jenis_klpd'] ?? null,
            'kd_satker' => $item['kd_satker'] ?? null,
            'kd_satker_str' => $item['kd_satker_str'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'kd_lpse' => $item['kd_lpse'] ?? null,
            'kd_swakelola_pct' => $item['kd_swakelola_pct'] ?? null,
            'kd_rup' => $item['kd_rup'] ?? null,
            'nama_paket' => $item['nama_paket'] ?? null,
            'pagu' => self::parseNumber($item['pagu'] ?? 0),
            'total_realisasi' => self::parseNumber($item['total_realisasi'] ?? 0),
            'nilai_pdn_pct' => self::parseNumber($item['nilai_pdn_pct'] ?? 0),
            'nilai_umk_pct' => self::parseNumber($item['nilai_umk_pct'] ?? 0),
            'sumber_dana' => $item['sumber_dana'] ?? null,
            'uraian_pekerjaan' => $item['uraian_pekerjaan'] ?? null,
            'last_synced_at' => now(),
        ];
    }

    public static function pencatatanRealisasi(array $item, $tahun = null): array
    {
        return [
            'tahun_anggaran' => $tahun ?? $item['tahun'] ?? $item['tahun_anggaran'] ?? date('Y'),
            'kd_klpd' => $item['kd_klpd'] ?? 'D264',
            'kd_satker' => $item['kd_satker'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'kd_swakelola_pct' => $item['kd_swakelola_pct'] ?? null,
            'jenis_realisasi' => $item['jenis_realisasi'] ?? null,
            'no_realisasi' => $item['no_realisasi'] ?? null,
            'tgl_realisasi' => self::parseDateTime($item['tgl_realisasi'] ?? null),
            'nilai_realisasi' => self::parseNumber($item['nilai_realisasi'] ?? $item['pagu'] ?? 0),
            'dok_realisasi' => $item['dok_realisasi'] ?? null,
            'ket_realisasi' => $item['ket_realisasi'] ?? null,
            'nama_pelaksana' => $item['nama_pelaksana'] ?? null,
            'npwp_pelaksana' => $item['npwp_pelaksana'] ?? null,
            'nip_ppk' => $item['nip_ppk'] ?? null,
            'nama_ppk' => $item['nama_ppk'] ?? null,
        ];
    }

    private static function parseNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_string($value) && preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);

            return (float) $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = str_replace('.', '', (string) $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    private static function parseDateTime($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return (new \DateTime($value))->format('Y-m-d H:i:s');
        } catch (\Exception $exception) {
            return null;
        }
    }
}
