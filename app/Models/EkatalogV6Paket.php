<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EkatalogV6Paket extends Model
{
    use HasFactory;

    protected $table = 'ekatalog_v6_pakets';

    protected $fillable = [
        'tahun_anggaran',
        'jenis_instansi',
        'nama_instansi',
        'nama_satker',
        'kd_klpd',
        'kd_satker_str',
        'kd_paket',
        'rup_code',
        'rup_name',
        'kode_penyedia',
        'rekan_id',
        'kd_rup',
        'rup_nama_pkt',
        'sumber_dana',
        'mak',
        'kd_penyedia_ppn',
        'jml_jenis_produk',
        'jml_produk',
        'ongkir',
        'total_harga',
        'tgl_order',
        'status_pkt',
        'status_pengiriman',
        'order_id',
        'order_date',
        'status',
        'shipment_status',
        'count_product',
        'total_qty',
        'total',
        'shipping_fee',
        'funding_source',
        'sync_source',
        'last_synced_at',
        'migrated_at'
    ];

    protected $casts = [
        'tgl_order' => 'datetime',
        'order_date' => 'datetime',
        'last_synced_at' => 'datetime',
        'migrated_at' => 'datetime',
    ];

    public $timestamps = true;

    public static function uniqueKeys($data)
    {
        return ['kd_paket' => $data['kd_paket'] ?? null];
    }
}
