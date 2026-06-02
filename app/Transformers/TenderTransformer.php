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
            'tahun' => $tahun ?? $item['tahun_anggaran'] ?? $item['tahun'] ?? date('Y'),
            'kd_klpd' => $item['kd_klpd'] ?? null,
            'nama_klpd' => $item['nama_klpd'] ?? null,
            'jenis_klpd' => $item['jenis_klpd'] ?? null,
            'kd_satker' => $item['kd_satker'] ?? null,
            'kd_satker_str' => $item['kd_satker_str'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'alamat_satker' => $item['alamat_satker'] ?? null,
            'kd_lpse' => $item['kd_lpse'] ?? null,
            'nama_paket' => $item['nama_paket'] ?? null,
            'lingkup_pekerjaan' => $item['lingkup_pekerjaan'] ?? null,
            'no_sppbj' => $item['no_sppbj'] ?? null,
            'no_kontrak' => $item['no_kontrak'] ?? null,
            'tgl_kontrak' => self::parseDateTime($item['tgl_kontrak'] ?? null),
            'tgl_kontrak_awal' => self::parseDateTime($item['tgl_kontrak_awal'] ?? null),
            'tgl_kontrak_akhir' => self::parseDateTime($item['tgl_kontrak_akhir'] ?? null),
            'kota_kontrak' => $item['kota_kontrak'] ?? null,
            'nip_ppk' => $item['nip_ppk'] ?? null,
            'nama_ppk' => $item['nama_ppk'] ?? null,
            'jabatan_ppk' => $item['jabatan_ppk'] ?? null,
            'no_sk_ppk' => $item['no_sk_ppk'] ?? null,
            'nama_penyedia' => $item['nama_penyedia'] ?? null,
            'kd_penyedia' => $item['kd_penyedia'] ?? null,
            'npwp_penyedia' => $item['npwp_penyedia'] ?? null,
            'npwp_16_penyedia' => $item['npwp_16_penyedia'] ?? null,
            'bentuk_usaha_penyedia' => $item['bentuk_usaha_penyedia'] ?? null,
            'tipe_penyedia' => $item['tipe_penyedia'] ?? null,
            'anggota_kso' => $item['anggota_kso'] ?? null,
            'wakil_sah_penyedia' => $item['wakil_sah_penyedia'] ?? null,
            'jabatan_wakil_penyedia' => $item['jabatan_wakil_penyedia'] ?? null,
            'nama_rek_bank' => $item['nama_rek_bank'] ?? null,
            'no_rek_bank' => $item['no_rek_bank'] ?? null,
            'nama_pemilik_rek_bank' => $item['nama_pemilik_rek_bank'] ?? null,
            'jenis_kontrak' => $item['jenis_kontrak'] ?? null,
            'status_kontrak' => $item['status_kontrak'] ?? null,
            'nilai_kontrak' => self::parseNumber($item['nilai_kontrak'] ?? 0),
            'nilai_pdn_kontrak' => self::parseNumber($item['nilai_pdn_kontrak'] ?? 0),
            'nilai_umk_kontrak' => self::parseNumber($item['nilai_umk_kontrak'] ?? 0),
            'alasan_ubah_nilai_kontrak' => $item['alasan_ubah_nilai_kontrak'] ?? null,
            'alasan_nilai_kontrak_10_persen' => $item['alasan_nilai_kontrak_10_persen'] ?? null,
            'informasi_lainnya' => $item['informasi_lainnya'] ?? null,
            'tgl_penetapan_status_kontrak' => self::parseDateTime($item['tgl_penetapan_status_kontrak'] ?? null),
            'alasan_penetapan_status_kontrak' => $item['alasan_penetapan_status_kontrak'] ?? null,
            'apakah_addendum' => $item['apakah_addendum'] ?? null,
            'versi_addendum' => $item['versi_addendum'] ?? null,
            'alasan_addendum' => $item['alasan_addendum'] ?? null,
            'sumber_api' => 'inaproc_v1',
        ];
    }

    /**
     * Transform tender selesai nilai (completed tender value) data
     */
    public static function selesaiNilai(array $item, $tahun = null): array
    {
        return [
            'kd_tender' => $item['kd_tender'] ?? null,
            'kd_paket' => $item['kd_paket'] ?? null,
            'kd_rup_paket' => $item['kd_rup_paket'] ?? null,
            'nama_paket' => $item['nama_paket'] ?? null,
            'kd_klpd' => $item['kd_klpd'] ?? null,
            'nama_klpd' => $item['nama_klpd'] ?? null,
            'jenis_klpd' => $item['jenis_klpd'] ?? null,
            'kd_satker' => $item['kd_satker'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'kd_lpse' => $item['kd_lpse'] ?? null,
            'pagu' => self::parseNumber($item['pagu'] ?? 0),
            'hps' => self::parseNumber($item['hps'] ?? 0),
            'nilai_penawaran' => self::parseNumber($item['nilai_penawaran'] ?? 0),
            'nilai_terkoreksi' => self::parseNumber($item['nilai_terkoreksi'] ?? 0),
            'nilai_negosiasi' => self::parseNumber($item['nilai_negosiasi'] ?? 0),
            'nilai_kontrak' => self::parseNumber($item['nilai_kontrak'] ?? 0),
            'nilai_pdn_kontrak' => self::parseNumber($item['nilai_pdn_kontrak'] ?? 0),
            'nilai_umk_kontrak' => self::parseNumber($item['nilai_umk_kontrak'] ?? 0),
            'kd_penyedia' => $item['kd_penyedia'] ?? null,
            'nama_penyedia' => $item['nama_penyedia'] ?? null,
            'npwp_penyedia' => $item['npwp_penyedia'] ?? null,
            'npwp_16_penyedia' => $item['npwp_16_penyedia'] ?? null,
            'tgl_pengumuman_tender' => self::parseDateTime($item['tgl_pengumuman_tender'] ?? null),
            'tgl_penetapan_pemenang' => self::parseDateTime($item['tgl_penetapan_pemenang'] ?? null),
            'tahun' => $tahun ?? $item['tahun_anggaran'] ?? $item['tahun'] ?? date('Y'),
            'sumber_api' => 'inaproc_v1',
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
