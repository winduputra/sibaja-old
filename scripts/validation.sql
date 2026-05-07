-- INAPROC API Migration - SQL Validation Queries
-- Usage: Copy and paste into MySQL client or run with: mysql -u root sibaja-old < validation.sql

-- =====================================================
-- 1. RECORD COUNTS PER TABLE
-- =====================================================
SELECT
    'satkers' AS table_name,
    COUNT(*) AS row_count
FROM satkers
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
ORDER BY row_count DESC;


-- =====================================================
-- 2. MISSING REQUIRED FIELDS
-- =====================================================
SELECT
    'satkers' AS table_name,
    'kd_satker' AS field_name,
    COUNT(*) AS null_count,
    (SELECT COUNT(*) FROM satkers) AS total_count
FROM satkers
WHERE kd_satker IS NULL

UNION ALL

SELECT 'satkers', 'nama_satker', COUNT(*), (SELECT COUNT(*) FROM satkers)
FROM satkers WHERE nama_satker IS NULL

UNION ALL

SELECT 'penyedias', 'kd_rup', COUNT(*), (SELECT COUNT(*) FROM penyedias)
FROM penyedias WHERE kd_rup IS NULL

UNION ALL

SELECT 'penyedias', 'nama_paket', COUNT(*), (SELECT COUNT(*) FROM penyedias)
FROM penyedias WHERE nama_paket IS NULL

UNION ALL

SELECT 'tender_pengumuman_data', 'kd_tender', COUNT(*), (SELECT COUNT(*) FROM tender_pengumuman_data)
FROM tender_pengumuman_data WHERE kd_tender IS NULL

UNION ALL

SELECT 'non_tender_pengumuman', 'kd_nontender', COUNT(*), (SELECT COUNT(*) FROM non_tender_pengumuman)
FROM non_tender_pengumuman WHERE kd_nontender IS NULL

UNION ALL

SELECT 'ekatalog_v6_pakets', 'order_id', COUNT(*), (SELECT COUNT(*) FROM ekatalog_v6_pakets)
FROM ekatalog_v6_pakets WHERE order_id IS NULL

HAVING null_count > 0
ORDER BY null_count DESC;


-- =====================================================
-- 3. SYNC METADATA COMPLETENESS
-- =====================================================
SELECT
    'satkers' AS table_name,
    COUNT(*) AS total_records,
    SUM(CASE WHEN sync_source IS NOT NULL THEN 1 ELSE 0 END) AS with_sync_source,
    SUM(CASE WHEN last_synced_at IS NOT NULL THEN 1 ELSE 0 END) AS with_last_synced_at,
    SUM(CASE WHEN sync_source IS NOT NULL AND last_synced_at IS NOT NULL THEN 1 ELSE 0 END) AS complete
FROM satkers

UNION ALL

SELECT 'penyedias', COUNT(*),
    SUM(CASE WHEN sync_source IS NOT NULL THEN 1 ELSE 0 END),
    SUM(CASE WHEN last_synced_at IS NOT NULL THEN 1 ELSE 0 END),
    SUM(CASE WHEN sync_source IS NOT NULL AND last_synced_at IS NOT NULL THEN 1 ELSE 0 END)
FROM penyedias

UNION ALL

SELECT 'tender_pengumuman_data', COUNT(*),
    SUM(CASE WHEN sync_source IS NOT NULL THEN 1 ELSE 0 END),
    SUM(CASE WHEN last_synced_at IS NOT NULL THEN 1 ELSE 0 END),
    SUM(CASE WHEN sync_source IS NOT NULL AND last_synced_at IS NOT NULL THEN 1 ELSE 0 END)
FROM tender_pengumuman_data

UNION ALL

SELECT 'non_tender_pengumuman', COUNT(*),
    SUM(CASE WHEN sync_source IS NOT NULL THEN 1 ELSE 0 END),
    SUM(CASE WHEN last_synced_at IS NOT NULL THEN 1 ELSE 0 END),
    SUM(CASE WHEN sync_source IS NOT NULL AND last_synced_at IS NOT NULL THEN 1 ELSE 0 END)
FROM non_tender_pengumuman

UNION ALL

SELECT 'ekatalog_v6_pakets', COUNT(*),
    SUM(CASE WHEN sync_source IS NOT NULL THEN 1 ELSE 0 END),
    SUM(CASE WHEN last_synced_at IS NOT NULL THEN 1 ELSE 0 END),
    SUM(CASE WHEN sync_source IS NOT NULL AND last_synced_at IS NOT NULL THEN 1 ELSE 0 END)
FROM ekatalog_v6_pakets;


-- =====================================================
-- 4. LAST SYNC TIMESTAMP
-- =====================================================
SELECT
    'Most Recent Sync' AS metric,
    MAX(last_synced_at) AS timestamp,
    TIMEDIFF(NOW(), MAX(last_synced_at)) AS age
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


-- =====================================================
-- 5. DUPLICATE CHECK (should be 0)
-- =====================================================
SELECT
    'satkers' AS table_name,
    COUNT(*) AS total_records,
    COUNT(DISTINCT kd_satker) AS unique_keys,
    COUNT(*) - COUNT(DISTINCT kd_satker) AS duplicate_count
FROM satkers

UNION ALL

SELECT 'penyedias', COUNT(*), COUNT(DISTINCT kd_rup), COUNT(*) - COUNT(DISTINCT kd_rup)
FROM penyedias

UNION ALL

SELECT 'tender_pengumuman_data', COUNT(*), COUNT(DISTINCT kd_tender), COUNT(*) - COUNT(DISTINCT kd_tender)
FROM tender_pengumuman_data

UNION ALL

SELECT 'non_tender_pengumuman', COUNT(*), COUNT(DISTINCT kd_nontender), COUNT(*) - COUNT(DISTINCT kd_nontender)
FROM non_tender_pengumuman

UNION ALL

SELECT 'ekatalog_v6_pakets', COUNT(*), COUNT(DISTINCT order_id), COUNT(*) - COUNT(DISTINCT order_id)
FROM ekatalog_v6_pakets;


-- =====================================================
-- 6. DATA SOURCE VERIFICATION
-- =====================================================
SELECT
    'satkers' AS table_name,
    sync_source,
    COUNT(*) AS record_count,
    MIN(last_synced_at) AS first_sync,
    MAX(last_synced_at) AS last_sync
FROM satkers
WHERE sync_source IS NOT NULL
GROUP BY sync_source

UNION ALL

SELECT 'penyedias', sync_source, COUNT(*), MIN(last_synced_at), MAX(last_synced_at)
FROM penyedias WHERE sync_source IS NOT NULL GROUP BY sync_source

UNION ALL

SELECT 'tender_pengumuman_data', sync_source, COUNT(*), MIN(last_synced_at), MAX(last_synced_at)
FROM tender_pengumuman_data WHERE sync_source IS NOT NULL GROUP BY sync_source

UNION ALL

SELECT 'non_tender_pengumuman', sync_source, COUNT(*), MIN(last_synced_at), MAX(last_synced_at)
FROM non_tender_pengumuman WHERE sync_source IS NOT NULL GROUP BY sync_source

UNION ALL

SELECT 'ekatalog_v6_pakets', sync_source, COUNT(*), MIN(last_synced_at), MAX(last_synced_at)
FROM ekatalog_v6_pakets WHERE sync_source IS NOT NULL GROUP BY sync_source;


-- =====================================================
-- 7. PUBLIC DATA SAMPLES (verify transformation)
-- =====================================================

-- Sample Satker records
SELECT kd_satker, nama_satker, tahun_aktif_json, sync_source, last_synced_at
FROM satkers
LIMIT 3;

-- Sample Tender records
SELECT kd_tender, nama_paket, pagu, nilai_kontrak, sync_source, last_synced_at
FROM tender_pengumuman_data
LIMIT 3;

-- Sample Non-Tender records
SELECT kd_nontender, nama_paket, pagu, nilai_kontrak, sync_source, last_synced_at
FROM non_tender_pengumuman
LIMIT 3;

-- Sample E-Katalog records
SELECT order_id, nama_satker, total, total_qty, sync_source, last_synced_at
FROM ekatalog_v6_pakets
LIMIT 3;
