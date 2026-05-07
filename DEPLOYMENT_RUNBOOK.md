# INAPROC API Migration - Deployment Runbook

## Overview
This document guides the complete deployment of the new INAPROC API v1 integration to production. **NO STAGING ENVIRONMENT AVAILABLE** - extensive local testing is critical before production.

## Pre-Deployment Checklist (Day 7-8)

### Local Testing with Mock Data
```bash
# 1. Test individual commands with --dry-run and --limit=10
php artisan inaproc:sync-rup --dry-run --limit=10
php artisan inaproc:sync-tender --dry-run --limit=10
php artisan inaproc:sync-non-tender --dry-run --limit=10
php artisan inaproc:sync-ekatalog-v6 --dry-run --limit=10

# 2. Verify unit tests pass
php artisan test tests/Unit/Services/InaprocinaproApiClientTest.php
php artisan test tests/Unit/Services/InaprocinaproRateLimiterTest.php

# 3. Check for any output/error messages
# Expected: "Dry run mode - no data was saved"
```

### Database Backup (Critical!)
```bash
# Create full database backup BEFORE any changes
mkdir -p /var/backups/sibaja-old/$(date +%Y%m%d)
mysqldump -u root -p sibaja-old > /var/backups/sibaja-old/$(date +%Y%m%d)/sibaja-old_backup_$(date +%H%M%S).sql

# Verify backup integrity
gunzip -t /var/backups/sibaja-old/$(date +%Y%m%d)/*.sql.gz 2>&1 | head

# Save backup location in secure place (not in repo)
echo "/var/backups/sibaja-old/$(date +%Y%m%d)/sibaja-old_backup_$(date +%H%M%S).sql" > BACKUP_LOCATION.txt
```

### Test Backup Restore
```bash
# Create test database copy
mysqldump -u root -p sibaja-old > /tmp/test_restore.sql
mysql -u root -p -e "CREATE DATABASE sibaja_old_test;"
mysql -u root -p sibaja_old_test < /tmp/test_restore.sql

# Verify restore worked
mysql -u root -p sibaja_old_test -e "SELECT COUNT(*) as total_records FROM satkers;"

# Clean up test database
mysql -u root -p -e "DROP DATABASE sibaja_old_test;"
```

## Production Deployment Sequence (Day 8-9)

### Phase 1: Code Deployment (15 minutes)
```bash
# 1. In production environment, pull new code
cd /path/to/sibaja-old
git pull origin main

# 2. Install any new dependencies (unlikely, but check)
composer install --no-dev

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Phase 2: Database Migration (10 minutes)
```bash
# 4. Run migrations (TRUNCATES will happen here)
php artisan migrate

# 5. Verify tables were truncated (should see 0 rows)
mysql -u root -p -e "
SELECT 'satkers' as table_name, COUNT(*) as row_count FROM satkers
UNION ALL
SELECT 'penyedias', COUNT(*) FROM penyedias
UNION ALL
SELECT 'tender_pengumuman_data', COUNT(*) FROM tender_pengumuman_data
UNION ALL
SELECT 'non_tender_pengumuman', COUNT(*) FROM non_tender_pengumuman
UNION ALL
SELECT 'ekatalog_v6_pakets', COUNT(*) FROM ekatalog_v6_pakets;
" sibaja-old
```

### Phase 3: Conservative First Sync (30 minutes)
```bash
# 6. Test with limit=10 (just 10 records to verify transformation)
php artisan inaproc:sync-rup --type=satker --limit=10 2>&1 | tee sync_satker_10.log
php artisan inaproc:sync-tender --limit=10 2>&1 | tee sync_tender_10.log
php artisan inaproc:sync-non-tender --limit=10 2>&1 | tee sync_nontender_10.log
php artisan inaproc:sync-ekatalog-v6 --limit=10 2>&1 | tee sync_ekatalog_10.log

# 7. Verify 10 records were inserted
mysql -u root -p -e "
SELECT 'satkers' as table_name, COUNT(*) as row_count FROM satkers
UNION ALL
SELECT 'penyedias', COUNT(*) FROM penyedias
UNION ALL
SELECT 'tender_pengumuman_data', COUNT(*) FROM tender_pengumuman_data
UNION ALL
SELECT 'non_tender_pengumuman', COUNT(*) FROM non_tender_pengumuman
UNION ALL
SELECT 'ekatalog_v6_pakets', COUNT(*) FROM ekatalog_v6_pakets;
" sibaja-old

# Expected: Each table should show 10 rows (or fewer if API returned fewer)
```

### Phase 4: Inspect Sample Data
```bash
# 8. Check sample transformed data vs API (requires manual inspection)
mysql -u root -p -e "
SELECT kd_satker, nama_satker, tahun_aktif_json, sync_source, last_synced_at
FROM satkers
LIMIT 3;
" sibaja-old

# Verify:
# - kd_satker and nama_satker are populated
# - tahun_aktif_json contains array like [2024,2025,2026]
# - sync_source = 'inaproc_v1'
# - last_synced_at is recent timestamp
```

### Phase 5: Full Production Sync (2-4 hours depending on API response)
```bash
# 9. Run full sync without limit (this will take time)
# Monitor logs in another terminal: tail -f storage/logs/laravel.log

# Start syncs in screen/tmux session to prevent interruption
screen -S inaproc-sync

# Inside screen session:
php artisan inaproc:sync-rup 2>&1 | tee sync_rup_full.log
php artisan inaproc:sync-tender 2>&1 | tee sync_tender_full.log
php artisan inaproc:sync-non-tender 2>&1 | tee sync_nontender_full.log
php artisan inaproc:sync-ekatalog-v6 2>&1 | tee sync_ekatalog_full.log

# Detach from screen: Ctrl+A then D
# Reattach later: screen -r inaproc-sync
```

### Phase 6: Post-Sync Validation (30 minutes)
```bash
# 10. Verify final record counts
mysql -u root -p -e "
SELECT table_name, row_count FROM (
  SELECT 'satkers' as table_name, COUNT(*) as row_count FROM satkers
  UNION ALL
  SELECT 'penyedias', COUNT(*) FROM penyedias
  UNION ALL
  SELECT 'tender_pengumuman_data', COUNT(*) FROM tender_pengumuman_data
  UNION ALL
  SELECT 'tender_selesai_nilai_data', COUNT(*) FROM tender_selesai_nilai_data
  UNION ALL
  SELECT 'non_tender_pengumuman', COUNT(*) FROM non_tender_pengumuman
  UNION ALL
  SELECT 'non_tender_selesai', COUNT(*) FROM non_tender_selesai
  UNION ALL
  SELECT 'non_tender_contract', COUNT(*) FROM non_tender_contract
  UNION ALL
  SELECT 'non_tender_realisasi', COUNT(*) FROM non_tender_realisasi
  UNION ALL
  SELECT 'ekatalog_v6_pakets', COUNT(*) FROM ekatalog_v6_pakets
) stats
ORDER BY row_count DESC;
" sibaja-old

# Document these numbers for comparison with API totals
```

### Phase 7: Check for Errors and Warnings
```bash
# 11. Search logs for errors
grep -i "error" storage/logs/laravel.log | tail -20
grep -i "failed" storage/logs/laravel.log | tail -20
grep -i "missing required field" storage/logs/laravel.log | wc -l

# Review sync logs created during process
tail -50 sync_rup_full.log
tail -50 sync_tender_full.log
tail -50 sync_nontender_full.log
tail -50 sync_ekatalog_full.log
```

## Monitoring (Days 9-10, First 48 Hours)

### Real-Time Monitoring
```bash
# Terminal 1: Monitor error logs
tail -f storage/logs/laravel.log | grep -i "error\|exception\|failed"

# Terminal 2: Monitor rate limiter cache
php artisan tinker
>>> Cache::get('inaproc_ratelimit_minute_' . now()->format('Y-m-d H:i'))
>>> Cache::get('inaproc_ratelimit_hour_' . now()->format('Y-m-d H'))
```

### Daily Health Check (During scheduled syncs)
```bash
# Create health check script: scripts/check_sync_health.sh
#!/bin/bash

echo "=== INAPROC Sync Health Check ==="
echo "Timestamp: $(date)"
echo ""

# Check if tables have data
mysql -u root -p -e "
SELECT
  CONCAT(table_name, ': ', row_count, ' rows') as status
FROM (
  SELECT 'satkers' as table_name, COUNT(*) as row_count FROM satkers
  UNION ALL
  SELECT 'penyedias', COUNT(*) FROM penyedias
  UNION ALL
  SELECT 'tender_pengumuman_data', COUNT(*) FROM tender_pengumuman_data
  UNION ALL
  SELECT 'non_tender_pengumuman', COUNT(*) FROM non_tender_pengumuman
  UNION ALL
  SELECT 'ekatalog_v6_pakets', COUNT(*) FROM ekatalog_v6_pakets
) stats;
" sibaja-old

# Check last sync timestamps
mysql -u root -p -e "
SELECT
  'Last synced:',
  MAX(last_synced_at) as most_recent,
  MIN(last_synced_at) as oldest
FROM (
  SELECT last_synced_at FROM satkers WHERE last_synced_at IS NOT NULL
  UNION ALL
  SELECT last_synced_at FROM penyedias WHERE last_synced_at IS NOT NULL
  UNION ALL
  SELECT last_synced_at FROM tender_pengumuman_data WHERE last_synced_at IS NOT NULL
  UNION ALL
  SELECT last_synced_at FROM non_tender_pengumuman WHERE last_synced_at IS NOT NULL
  UNION ALL
  SELECT last_synced_at FROM ekatalog_v6_pakets WHERE last_synced_at IS NOT NULL
) timestamps;
" sibaja-old

# Check for any validation errors in logs
echo ""
echo "Recent errors:"
tail -5 storage/logs/laravel.log | grep -i "error"
```

## Rollback Procedure (If Issues Occur)

### Quick Rollback (< 1 hour to restore)
```bash
# 1. Stop any running syncs
pkill -f "artisan inaproc"

# 2. Restore from backup
mysql -u root -p < /var/backups/sibaja-old/$(date +%Y%m%d)/sibaja-old_backup_*.sql

# 3. Revert code changes
git revert HEAD

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
```

### Full Rollback with Investigation
```bash
# 1. Keep current problematic data for investigation
mysqldump -u root -p sibaja-old > /var/backups/sibaja-old/$(date +%Y%m%d)/failed_sync_$(date +%H%M%S).sql

# 2. Restore backup
mysql -u root -p < /var/backups/sibaja-old/$(date +%Y%m%d)/sibaja-old_backup_*.sql

# 3. Revert code
git revert HEAD
php artisan config:clear

# 4. Keep failed sync dump for debugging
ls -lh /var/backups/sibaja-old/$(date +%Y%m%d)/*.sql
```

## Scheduled Syncs (After Successful Deployment)

### Automatic Daily Syncs
The Kernel has been updated to run scheduled syncs daily:

```
02:00 - RUP Master Satker sync
02:15 - RUP Paket Penyedia sync
02:30 - Tender sync (all types)
02:45 - Non-Tender sync (all types)
03:00 - E-Katalog V6 sync
```

### Monitor Scheduled Syncs
```bash
# Check if scheduler is running (should be in cron or Supervisor)
ps aux | grep "artisan schedule:run"

# View last execution
tail -20 storage/logs/laravel.log | grep "sync"

# Manually trigger scheduler (for testing)
php artisan schedule:run
```

## Troubleshooting

### Command Timeout
**Problem**: Command times out on large datasets
**Solution**:
```bash
# Increase PHP timeout
php -d max_execution_time=600 artisan inaproc:sync-rup

# Split sync into smaller batches with --limit
php artisan inaproc:sync-rup --limit=1000
php artisan inaproc:sync-rup --limit=2000  # then another batch
```

### Rate Limit Hit
**Problem**: "Rate limit exceeded" errors
**Solution**:
```bash
# Check rate limit status
php artisan tinker
>>> $limiter = new App\Services\InaprocinaproRateLimiter();
>>> $limiter->getStatus()

# Wait for capacity or reset manually
>>> $limiter->reset()

# Resume sync after waiting
php artisan inaproc:sync-rup
```

### Missing Required Fields
**Problem**: "Missing required field: kd_satker"
**Solution**:
```bash
# Check API response format
# Verify all required fields are present in API response
# May need to update config/api.php required_fields if API changed

# Review logs for which fields are missing
grep "Missing required field" storage/logs/laravel.log | sort | uniq -c
```

### Database Foreign Key Errors
**Problem**: Foreign key constraint violation
**Solution**:
```bash
# Ensure parent table (Satker) synced FIRST
php artisan inaproc:sync-rup --type=satker

# Then child tables
php artisan inaproc:sync-tender
php artisan inaproc:sync-non-tender
php artisan inaproc:sync-ekatalog-v6
```

## Validation Queries

### Data Integrity Checks
```sql
-- Check for NULL values in required fields
SELECT 'satkers' as table_name, COUNT(*) as null_count
FROM satkers
WHERE kd_satker IS NULL OR nama_satker IS NULL
UNION ALL
SELECT 'penyedias', COUNT(*)
FROM penyedias
WHERE kd_rup IS NULL OR nama_paket IS NULL
UNION ALL
SELECT 'tender_pengumuman_data', COUNT(*)
FROM tender_pengumuman_data
WHERE kd_tender IS NULL OR nama_paket IS NULL;

-- Check sync metadata completeness
SELECT table_name,
  COUNT(*) as total_records,
  SUM(CASE WHEN sync_source IS NULL THEN 1 ELSE 0 END) as missing_sync_source,
  SUM(CASE WHEN last_synced_at IS NULL THEN 1 ELSE 0 END) as missing_last_synced_at
FROM (
  SELECT 'satkers' as table_name, sync_source, last_synced_at FROM satkers
  UNION ALL
  SELECT 'penyedias', sync_source, last_synced_at FROM penyedias
) t
GROUP BY table_name;

-- Check for duplicate records (should be 0)
SELECT table_name, COUNT(*) - COUNT(DISTINCT unique_key) as duplicate_count
FROM (
  SELECT 'satkers' as table_name, kd_satker as unique_key FROM satkers
  UNION ALL
  SELECT 'penyedias', kd_rup FROM penyedias
  UNION ALL
  SELECT 'tender_pengumuman_data', kd_tender FROM tender_pengumuman_data
) t
GROUP BY table_name;
```

## Success Criteria (Day 10)

- [ ] All 5 sync commands completed without errors
- [ ] Database row counts match expected totals
- [ ] All records have sync_source='inaproc_v1' and recent last_synced_at
- [ ] No NULL values in required fields
- [ ] No duplicate records
- [ ] Scheduled syncs running daily at correct times
- [ ] Logs show no errors or warnings
- [ ] Application features using this data function correctly
- [ ] 48-hour monitoring period completed without issues
- [ ] Team trained on manual re-sync procedures
