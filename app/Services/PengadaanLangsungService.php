<?php

namespace App\Services;

use Exception;

class PengadaanLangsungService
{
    private const KODE_KLPD = 'D264';
    private const LIMIT = 1000;
    private const SUPPORTED_YEARS = [2024, 2025, 2026];
    private const DEFAULT_YEAR = 2026;
    private const PLANNING_ENDPOINT = 'tender/pencatatan-non-tender';
    private const REALIZATION_ENDPOINT = 'tender/pencatatan-non-tender-realisasi';

    private InaprocinaproApiClient $client;

    public function __construct(InaprocinaproApiClient $client)
    {
        $this->client = $client;
    }

    public function report(int $tahun): array
    {
        $selectedYear = $this->normalizeYear($tahun);

        try {
            $planningRows = $this->fetchItems(self::PLANNING_ENDPOINT, $selectedYear);
            $realizationRows = $this->fetchItems(self::REALIZATION_ENDPOINT, $selectedYear);
            $realizationByCode = $this->realizationByCode($realizationRows);
            $rows = $this->mapRows($planningRows, $realizationByCode);

            return [
                'selectedYear' => $selectedYear,
                'years' => self::SUPPORTED_YEARS,
                'rows' => $rows,
                'totalPagu' => array_sum(array_column($rows, 'nilai_pagu')),
                'totalFinal' => array_sum(array_column($rows, 'nilai_final')),
                'apiError' => null,
            ];
        } catch (Exception $exception) {
            return [
                'selectedYear' => $selectedYear,
                'years' => self::SUPPORTED_YEARS,
                'rows' => [],
                'totalPagu' => 0,
                'totalFinal' => 0,
                'apiError' => $exception->getMessage(),
            ];
        }
    }

    public function normalizeYear(int $tahun): int
    {
        return in_array($tahun, self::SUPPORTED_YEARS, true) ? $tahun : self::DEFAULT_YEAR;
    }

    private function fetchItems(string $endpoint, int $tahun): array
    {
        $response = $this->client->request($endpoint, [
            'kode_klpd' => config('api.inaproc.kode_klpd', self::KODE_KLPD),
            'tahun' => $tahun,
            'limit' => self::LIMIT,
        ]);

        $items = $response['data'] ?? $response['items'] ?? $response;

        return is_array($items) ? $items : [];
    }

    private function realizationByCode(array $realizationRows): array
    {
        $indexedRows = [];

        foreach ($realizationRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = trim((string) ($row['kd_nontender_pct'] ?? ''));

            if ($code === '') {
                continue;
            }

            $existingRow = $indexedRows[$code] ?? [
                'nama_penyedia' => '',
                'alamat_penyedia' => '',
                'total_realisasi' => 0.0,
            ];

            $indexedRows[$code] = [
                'nama_penyedia' => $existingRow['nama_penyedia'] ?: (string) ($row['nama_penyedia'] ?? ''),
                'alamat_penyedia' => $existingRow['alamat_penyedia'] ?: (string) ($row['alamat_penyedia'] ?? ''),
                'total_realisasi' => $existingRow['total_realisasi'] + $this->realizationValue($row),
            ];
        }

        return $indexedRows;
    }

    private function mapRows(array $planningRows, array $realizationByCode): array
    {
        $rows = [];

        foreach ($planningRows as $planningRow) {
            if (!$this->shouldShowPlanningRow($planningRow)) {
                continue;
            }

            $code = trim((string) ($planningRow['kd_nontender_pct'] ?? ''));
            $realizationRow = $realizationByCode[$code] ?? [
                'nama_penyedia' => '',
                'alamat_penyedia' => '',
                'total_realisasi' => 0.0,
            ];

            $rows[] = [
                'perangkat_daerah' => (string) ($planningRow['nama_satker'] ?? ''),
                'nama_paket' => (string) ($planningRow['nama_paket'] ?? ''),
                'kegiatan' => '',
                'penyedia' => $realizationRow['nama_penyedia'],
                'alamat_penyedia' => $realizationRow['alamat_penyedia'],
                'nilai_pagu' => (float) ($planningRow['pagu'] ?? 0),
                'nilai_final' => (float) ($realizationRow['total_realisasi'] ?: ($planningRow['total_realisasi'] ?? 0)),
            ];
        }

        return $rows;
    }

    private function shouldShowPlanningRow($planningRow): bool
    {
        if (!is_array($planningRow)) {
            return false;
        }

        return ($planningRow['mtd_pemilihan'] ?? '') === 'Pengadaan Langsung'
            && ($planningRow['status_nontender_pct_ket'] ?? '') === 'Paket Selesai';
    }

    private function realizationValue(array $row): float
    {
        foreach (['total_realisasi', 'nilai_realisasi'] as $key) {
            if (isset($row[$key]) && is_numeric($row[$key])) {
                return (float) $row[$key];
            }
        }

        return 0.0;
    }
}
