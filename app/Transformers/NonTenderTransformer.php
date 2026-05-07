<?php

namespace App\Transformers;

/**
 * Transform Non-Tender data from INAPROC API to internal format
 */
class NonTenderTransformer
{
    /**
     * Transform non-tender pengumuman (announcement) data
     */
    public static function pengumuman(array $item, $tahun = null): array
    {
        return [
            'kd_nontender' => $item['kd_nontender'] ?? null,
            'tahun_anggaran' => $tahun ?? $item['tahun'] ?? date('Y'),
            'kd_klpd' => $item['kd_klpd'] ?? 'D264',
            'nama_klpd' => $item['nama_klpd'] ?? null,
            'kd_satker' => $item['kd_satker'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'kd_pkt_dce' => $item['kd_pkt_dce'] ?? null,
            'kd_rup' => self::parseKdRup($item['kd_rup'] ?? null),
            'nama_paket' => $item['nama_paket'] ?? null,
            'jenis_pengadaan' => $item['jenis_pengadaan'] ?? null,
            'mtd_pemilihan' => $item['mtd_pemilihan'] ?? null,
            'pagu' => self::parseNumber($item['pagu'] ?? 0),
            'hps' => self::parseNumber($item['hps'] ?? 0),
            'kontrak_pembayaran' => $item['kontrak_pembayaran'] ?? null,
            'kualifikasi_paket' => $item['kualifikasi_paket'] ?? null,
            'sumber_dana' => $item['sumber_dana'] ?? null,
            'status_nontender' => $item['status_nontender'] ?? null,
            'tgl_buat_paket' => self::parseDateTime($item['tgl_buat_paket'] ?? null),
            'tgl_pengumuman_nontender' => self::parseDateTime($item['tgl_pengumuman_nontender'] ?? null),
            'sync_source' => 'inaproc_v1',
            'last_synced_at' => now(),
        ];
    }

    /**
     * Transform non-tender selesai (completed) data
     */
    public static function selesai(array $item, $tahun = null): array
    {
        return [
            'kd_nontender' => $item['kd_nontender'] ?? null,
            'tahun_anggaran' => $tahun ?? $item['tahun'] ?? date('Y'),
            'kd_satker' => $item['kd_satker'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'kd_pkt_dce' => $item['kd_pkt_dce'] ?? null,
            'kd_rup' => self::parseKdRup($item['kd_rup'] ?? null),
            'nama_paket' => $item['nama_paket'] ?? null,
            'jenis_pengadaan' => $item['jenis_pengadaan'] ?? null,
            'mtd_pemilihan' => $item['mtd_pemilihan'] ?? null,
            'pagu' => self::parseNumber($item['pagu'] ?? 0),
            'hps' => self::parseNumber($item['hps'] ?? 0),
            'nilai_penawaran' => self::parseNumber($item['nilai_penawaran'] ?? 0),
            'nilai_terkoreksi' => self::parseNumber($item['nilai_terkoreksi'] ?? 0),
            'nilai_negosiasi' => self::parseNumber($item['nilai_negosiasi'] ?? 0),
            'nilai_kontrak' => self::parseNumber($item['nilai_kontrak'] ?? 0),
            'nilai_pdn_kontrak' => self::parseNumber($item['nilai_pdn_kontrak'] ?? 0),
            'nilai_umk_kontrak' => self::parseNumber($item['nilai_umk_kontrak'] ?? 0),
            'status_nontender' => $item['status_nontender'] ?? null,
            'sumber_dana' => $item['sumber_dana'] ?? null,
            'tgl_selesai_nontender' => self::parseDateTime($item['tgl_selesai_nontender'] ?? null),
            'sync_source' => 'inaproc_v1',
            'last_synced_at' => now(),
        ];
    }

    /**
     * Transform non-tender ekontrak kontrak (contract) data
     */
    public static function ekontrakKontrak(array $item, $tahun = null): array
    {
        return [
            'kd_nontender' => $item['kd_nontender'] ?? null,
            'tahun_anggaran' => $tahun ?? $item['tahun'] ?? date('Y'),
            'kd_satker' => $item['kd_satker'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'nama_paket' => $item['nama_paket'] ?? null,
            'bentuk_usaha_penyedia' => $item['bentuk_usaha_penyedia'] ?? null,
            'jenis_kontrak' => $item['jenis_kontrak'] ?? null,
            'mtd_pengadaan' => $item['mtd_pengadaan'] ?? null,
            'kota_kontrak' => $item['kota_kontrak'] ?? null,
            'status_kontrak' => $item['status_kontrak'] ?? null,
            'nilai_kontrak' => self::parseNumber($item['nilai_kontrak'] ?? 0),
            'nilai_pdn_kontrak' => self::parseNumber($item['nilai_pdn_kontrak'] ?? 0),
            'nilai_umk_kontrak' => self::parseNumber($item['nilai_umk_kontrak'] ?? 0),
            'no_sppbj' => $item['no_sppbj'] ?? null,
            'tgl_kontrak' => self::parseDateTime($item['tgl_kontrak'] ?? null),
            // Convert apakah_addendum to string if needed
            'apakah_addendum' => self::boolToString($item['apakah_addendum'] ?? false),
            'sync_source' => 'inaproc_v1',
            'last_synced_at' => now(),
        ];
    }

    /**
     * Transform pencatatan non-tender realisasi (realization recording) data
     */
    public static function pencatatanRealisasi(array $item, $tahun = null): array
    {
        return [
            'kd_nontender_pct' => $item['kd_nontender_pct'] ?? null,
            'tahun_anggaran' => $tahun ?? $item['tahun'] ?? date('Y'),
            'kd_paket_dce' => $item['kd_paket_dce'] ?? null,
            'kd_rup_paket' => $item['kd_rup_paket'] ?? null,
            'kd_satker' => $item['kd_satker'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'nama_paket' => $item['nama_paket'] ?? null,
            'jenis_realisasi' => $item['jenis_realisasi'] ?? null,
            'pagu' => self::parseNumber($item['pagu'] ?? 0),
            'nilai_realisasi' => self::parseNumber($item['nilai_realisasi'] ?? 0),
            'dok_realisasi' => $item['dok_realisasi'] ?? null,
            'ket_realisasi' => $item['ket_realisasi'] ?? null,
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

    /**
     * Convert ISO 8601 datetime to MySQL datetime format
     */
    protected static function parseDateTime($value)
    {
        if (!$value) {
            return null;
        }

        try {
            // Handle ISO 8601 format: 2026-03-05T22:38:31.06Z
            // Convert to: 2026-03-05 22:38:31
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i:s*', $value);
            if ($dt === false) {
                // Try alternative formats
                $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
            }
            if ($dt === false) {
                return null;
            }
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse kd_rup which may contain semicolon-separated values
     * Takes the first value if multiple are present
     */
    protected static function parseKdRup($value)
    {
        if (!$value) {
            return null;
        }

        // If contains semicolon, split and take first value
        if (\strpos($value, ';') !== false) {
            $values = \explode(';', $value);
            $first = \trim($values[0]);
            return !empty($first) ? $first : null;
        }

        return $value;
    }

    /**
     * Convert boolean/string to 'Y' or 'T' format
     */
    protected static function boolToString($value): string
    {
        if ($value === true || $value === 1 || $value === '1' || \strtolower($value) === 'true' || \strtolower($value) === 'yes') {
            return 'Y';
        }
        return 'T';
    }
}
