<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PencatatanSwakelola extends Model
{
    protected $table = 'swakelola_pencatatan';

    protected $fillable = [
        'tahun_anggaran', 'kd_klpd', 'nama_klpd', 'jenis_klpd', 'kd_satker', 'kd_satker_str',
        'nama_satker', 'kd_lpse', 'kd_swakelola_pct', 'kd_rup', 'nama_paket', 'pagu',
        'total_realisasi', 'nilai_pdn_pct', 'nilai_umk_pct', 'sumber_dana', 'uraian_pekerjaan',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];
}
