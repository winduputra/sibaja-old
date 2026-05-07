<?php

namespace App\Transformers;

/**
 * Transform Tender data from INAPROC API to internal format
 */
class TenderTransformer
{
    /**
     * Transform tender pengumuman (announcement) data
     */
    public static function pengumuman(array $item, $tahun = null): array
    {
        return [
            'kd_tender' => $item['kd_tender'] ?? null,
            'tahun' => $tahun ?? $item['tahun'] ?? date('Y'),
            'kd_klpd' => $item['kd_klpd'] ?? 'D264',
            'nama_klpd' => $item['nama_klpd'] ?? null,
            'kd_satker' => $item['kd_satker'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'kd_pkt_dce' => $item['kd_pkt_dce'] ?? null,
            'kd_rup' => $item['kd_rup'] ?? null,
            'nama_paket' => $item['nama_paket'] ?? null,
            'jenis_pengadaan' => $item['jenis_pengadaan'] ?? null,
            'mtd_pemilihan' => $item['mtd_pemilihan'] ?? null,
            'pagu' => self::parseNumber($item['pagu'] ?? 0),
            'hps' => self::parseNumber($item['hps'] ?? 0),
            'sumber_dana' => $item['sumber_dana'] ?? null,
            'status_tender' => $item['status_tender'] ?? null,
            'tgl_pengumuman_tender' => self::parseDateTime($item['tgl_pengumuman_tender'] ?? null),
            'tanggal_status' => self::parseDateTime($item['tanggal_status'] ?? null),
            'sync_source' => 'inaproc_v1',
            'last_synced_at' => now(),
        ];
    }

    /**
     * Transform tender ekontrak kontrak (contract) data
     */
    public static function ekontrakKontrak(array $item, $tahun = null): array
    {
        return [
            'kd_tender' => $item['kd_tender'] ?? null,
            'tahun_anggaran' => $tahun ?? $item['tahun'] ?? date('Y'),
            'kd_satker' => $item['kd_satker'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'nama_paket' => $item['nama_paket'] ?? null,
            'jenis_kontrak' => $item['jenis_kontrak'] ?? null,
            'status_kontrak' => $item['status_kontrak'] ?? null,
            'nilai_kontrak' => self::parseNumber($item['nilai_kontrak'] ?? 0),
            'nilai_pdn_kontrak' => self::parseNumber($item['nilai_pdn_kontrak'] ?? 0),
            'nilai_umk_kontrak' => self::parseNumber($item['nilai_umk_kontrak'] ?? 0),
            'sync_source' => 'inaproc_v1',
            'last_synced_at' => now(),
        ];
    }

    /**
     * Transform tender selesai nilai (completed tender value) data
     */
    public static function selesaiNilai(array $item): array
    {
        return [
            'kd_tender' => $item['kd_tender'] ?? null,
            'kd_paket' => $item['kd_paket'] ?? null,
            'kd_rup_paket' => $item['kd_rup_paket'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'pagu' => self::parseNumber($item['pagu'] ?? 0),
            'hps' => self::parseNumber($item['hps'] ?? 0),
            'nilai_penawaran' => self::parseNumber($item['nilai_penawaran'] ?? 0),
            'nilai_terkoreksi' => self::parseNumber($item['nilai_terkoreksi'] ?? 0),
            'nilai_negosiasi' => self::parseNumber($item['nilai_negosiasi'] ?? 0),
            'nilai_kontrak' => self::parseNumber($item['nilai_kontrak'] ?? 0),
            'nilai_pdn_kontrak' => self::parseNumber($item['nilai_pdn_kontrak'] ?? 0),
            'nilai_umk_kontrak' => self::parseNumber($item['nilai_umk_kontrak'] ?? 0),
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
}
