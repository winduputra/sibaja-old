# 📌 Command: php artisan update:all

## Deskripsi
Command `update:all` adalah orchestrator yang menjalankan **semua sync command** dari INAPROC API dalam satu perintah. Ini akan secara otomatis menyinkronkan:

- ✅ **RUP Data** (Master Satker, Paket Penyedia, Paket Swakelola)
- ✅ **Tender Data** (Pengumuman, Ekontrak, Selesai Nilai)
- ✅ **Non-Tender Data** (Pengumuman, Selesai, Ekontrak, Realisasi)
- ✅ **E-Katalog V6** (E-Purchasing)

---

## 📖 Cara Penggunaan

### 1️⃣ Sync Semua Data (Tahun Default 2026)
```bash
php artisan update:all
```

### 2️⃣ Sync Tahun Tertentu
```bash
php artisan update:all --tahun=2025
```

### 3️⃣ Sync Semua Tahun yang Didukung
```bash
php artisan update:all --all-years
```

### 4️⃣ Dry Run (Preview Tanpa Menyimpan)
```bash
php artisan update:all --dry-run
```

### 5️⃣ Dengan Limit Records
```bash
php artisan update:all --limit=100
```

### 6️⃣ Sync Module Tertentu Saja
```bash
# Hanya RUP dan Tender
php artisan update:all --only=rup,tender

# Hanya RUP
php artisan update:all --only=rup
```

### 7️⃣ Skip Module Tertentu
```bash
# Sync semua kecuali E-Katalog
php artisan update:all --skip=ekatalog

# Sync semua kecuali Non-Tender
php artisan update:all --skip=non-tender
```

### 8️⃣ Kombinasi Options
```bash
# Sync RUP dan Tender tahun 2025 dengan limit 100 records
php artisan update:all --tahun=2025 --only=rup,tender --limit=100

# Sync semua tahun dengan dry-run (preview saja)
php artisan update:all --all-years --dry-run

# Sync semua tahun kecuali E-Katalog
php artisan update:all --all-years --skip=ekatalog
```

---

## 📊 Opsi Command

| Opsi | Default | Deskripsi |
|------|---------|-----------|
| `--tahun` | 2026 | Tahun data yang akan disinkronkan |
| `--all-years` | - | Sync semua tahun yang didukung oleh API |
| `--dry-run` | - | Preview tanpa menyimpan ke database |
| `--limit` | 0 (unlimited) | Batasi jumlah records yang disinkronkan |
| `--only` | - | Hanya sinkronkan module tertentu (comma-separated) |
| `--skip` | - | Skip module tertentu (comma-separated) |

---

## 🎯 Module yang Tersedia

```
Modules untuk --only dan --skip:
  ✓ rup         → RUP Data (Master Satker, Penyedia, Swakelola)
  ✓ tender      → Tender Data (Pengumuman, Ekontrak, Selesai Nilai)
  ✓ non-tender  → Non-Tender Data (Pengumuman, Selesai, Ekontrak, Realisasi)
  ✓ ekatalog    → E-Katalog V6 Data
```

---

## 📋 Contoh Penggunaan Praktis

### 📌 Skenario 1: Update Daily (Pagi hari, tahun 2026)
```bash
php artisan update:all --tahun=2026
```
**Keterangan**: Menjalankan sync dengan cepat untuk data 2026

---

### 📌 Skenario 2: Update Bulanan (Sync semua tahun)
```bash
php artisan update:all --all-years
```
**Keterangan**: Sinkronkan semua data dari semua tahun yang didukung API

---

### 📌 Skenario 3: Testing/Preview (Dry Run)
```bash
php artisan update:all --tahun=2026 --dry-run --limit=50
```
**Keterangan**: Cek data yang akan disinkronkan tanpa benar-benar menyimpan

---

### 📌 Skenario 4: Update RUP Saja
```bash
php artisan update:all --tahun=2026 --only=rup
```
**Keterangan**: Sync hanya RUP data jika tender/non-tender sedang error

---

### 📌 Skenario 5: Update Cepat (Skip E-Katalog)
```bash
php artisan update:all --tahun=2026 --skip=ekatalog
```
**Keterangan**: Sync hanya data penting (RUP, Tender, Non-Tender)

---

## 📈 Output Example

```
╔════════════════════════════════════════════════╗
║   🚀 SIBAJA DATA SYNC - UPDATE ALL              ║
║   Sync all data from INAPROC API                ║
╚════════════════════════════════════════════════╝

Options:
  - Year: ALL SUPPORTED YEARS
  - Dry Run: NO
  - Limit: UNLIMITED
  - Only: rup,tender

📦 Modules to sync: rup, tender

┌────────────────────────────────────────────────┐
│ [1/2] RUP Data
└────────────────────────────────────────────────┘

╔═══════════════════════════════════════╗
║ Syncing RUP Data for Year: 2025
╚═══════════════════════════════════════╝
...
✅ RUP Data synced successfully!

┌────────────────────────────────────────────────┐
│ [2/2] Tender Data
└────────────────────────────────────────────────┘

╔═══════════════════════════════════════╗
║ Syncing Tender Data for Year: 2025
╚═══════════════════════════════════════╝
...
✅ Tender Data synced successfully!

╔════════════════════════════════════════════════╗
║   ✨ UPDATE ALL COMPLETED                       ║
╚════════════════════════════════════════════════╝

📊 Summary:
  - Total Modules: 2
  - With Errors: 0
  - Duration: 45.32s
```

---

## 🚨 Tips & Troubleshooting

### ❓ Command tidak ditemukan?
```bash
# Clear command cache
php artisan cache:clear

# Coba lagi
php artisan update:all --help
```

### ❓ Ingin melihat detail sync setiap module?
Tambahkan `-v` untuk verbose mode:
```bash
php artisan update:all -v
```

### ❓ Ingin lihat semua command yang tersedia?
```bash
php artisan list
```

### ❓ Bagaimana jika API sedang down?
Command akan menampilkan error untuk module yang gagal, tapi tetap melanjutkan ke module berikutnya.

### ❓ Perlu rollback data?
Gunakan `--dry-run` untuk preview terlebih dahulu:
```bash
php artisan update:all --dry-run --tahun=2026
```

---

## 🔄 Scheduled Execution

Jika ingin menjalankan sync secara periodik, tambahkan ke `app/Console/Kernel.php`:

```php
// Sync semua data setiap hari jam 2 pagi
$schedule->command('update:all --tahun=2026')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Sync semua tahun setiap minggu Minggu jam 3 pagi
$schedule->command('update:all --all-years --skip=ekatalog')
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->withoutOverlapping();
```

---

## 📝 Catatan Penting

⚠️ **Database Capacity**: Sync semua tahun dengan `--all-years` bisa memakan waktu lama. Pastikan:
- Database memiliki ruang disk yang cukup
- Tidak ada query berat yang berjalan bersamaan
- Connection pool sudah dikonfigurasi dengan baik

⚠️ **API Rate Limits**: INAPROC API memiliki batas:
- 1000 requests per menit
- 5000 requests per jam
- Command sudah menghandle ini, tapi pastikan tidak menjalankan multiple instances konsekutif

⚠️ **Maintenance Window**: Jalankan sync di luar jam operasional peak untuk menghindari:
- Timeout pada user interface
- Database lock
- High server load

---

## 🎯 Summary

| Situasi | Command |
|---------|---------|
| Daily sync (2026 only) | `php artisan update:all` |
| Weekly full sync | `php artisan update:all --all-years` |
| Testing/Preview | `php artisan update:all --dry-run` |
| Quick update (skip catalogs) | `php artisan update:all --skip=ekatalog` |
| Single module update | `php artisan update:all --only=tender` |

---

**Created**: April 29, 2026  
**Command File**: `app/Console/Commands/UpdateAllCommand.php`
