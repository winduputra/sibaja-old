<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RupHistoryKajiUlang extends Model
{
    use HasFactory;

    protected $table = 'rup_history_kaji_ulang';

    protected $fillable = [
        'tahun_anggaran',
        'kd_klpd',
        'nama_klpd',
        'jenis_klpd',
        'jenis_paket',
        'jenis_revisi',
        'kd_rup_baru',
        'kd_rup_lama',
        'kd_satker',
        'kd_satker_str',
        'nama_satker',
        'tgl_kaji_ulang',
        'alasan_kajiulang',
        'last_update_ref',
        'payload_hash',
        'raw_payload',
        'sync_source',
        'last_synced_at',
    ];

    protected $casts = [
        'tgl_kaji_ulang' => 'datetime',
        'last_update_ref' => 'datetime',
        'last_synced_at' => 'datetime',
        'raw_payload' => 'array',
    ];
}
