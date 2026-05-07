<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Satker extends Model
{
    use HasFactory;

    protected $fillable = [
        'tahun_anggaran',
        'tahun_aktif_json',
        'kd_satker',
        'kd_satker_str',
        'nama_satker',
        'alamat',
        'telepon',
        'fax',
        'kodepos',
        'status_satker',
        'ket_satker',
        'jenis_satker',
        'kd_klpd',
        'nama_klpd',
        'jenis_klpd',
        'kode_eselon',
        'sync_source',
        'last_synced_at',
        'migrated_at',
    ];

    protected $casts = [
        'tahun_aktif_json' => 'json',
        'last_synced_at' => 'datetime',
        'migrated_at' => 'datetime',
    ];

    // Jika Anda ingin memformat kolom tertentu atau menjalankan relasi, Anda dapat menambahkannya di sini.
    // Contoh:
    // public function klpd() {
    //     return $this->belongsTo(Klpd::class, 'kd_klpd', 'kd_klpd');
    // }
    
}