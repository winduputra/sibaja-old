<?php

namespace App\Http\Controllers;

use App\Models\Penyedia;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PdnUmkTrackerController extends Controller
{
    private const UKM_TARGET_NILAI = 1610639336358;
    private const UKM_TARGET_PAKET = 5505;
    private const PDN_TARGET_NILAI = 2652958142758;
    private const PDN_TARGET_PAKET = 5532;

    public function index(Request $request)
    {
        $availableYears = $this->getAvailableYears();
        $latestYear = $availableYears->first() ?? (int) date('Y');
        $year = (int) $request->get('tahun', $latestYear);

        $rupRows = $this->getRupRows($year);
        $realizedByMethod = $this->getRealizedRupCodesByMethod($year);

        $buckets = collect([
            $this->buildBucket(
                'PDN',
                'status_pdn',
                ['PDN'],
                $rupRows,
                $realizedByMethod,
                self::PDN_TARGET_NILAI,
                self::PDN_TARGET_PAKET
            ),
            $this->buildBucket(
                'UKM/UMK',
                'status_ukm',
                ['UKM', 'UMK'],
                $rupRows,
                $realizedByMethod,
                self::UKM_TARGET_NILAI,
                self::UKM_TARGET_PAKET
            ),
        ]);

        return view('monitoring.pdn-umk-tracker', compact('availableYears', 'year', 'buckets'));
    }

    private function getAvailableYears(): Collection
    {
        return $this->baseRupQuery()
            ->select('tahun_anggaran')
            ->distinct()
            ->pluck('tahun_anggaran')
            ->map(fn ($year) => (int) $year)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
    }

    private function getRupRows(int $year): Collection
    {
        return $this->baseRupQuery($year)
            ->select('kd_rup', 'pagu', 'status_pdn', 'status_ukm')
            ->get()
            ->map(function ($row) {
                return [
                    'kd_rup' => trim((string) $row->kd_rup),
                    'pagu' => (float) $row->pagu,
                    'status_pdn' => strtoupper(trim((string) $row->status_pdn)),
                    'status_ukm' => strtoupper(trim((string) $row->status_ukm)),
                ];
            })
            ->filter(fn ($row) => $row['kd_rup'] !== '')
            ->values();
    }

    private function baseRupQuery(?int $year = null)
    {
        $query = Penyedia::query()
            ->where('kd_klpd', 'D264')
            ->where('kd_satker', '!=', '350504')
            ->where('status_umumkan_rup', 'Terumumkan')
            ->where('status_aktif_rup', 1);

        if ($year) {
            $query->where('tahun_anggaran', $year);
        }

        return $query;
    }

    private function getRealizedRupCodesByMethod(int $year): Collection
    {
        return collect([
            'tender' => [
                'label' => 'Tender',
                'codes' => $this->getColumnRupCodes('tender_pengumuman_data', 'tahun', $year, 'kd_rup'),
            ],
            'non_tender' => [
                'label' => 'Non Tender',
                'codes' => $this->getColumnRupCodes('non_tender_pengumuman', 'tahun_anggaran', $year, 'kd_rup'),
            ],
            'e_purchasing' => [
                'label' => 'E-Purchasing',
                'codes' => $this->getEkatalogRupCodes($year),
            ],
        ]);
    }

    private function getColumnRupCodes(string $table, string $yearColumn, int $year, string $codeColumn): Collection
    {
        return $this->normalizeRupCodes(
            DB::table($table)
                ->where($yearColumn, $year)
                ->whereNotNull($codeColumn)
                ->where($codeColumn, '!=', '')
                ->distinct()
                ->pluck($codeColumn)
        );
    }

    private function getEkatalogRupCodes(int $year): Collection
    {
        $codes = collect();

        if (Schema::hasTable('ekatalog_v6_pakets')) {
            if (Schema::hasColumn('ekatalog_v6_pakets', 'rup_code')) {
                $codes = $codes->merge($this->getColumnRupCodes('ekatalog_v6_pakets', 'tahun_anggaran', $year, 'rup_code'));
            }

            if (Schema::hasColumn('ekatalog_v6_pakets', 'kd_rup')) {
                $codes = $codes->merge($this->getColumnRupCodes('ekatalog_v6_pakets', 'tahun_anggaran', $year, 'kd_rup'));
            }
        }

        if (Schema::hasTable('ekatalog_v5_pakets')) {
            $codes = $codes->merge($this->getColumnRupCodes('ekatalog_v5_pakets', 'tahun_anggaran', $year, 'kd_rup'));
        }

        return $this->normalizeRupCodes($codes);
    }

    private function normalizeRupCodes(Collection $codes): Collection
    {
        return $codes
            ->map(fn ($code) => trim((string) $code))
            ->filter(fn ($code) => $code !== '')
            ->unique()
            ->values();
    }

    private function buildBucket(
        string $label,
        string $statusField,
        array $statusValues,
        Collection $rupRows,
        Collection $realizedByMethod,
        int $targetNilai,
        int $targetPaket
    ): array {
        $bucketRows = $rupRows
            ->filter(fn ($row) => in_array($row[$statusField], $statusValues, true))
            ->values();

        $rupValue = $bucketRows->sum('pagu');
        $bucketByCode = $bucketRows->keyBy('kd_rup');
        $bucketCodes = $bucketByCode->keys();
        $rupPackageCount = $bucketCodes->count();

        $methodRows = $realizedByMethod->map(function ($method) use ($bucketCodes, $bucketByCode) {
            $matchedCodes = $method['codes']->intersect($bucketCodes)->values();

            return [
                'label' => $method['label'],
                'paket' => $matchedCodes->count(),
                'nilai' => $this->sumPaguByCodes($matchedCodes, $bucketByCode),
            ];
        })->values();

        $realizedCodes = $realizedByMethod
            ->pluck('codes')
            ->reduce(fn (Collection $carry, Collection $codes) => $carry->merge($codes), collect())
            ->unique()
            ->intersect($bucketCodes)
            ->values();

        $realizedValue = $this->sumPaguByCodes($realizedCodes, $bucketByCode);
        $realizedPackageCount = $realizedCodes->count();

        return [
            'label' => $label,
            'rup' => [
                'nilai' => $rupValue,
                'paket' => $rupPackageCount,
            ],
            'target' => [
                'nilai' => $targetNilai,
                'paket' => $targetPaket,
            ],
            'delta' => [
                'nilai' => $rupValue - $targetNilai,
                'paket' => $rupPackageCount - $targetPaket,
            ],
            'valid' => [
                'nilai' => (int) round($rupValue) === $targetNilai,
                'paket' => $rupPackageCount === $targetPaket,
            ],
            'realized' => [
                'nilai' => $realizedValue,
                'paket' => $realizedPackageCount,
                'persen_nilai' => $this->percent($realizedValue, $rupValue),
                'persen_paket' => $this->percent($realizedPackageCount, $rupPackageCount),
            ],
            'methods' => $methodRows,
        ];
    }

    private function sumPaguByCodes(Collection $codes, Collection $bucketByCode): float
    {
        return (float) $codes->sum(function ($code) use ($bucketByCode) {
            $row = $bucketByCode->get($code);

            return $row['pagu'] ?? 0;
        });
    }

    private function percent(float $value, float $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($value / $total) * 100, 2);
    }
}
