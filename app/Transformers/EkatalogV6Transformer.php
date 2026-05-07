<?php

namespace App\Transformers;

/**
 * Transform E-Katalog V6 data from INAPROC API to internal format
 */
class EkatalogV6Transformer
{
    /**
     * Transform e-katalog V6 paket e-purchasing data
     */
    public static function paketEPurchasing(array $item): array
    {
        return [
            'kd_paket' => $item['order_id'] ?? null,
            'tahun_anggaran' => $item['fiscal_year'] ?? date('Y'),
            'kd_klpd' => $item['kode_klpd'] ?? 'D264',
            'kd_satker_str' => $item['kode_satker'] ?? null,
            'nama_satker' => $item['nama_satker'] ?? null,
            'rup_code' => $item['rup_code'] ?? null,
            'rup_name' => $item['rup_name'] ?? null,
            'kode_penyedia' => $item['kode_penyedia'] ?? null,
            'rekan_id' => $item['rekan_id'] ?? null,
            'tgl_order' => self::parseDateTime($item['order_date'] ?? null),
            'status_pkt' => $item['status'] ?? null,
            'status_pengiriman' => $item['shipment_status'] ?? null,
            'jml_jenis_produk' => (int) ($item['count_product'] ?? 0),
            'jml_produk' => (int) ($item['total_qty'] ?? 0),
            'total_harga' => self::parseNumber($item['total'] ?? 0),
            'ongkir' => self::parseNumber($item['shipping_fee'] ?? 0),
            'sumber_dana' => $item['funding_source'] ?? null,
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
