#!/bin/bash

# INAPROC API Migration - Quick Validation Script
# Usage: bash scripts/validate-quick.sh
# Or: chmod +x scripts/validate-quick.sh && ./scripts/validate-quick.sh

set -e

DB_USER="root"
DB_PASS=""
DB_NAME="sibaja-old"

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║       INAPROC API Migration - Quick Validation Check               ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""
echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to run MySQL query
run_query() {
    if [ -z "$DB_PASS" ]; then
        mysql -u "$DB_USER" -e "$1" "$DB_NAME" 2>/dev/null
    else
        mysql -u "$DB_USER" -p"$DB_PASS" -e "$1" "$DB_NAME" 2>/dev/null
    fi
}

# Check 1: Database Connection
echo -e "${BLUE}1. Database Connection${NC}"
if run_query "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Connected to database: $DB_NAME${NC}"
else
    echo -e "${RED}✗ Failed to connect to database${NC}"
    exit 1
fi
echo ""

# Check 2: Table Record Counts
echo -e "${BLUE}2. Record Counts${NC}"

declare -A tables=(
    ["satkers"]="Satker"
    ["penyedias"]="RUP Penyedia"
    ["tender_pengumuman_data"]="Tender Pengumuman"
    ["non_tender_pengumuman"]="Non-Tender Pengumuman"
    ["ekatalog_v6_pakets"]="E-Katalog V6"
)

total=0
for table in "${!tables[@]}"; do
    count=$(run_query "SELECT COUNT(*) FROM $table;" | tail -1)
    total=$((total + count))

    if [ "$count" -gt 0 ]; then
        printf "${GREEN}✓${NC} %-35s %6d records\n" "${tables[$table]}" "$count"
    else
        printf "${YELLOW}○${NC} %-35s %6d records\n" "${tables[$table]}" "$count"
    fi
done

echo ""
printf "  Total records imported: %d\n" "$total"

if [ "$total" -eq 0 ]; then
    echo -e "${YELLOW}⚠ Warning: No data found. Sync may not have run yet.${NC}"
fi
echo ""

# Check 3: Data Completeness (sample check)
echo -e "${BLUE}3. Data Quality (Sample Checks)${NC}"

null_satkers=$(run_query "SELECT COUNT(*) FROM satkers WHERE kd_satker IS NULL;" | tail -1)
if [ "$null_satkers" -eq 0 ]; then
    echo -e "${GREEN}✓ Satkers: No missing kd_satker${NC}"
else
    echo -e "${RED}✗ Satkers: $null_satkers records with NULL kd_satker${NC}"
fi

null_penyedias=$(run_query "SELECT COUNT(*) FROM penyedias WHERE kd_rup IS NULL;" | tail -1)
if [ "$null_penyedias" -eq 0 ]; then
    echo -e "${GREEN}✓ Penyedias: No missing kd_rup${NC}"
else
    echo -e "${RED}✗ Penyedias: $null_penyedias records with NULL kd_rup${NC}"
fi

null_tender=$(run_query "SELECT COUNT(*) FROM tender_pengumuman_data WHERE kd_tender IS NULL;" | tail -1)
if [ "$null_tender" -eq 0 ]; then
    echo -e "${GREEN}✓ Tender: No missing kd_tender${NC}"
else
    echo -e "${RED}✗ Tender: $null_tender records with NULL kd_tender${NC}"
fi

echo ""

# Check 4: Sync Metadata
echo -e "${BLUE}4. Sync Metadata${NC}"

synced_satkers=$(run_query "SELECT COUNT(*) FROM satkers WHERE sync_source = 'inaproc_v1';" | tail -1)
total_satkers=$(run_query "SELECT COUNT(*) FROM satkers;" | tail -1)

if [ "$total_satkers" -gt 0 ]; then
    percentage=$((synced_satkers * 100 / total_satkers))
    if [ "$percentage" -eq 100 ]; then
        echo -e "${GREEN}✓ Satkers: $synced_satkers/$total_satkers (100%) synced from INAPROC${NC}"
    else
        echo -e "${YELLOW}○ Satkers: $synced_satkers/$total_satkers ($percentage%) synced from INAPROC${NC}"
    fi
else
    echo -e "${YELLOW}○ Satkers: No records to check${NC}"
fi

echo ""

# Check 5: Last Sync Time
echo -e "${BLUE}5. Recent Activity${NC}"

last_sync=$(run_query "SELECT MAX(last_synced_at) FROM (
  SELECT last_synced_at FROM satkers
  UNION ALL SELECT last_synced_at FROM penyedias
  UNION ALL SELECT last_synced_at FROM tender_pengumuman_data
  UNION ALL SELECT last_synced_at FROM non_tender_pengumuman
  UNION ALL SELECT last_synced_at FROM ekatalog_v6_pakets
) t WHERE last_synced_at IS NOT NULL;" | tail -1)

if [ -z "$last_sync" ] || [ "$last_sync" = "NULL" ]; then
    echo -e "${YELLOW}○ No sync activity recorded${NC}"
else
    echo -e "${GREEN}✓ Last sync: $last_sync${NC}"
fi

echo ""

# Check 6: Summary
echo -e "${BLUE}6. Summary${NC}"

if [ "$total" -eq 0 ]; then
    echo -e "${YELLOW}⚠ Status: Ready for first sync${NC}"
    echo "   Run: php artisan inaproc:sync-rup"
elif [ "$total" -lt 100 ]; then
    echo -e "${YELLOW}⚠ Status: Partial import detected${NC}"
else
    echo -e "${GREEN}✓ Status: Migration successful${NC}"
fi

echo ""
echo "═══════════════════════════════════════════════════════════════════════"
echo ""
