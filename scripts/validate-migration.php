<?php

/**
 * INAPROC API Migration - Data Validation & Monitoring Script
 *
 * Usage: php artisan tinker < validation.php
 * Or: php scripts/validate-migration.php
 */

use Illuminate\Support\Facades\DB;

echo "\n=== INAPROC API Migration - Data Validation Report ===\n";
echo "Generated: " . now()->toDateTimeString() . "\n\n";

// 1. Record Counts
echo "1. TABLE RECORD COUNTS\n";
echo str_repeat("-", 50) . "\n";

$tables = [
    'satkers' => 'Satker (Master)',
    'penyedias' => 'RUP Paket Penyedia',
    'tender_pengumuman_data' => 'Tender Pengumuman',
    'tender_selesai_nilai_data' => 'Tender Selesai Nilai',
    'non_tender_pengumuman' => 'Non-Tender Pengumuman',
    'non_tender_selesai' => 'Non-Tender Selesai',
    'non_tender_contract' => 'Non-Tender Contract',
    'non_tender_realisasi' => 'Non-Tender Realisasi',
    'ekatalog_v6_pakets' => 'E-Katalog V6',
];

$totalRecords = 0;
foreach ($tables as $table => $label) {
    $count = DB::table($table)->count();
    $totalRecords += $count;
    $status = $count > 0 ? '✓' : '✗';
    echo sprintf("%-40s %6d records  %s\n", $label, $count, $status);
}

echo str_repeat("-", 50) . "\n";
echo sprintf("%-40s %6d records\n\n", "TOTAL", $totalRecords);

// 2. Data Completeness Check
echo "2. DATA COMPLETENESS (Required Fields)\n";
echo str_repeat("-", 50) . "\n";

$completenessChecks = [
    'satkers' => ['kd_satker', 'nama_satker'],
    'penyedias' => ['kd_rup', 'nama_paket', 'kd_satker'],
    'tender_pengumuman_data' => ['kd_tender', 'nama_paket', 'pagu'],
    'tender_selesai_nilai_data' => ['kd_tender', 'nilai_kontrak'],
    'non_tender_pengumuman' => ['kd_nontender', 'nama_paket', 'pagu'],
    'non_tender_selesai' => ['kd_nontender', 'nilai_kontrak'],
    'non_tender_contract' => ['kd_nontender', 'nilai_kontrak'],
    'non_tender_realisasi' => ['kd_nontender_pct', 'nilai_realisasi'],
    'ekatalog_v6_pakets' => ['order_id', 'total'],
];

foreach ($completenessChecks as $table => $requiredFields) {
    $label = array_search($table, array_keys($tables)) ? $tables[$table] : $table;

    foreach ($requiredFields as $field) {
        $nullCount = DB::table($table)->whereNull($field)->count();
        $totalCount = DB::table($table)->count();

        if ($totalCount === 0) {
            echo sprintf("  %-35s %-20s SKIPPED (0 records)\n", $label, $field);
            continue;
        }

        $percent = $totalCount > 0 ? ($nullCount / $totalCount) * 100 : 0;
        $status = $nullCount === 0 ? '✓ PASS' : sprintf('✗ FAIL (%d NULL / %d total)', $nullCount, $totalCount);
        echo sprintf("  %-35s %-20s %s\n", $label, $field, $status);
    }
}

echo "\n";

// 3. Sync Metadata Check
echo "3. SYNC METADATA STATUS\n";
echo str_repeat("-", 50) . "\n";

$syncTables = array_filter($tables, function($t) {
    return in_array($t, ['satkers', 'penyedias', 'tender_pengumuman_data', 'non_tender_pengumuman', 'ekatalog_v6_pakets']);
});

foreach ($syncTables as $table => $label) {
    $recordsWithMetadata = DB::table($table)
        ->whereNotNull('sync_source')
        ->whereNotNull('last_synced_at')
        ->count();

    $totalCount = DB::table($table)->count();

    if ($totalCount === 0) {
        echo sprintf("%-40s SKIPPED (0 records)\n", $label);
        continue;
    }

    $percent = ($recordsWithMetadata / $totalCount) * 100;
    $status = $percent === 100 ? '✓ COMPLETE' : '✗ INCOMPLETE';
    echo sprintf("%-40s %3.0f%% with metadata  %s\n", $label, $percent, $status);
}

echo "\n";

// 4. Last Sync Timestamps
echo "4. RECENT SYNC ACTIVITY\n";
echo str_repeat("-", 50) . "\n";

$recentSyncs = DB::table('satkers')
    ->whereNotNull('last_synced_at')
    ->orderByDesc('last_synced_at')
    ->limit(1)
    ->get(['table_name' => 'satkers', 'last_synced_at']);

// Find most recent sync across all tables
$mostRecentSync = null;
foreach ($syncTables as $table => $label) {
    $record = DB::table($table)
        ->whereNotNull('last_synced_at')
        ->orderByDesc('last_synced_at')
        ->first();

    if ($record && (!$mostRecentSync || $record->last_synced_at > $mostRecentSync->last_synced_at)) {
        $mostRecentSync = $record;
    }
}

if ($mostRecentSync) {
    $minutesAgo = now()->diffInMinutes($mostRecentSync->last_synced_at);
    echo sprintf("Most recent sync: %s (%d minutes ago)\n\n",
        $mostRecentSync->last_synced_at->toDateTimeString(), $minutesAgo);
} else {
    echo "No sync metadata found\n\n";
}

// 5. Data Quality Issues
echo "5. DATA QUALITY ALERTS\n";
echo str_repeat("-", 50) . "\n";

$hasIssues = false;

// Check for duplicate unique keys
foreach ($completenessChecks as $table => $fields) {
    if (empty(DB::table($table)->count())) {
        continue;
    }

    $uniqueField = $fields[0] ?? 'id';
    $duplicates = DB::table($table)
        ->select($uniqueField)
        ->groupBy($uniqueField)
        ->havingRaw('COUNT(*) > 1')
        ->count();

    if ($duplicates > 0) {
        echo sprintf("✗ %s: %d duplicate unique keys\n", $tables[$table] ?? $table, $duplicates);
        $hasIssues = true;
    }
}

// Check for orphaned foreign keys (if applicable)
$nullSatkerKeys = DB::table('penyedias')->whereNull('kd_satker')->count();
if ($nullSatkerKeys > 0) {
    echo sprintf("✗ Penyedia: %d records with NULL kd_satker\n", $nullSatkerKeys);
    $hasIssues = true;
}

if (!$hasIssues) {
    echo "✓ No data quality issues detected\n";
}

echo "\n";

// 6. Rate Limiter Cache Status
echo "6. RATE LIMITER STATUS\n";
echo str_repeat("-", 50) . "\n";

$now = now();
$minuteKey = 'inaproc_ratelimit_minute_' . $now->format('Y-m-d H:i');
$hourKey = 'inaproc_ratelimit_hour_' . $now->format('Y-m-d H');

$minuteCount = Cache::get($minuteKey, 0);
$hourCount = Cache::get($hourKey, 0);

echo sprintf("Current minute requests: %d / 1000\n", $minuteCount);
echo sprintf("Current hour requests:   %d / 5000\n", $hourCount);
echo "\n";

// 7. Summary
echo "7. SUMMARY\n";
echo str_repeat("-", 50) . "\n";

if ($totalRecords === 0) {
    echo "⚠ WARNING: No data found. Sync may not have run.\n";
} elseif ($totalRecords < 100) {
    echo "⚠ WARNING: Low record count. Data may be incomplete.\n";
} else {
    echo "✓ Migration appears successful\n";
}

echo sprintf("\nTotal records in system: %d\n", $totalRecords);
echo sprintf("Report generated: %s\n\n", now()->toDateTimeString());

echo "=== End of Report ===\n\n";
