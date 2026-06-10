<?php

/**
 * INAPROC API Configuration
 * Centralized configuration for new INAPROC API v1 endpoints
 *
 * Rate Limits:
 * - 1000 requests per minute
 * - 5000 requests per hour
 * - Max 1000 results per request
 */

return [
    'inaproc' => [
        'base_url' => env('INAPROC_API_BASE_URL', 'https://data.inaproc.id/api/v1/'),
        'bearer_token' => env('INAPROC_API_TOKEN', ''),
        'kode_klpd' => 'D264',
        'default_tahun' => '2026',
        'limit_per_request' => 1000,

        'rate_limit' => [
            'per_minute' => env('INAPROC_RATE_LIMIT_MINUTE', 1000),
            'per_hour' => env('INAPROC_RATE_LIMIT_HOUR', 5000),
        ],

        'endpoints' => [
            // RUP: Rencana Umum Pengadaan
            'rup_master_satker' => [
                'path' => 'rup/master-satker',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2026], // Only 2026 available
                'filters' => [
                    'tahun_aktif' => 2026, // Must include this year
                ],
                'required_fields' => ['kd_satker', 'nama_satker', 'tahun_aktif'],
                'model' => 'App\Models\Satker',
                'table' => 'satkers',
                'unique_key' => ['kd_satker'],
            ],

            'rup_paket_penyedia' => [
                'path' => 'rup/paket-penyedia-terumumkan',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026], // Support 2025 and 2026
                'filters' => [
                    'status_aktif_rup' => true,
                    'status_umumkan_rup' => 'Terumumkan',
                ],
                'required_fields' => [
                    'jenis_pengadaan', 'kd_rup', 'kd_satker', 'metode_pengadaan',
                    'nama_paket', 'nama_satker', 'pagu', 'tgl_akhir_kontrak',
                    'tgl_akhir_pemilihan', 'tgl_awal_kontrak', 'tgl_awal_pemilihan',
                    'tgl_pengumuman_paket', 'tipe_paket', 'urarian_pekerjaan'
                ],
                'model' => 'App\Models\Penyedia',
                'table' => 'penyedias',
                'unique_key' => ['kd_rup'],
            ],

            'rup_paket_swakelola' => [
                'path' => 'rup/paket-swakelola-terumumkan',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [
                    'status_aktif_rup' => true,
                    'status_umumkan_rup' => 'Terumumkan',
                ],
                'required_fields' => [
                    'kd_rup', 'kd_satker', 'nama_paket', 'nama_satker', 'pagu',
                    'tgl_akhir_pelaksanaan_kontrak', 'tgl_awal_pelaksanaan_kontrak',
                    'tgl_buat_paket', 'tgl_pengumuman_paket', 'uraian_pekerjaan'
                ],
                'model' => 'App\Models\StrategicPackage',
                'table' => 'strategic_packages',
                'unique_key' => ['kd_rup'],
            ],

            'rup_history_kaji_ulang' => [
                'path' => 'rup/history-kaji-ulang',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'jenis_paket' => 'PENYEDIA ', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'jenis_paket' => ['PENYEDIA ', 'SWAKELOLA'],
                'filters' => [],
                'required_fields' => [
                    'kd_klpd', 'tahun_anggaran', 'jenis_paket', 'jenis_revisi',
                    'kd_rup_baru', 'tgl_kaji_ulang'
                ],
                'model' => 'App\Models\RupHistoryKajiUlang',
                'table' => 'rup_history_kaji_ulang',
                'unique_key' => ['payload_hash'],
            ],

            // TENDER: Pengadaan Barang/Jasa
            'tender_pengumuman' => [
                'path' => 'tender/pengumuman',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [],
                'required_fields' => [
                    'hps', 'jenis_pengadaan', 'kd_pkt_dce', 'kd_rup', 'kd_satker',
                    'kd_tender', 'mtd_pemilihan', 'nama_paket', 'nama_satker', 'pagu',
                    'status_tender', 'sumber_dana', 'tanggal_status', 'tgl_pengumuman_tender'
                ],
                'model' => 'App\Models\TenderPengumumanData',
                'table' => 'tender_pengumuman_data',
                'unique_key' => ['kd_tender'],
            ],

            'tender_ekontrak_kontrak' => [
                'path' => 'tender/tender-ekontrak-kontrak',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [],
                'required_fields' => [
                    'jenis_kontrak', 'kd_satker', 'kd_tender', 'nama_paket',
                    'nama_satker', 'status_kontrak'
                    // nilai_kontrak is optional - may not exist for all ekontrak records yet
                ],
                'model' => 'App\Models\Tender',
                'table' => 'tenders',
                'unique_key' => ['kd_tender'],
            ],

            'tender_selesai_nilai' => [
                'path' => 'tender/tender-selesai-nilai',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [],
                'required_fields' => [
                    'hps', 'kd_tender', 'nama_satker', 'pagu'
                    // All nilai_* fields are optional - not all records have them
                ],
                'model' => 'App\Models\TenderSelesaiNilaiData',
                'table' => 'tender_selesai_nilai_data',
                'unique_key' => ['kd_tender'],
            ],

            // NON-TENDER: Pengadaan Non-Tender
            'non_tender_pengumuman' => [
                'path' => 'tender/non-tender-pengumuman',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [],
                'required_fields' => [
                    'hps', 'jenis_pengadaan', 'kd_nontender', 'kd_pkt_dce', 'kd_rup',
                    'kd_satker', 'kontrak_pembayaran', 'kualifikasi_paket', 'mtd_pemilihan',
                    'nama_paket', 'nama_satker', 'pagu', 'status_nontender', 'sumber_dana',
                    'tgl_buat_paket', 'tgl_pengumuman_nontender'
                ],
                'model' => 'App\Models\NonTenderPengumuman',
                'table' => 'non_tender_pengumuman',
                'unique_key' => ['kd_nontender'],
            ],

            'non_tender_selesai' => [
                'path' => 'tender/non-tender-selesai',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [],
                'required_fields' => [
                    'hps', 'jenis_pengadaan', 'kd_nontender', 'kd_satker',
                    'mtd_pemilihan', 'nama_paket', 'nama_satker', 'pagu', 'status_nontender'
                    // All nilai_* fields are optional
                ],
                'model' => 'App\Models\NonTenderSelesai',
                'table' => 'non_tender_selesai',
                'unique_key' => ['kd_nontender'],
            ],

            'non_tender_ekontrak_kontrak' => [
                'path' => 'tender/non-tender-ekontrak-kontrak',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [],
                'required_fields' => [
                    'apakah_addendum', 'jenis_kontrak',
                    'kd_nontender', 'kd_satker', 'mtd_pengadaan',
                    'nama_paket', 'nama_satker', 'status_kontrak', 'tgl_kontrak'
                    // nilai_* fields are optional
                ],
                'model' => 'App\Models\NonTenderKontrak',
                'table' => 'non_tender_contract',
                'unique_key' => ['kd_nontender'],
            ],

            'pencatatan_non_tender' => [
                'path' => 'tender/pencatatan-non-tender',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [],
                'required_fields' => [
                    'kd_nontender_pct', 'kd_satker', 'nama_paket', 'nama_satker', 'pagu'
                ],
                'model' => 'App\Models\PencatatanNonTender',
                'table' => 'non_tender_pencatatan',
                'unique_key' => ['kd_nontender_pct'],
            ],

            'pencatatan_non_tender_realisasi' => [
                'path' => 'tender/pencatatan-non-tender-realisasi',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [],
                'required_fields' => [
                    'jenis_realisasi', 'kd_nontender_pct', 'kd_satker',
                    'nama_paket', 'nama_satker', 'pagu'
                    // nilai_realisasi is optional
                ],
                'model' => 'App\Models\RealisasiNonTender',
                'table' => 'non_tender_realisasi',
                'unique_key' => ['kd_nontender_pct'],
            ],

            'pencatatan_swakelola' => [
                'path' => 'tender/pencatatan-swakelola',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [],
                'required_fields' => [
                    'kd_swakelola_pct', 'kd_satker', 'nama_paket', 'nama_satker', 'pagu'
                ],
                'model' => 'App\Models\PencatatanSwakelola',
                'table' => 'swakelola_pencatatan',
                'unique_key' => ['kd_swakelola_pct'],
            ],

            'pencatatan_swakelola_realisasi' => [
                'path' => 'tender/pencatatan-swakelola-realisasi',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [],
                'required_fields' => [
                    'jenis_realisasi', 'kd_swakelola_pct', 'kd_satker',
                    'nama_paket', 'nama_satker', 'pagu'
                ],
                'model' => 'App\Models\SwakelolaRealisasi',
                'table' => 'swakelola_realisasi',
                'unique_key' => ['kd_swakelola_pct'],
            ],

            // E-KATALOG
            'ekatalog_v6' => [
                'path' => 'ekatalog/paket-e-purchasing',
                'method' => 'GET',
                'params' => ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
                'supported_years' => [2025, 2026],
                'filters' => [
                    'kode_klpd' => 'D264',
                    'status' => ['PAYMENT_OUTSIDE_SYSTEM', 'COMPLETED', 'ON_PROCESS', 'ON_ADDENDUM'],
                ],
                'required_fields' => [
                    'count_product', 'fiscal_year', 'funding_source', 'kode_penyedia',
                    'kode_satker', 'nama_satker', 'order_date', 'order_id', 'rekan_id',
                    'rup_code', 'rup_name', 'shipment_status', 'shipping_fee', 'status',
                    'total', 'total_qty'
                ],
                'model' => 'App\Models\EkatalogV6Paket',
                'table' => 'ekatalog_v6_pakets',
                'unique_key' => ['order_id'],
            ],
        ],
    ],
];
