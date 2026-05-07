# 🚀 Quick Reference: php artisan update:all

## ⚡ Quick Start

```bash
# Sync everything (default year 2026)
php artisan update:all

# See help
php artisan update:all --help
```

## 📋 Common Commands

```bash
# Daily sync (2026 only)
php artisan update:all

# Weekly full sync (all years)
php artisan update:all --all-years

# Test mode (preview without saving)
php artisan update:all --dry-run

# Skip heavy modules (faster sync)
php artisan update:all --skip=ekatalog

# Single module only
php artisan update:all --only=rup

# Specific year
php artisan update:all --tahun=2025
```

## 🎯 Available Modules

- `rup` - RUP (Rencana Umum Pengadaan)
- `tender` - Tender Pengadaan
- `non-tender` - Non-Tender Pengadaan  
- `ekatalog` - E-Katalog V6

## 📊 Options Summary

| Option | Purpose |
|--------|---------|
| `--tahun=YEAR` | Set year (default: 2026) |
| `--all-years` | Sync all supported years |
| `--dry-run` | Preview without saving |
| `--limit=N` | Limit records per endpoint |
| `--only=MODULE` | Sync only specific modules (comma-separated) |
| `--skip=MODULE` | Skip specific modules (comma-separated) |

## 🔗 Full Documentation

See [UPDATE_ALL_COMMAND.md](UPDATE_ALL_COMMAND.md) for complete guide with examples and troubleshooting.

---
**Created**: April 29, 2026
