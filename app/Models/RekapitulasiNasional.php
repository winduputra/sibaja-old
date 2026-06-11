<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapitulasiNasional extends Model
{
    protected $table = 'rekapitulasi_nasional';

    protected $fillable = [
        'province_code',
        'province_name',
        'source_url',
        'penyedia_realisasi',
        'penyedia_perencanaan',
        'penyedia_persentase',
        'swakelola_realisasi',
        'swakelola_perencanaan',
        'swakelola_persentase',
        'total_realisasi',
        'total_perencanaan',
        'total_persentase',
        'raw_text_hash',
        'scraped_at',
    ];

    protected $casts = [
        'penyedia_realisasi' => 'decimal:2',
        'penyedia_perencanaan' => 'decimal:2',
        'penyedia_persentase' => 'decimal:2',
        'swakelola_realisasi' => 'decimal:2',
        'swakelola_perencanaan' => 'decimal:2',
        'swakelola_persentase' => 'decimal:2',
        'total_realisasi' => 'decimal:2',
        'total_perencanaan' => 'decimal:2',
        'total_persentase' => 'decimal:2',
        'scraped_at' => 'datetime',
    ];
}
