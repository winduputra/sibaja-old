<?php

namespace App\Services;

use App\Models\RekapitulasiNasional;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class RekapitulasiNasionalData
{
    public static function heading(): string
    {
        return 'REALISASI PENGADAAN PROVINSI SE-INDONESIA';
    }

    public static function subtitle(): string
    {
        $latestSync = static::latestSyncDate();

        if ($latestSync === null) {
            return 'PER TANGGAL 4 MEI 2026';
        }

        return 'PER TANGGAL ' . static::formatIndonesianDate($latestSync);
    }

    public static function rows(): array
    {
        $rows = static::databaseRows();

        if (!empty($rows)) {
            return $rows;
        }

        return static::sortRowsByTotalPercentage(static::fallbackRows());
    }

    public static function totals(): array
    {
        $rows = static::databaseRows(false);

        if (!empty($rows)) {
            $totals = RekapitulasiNasional::query()->selectRaw('
                SUM(penyedia_realisasi) as penyedia_realisasi,
                SUM(penyedia_perencanaan) as penyedia_perencanaan,
                SUM(swakelola_realisasi) as swakelola_realisasi,
                SUM(swakelola_perencanaan) as swakelola_perencanaan,
                SUM(total_realisasi) as total_realisasi,
                SUM(total_perencanaan) as total_perencanaan
            ')->first();

            $penyediaPersentase = static::percentage((float) $totals->penyedia_realisasi, (float) $totals->penyedia_perencanaan);
            $swakelolaPersentase = static::percentage((float) $totals->swakelola_realisasi, (float) $totals->swakelola_perencanaan);
            $totalPersentase = static::percentage((float) $totals->total_realisasi, (float) $totals->total_perencanaan);

            return [
                static::formatMoney($totals->penyedia_realisasi),
                static::formatMoney($totals->penyedia_perencanaan),
                static::formatPercent($penyediaPersentase),
                static::formatMoney($totals->swakelola_realisasi),
                static::formatMoney($totals->swakelola_perencanaan),
                static::formatPercent($swakelolaPersentase),
                static::formatMoney($totals->total_realisasi),
                static::formatMoney($totals->total_perencanaan),
                static::formatPercent($totalPersentase),
            ];
        }

        return static::fallbackTotals();
    }

    private static function databaseRows(bool $formatted = true): array
    {
        if (!Schema::hasTable('rekapitulasi_nasional')) {
            return [];
        }

        $records = RekapitulasiNasional::query()
            ->orderByDesc('total_persentase')
            ->orderBy('province_name')
            ->get();

        if ($records->isEmpty()) {
            return [];
        }

        if (!$formatted) {
            return $records->all();
        }

        return $records->map(function (RekapitulasiNasional $record): array {
            return [
                $record->province_name,
                static::formatMoney($record->penyedia_realisasi),
                static::formatMoney($record->penyedia_perencanaan),
                static::formatPercent($record->penyedia_persentase),
                static::formatMoney($record->swakelola_realisasi),
                static::formatMoney($record->swakelola_perencanaan),
                static::formatPercent($record->swakelola_persentase),
                static::formatMoney($record->total_realisasi),
                static::formatMoney($record->total_perencanaan),
                static::formatPercent($record->total_persentase),
            ];
        })->all();
    }

    private static function fallbackRows(): array
    {
        return [
            ['ACEH', '1.012.350.000.000', '2.374.710.000.000', '42,63%', '382.420.000.000', '945.280.000.000', '40,46%', '1.394.770.000.000', '3.319.990.000.000', '42,01%'],
            ['SUMATERA UTARA', '1.856.275.000.000', '4.721.890.000.000', '39,31%', '714.660.000.000', '1.689.430.000.000', '42,30%', '2.570.935.000.000', '6.411.320.000.000', '40,10%'],
            ['SUMATERA BARAT', '943.815.000.000', '2.185.600.000.000', '43,18%', '366.920.000.000', '823.770.000.000', '44,54%', '1.310.735.000.000', '3.009.370.000.000', '43,55%'],
            ['RIAU', '1.221.480.000.000', '3.246.870.000.000', '37,62%', '488.745.000.000', '1.098.320.000.000', '44,50%', '1.710.225.000.000', '4.345.190.000.000', '39,36%'],
            ['JAMBI', '765.935.000.000', '1.926.480.000.000', '39,76%', '298.610.000.000', '731.240.000.000', '40,84%', '1.064.545.000.000', '2.657.720.000.000', '40,05%'],
            ['SUMATERA SELATAN', '1.345.710.000.000', '3.814.920.000.000', '35,27%', '536.890.000.000', '1.267.430.000.000', '42,36%', '1.882.600.000.000', '5.082.350.000.000', '37,04%'],
            ['BENGKULU', '512.480.000.000', '1.294.760.000.000', '39,58%', '206.225.000.000', '508.940.000.000', '40,52%', '718.705.000.000', '1.803.700.000.000', '39,85%'],
            ['LAMPUNG', '1.092.780.000.000', '2.886.410.000.000', '37,86%', '429.335.000.000', '1.024.615.000.000', '41,90%', '1.522.115.000.000', '3.911.025.000.000', '38,92%'],
            ['KEPULAUAN BANGKA BELITUNG', '463.775.000.000', '1.104.330.000.000', '42,00%', '184.540.000.000', '416.880.000.000', '44,27%', '648.315.000.000', '1.521.210.000.000', '42,62%'],
            ['KEPULAUAN RIAU', '689.315.000.000', '1.802.640.000.000', '38,24%', '267.880.000.000', '653.110.000.000', '41,02%', '957.195.000.000', '2.455.750.000.000', '38,98%'],
            ['DKI JAKARTA', '4.286.900.000.000', '9.874.550.000.000', '43,41%', '1.406.825.000.000', '3.206.480.000.000', '43,87%', '5.693.725.000.000', '13.081.030.000.000', '43,53%'],
            ['JAWA BARAT', '5.942.770.000.000', '14.655.940.000.000', '40,55%', '2.194.330.000.000', '5.172.680.000.000', '42,42%', '8.137.100.000.000', '19.828.620.000.000', '41,04%'],
            ['JAWA TENGAH', '4.734.815.000.000', '11.820.430.000.000', '40,06%', '1.876.420.000.000', '4.347.290.000.000', '43,16%', '6.611.235.000.000', '16.167.720.000.000', '40,89%'],
            ['DI YOGYAKARTA', '845.340.000.000', '1.942.160.000.000', '43,53%', '318.775.000.000', '692.580.000.000', '46,03%', '1.164.115.000.000', '2.634.740.000.000', '44,18%'],
            ['JAWA TIMUR', '5.321.620.000.000', '13.487.230.000.000', '39,46%', '2.044.980.000.000', '4.861.550.000.000', '42,06%', '7.366.600.000.000', '18.348.780.000.000', '40,15%'],
            ['BANTEN', '1.532.970.000.000', '3.764.280.000.000', '40,72%', '562.610.000.000', '1.321.920.000.000', '42,56%', '2.095.580.000.000', '5.086.200.000.000', '41,20%'],
            ['BALI', '1.108.845.000.000', '2.693.510.000.000', '41,17%', '426.760.000.000', '978.430.000.000', '43,62%', '1.535.605.000.000', '3.671.940.000.000', '41,82%'],
            ['NUSA TENGGARA BARAT', '897.640.000.000', '2.241.870.000.000', '40,04%', '351.235.000.000', '842.710.000.000', '41,68%', '1.248.875.000.000', '3.084.580.000.000', '40,49%'],
            ['NUSA TENGGARA TIMUR', '925.315.000.000', '2.403.980.000.000', '38,49%', '371.880.000.000', '936.740.000.000', '39,70%', '1.297.195.000.000', '3.340.720.000.000', '38,83%'],
            ['KALIMANTAN BARAT', '1.017.485.000.000', '2.746.380.000.000', '37,05%', '402.945.000.000', '1.041.870.000.000', '38,67%', '1.420.430.000.000', '3.788.250.000.000', '37,50%'],
            ['KALIMANTAN TENGAH', '854.710.000.000', '2.137.520.000.000', '39,99%', '333.620.000.000', '803.440.000.000', '41,52%', '1.188.330.000.000', '2.940.960.000.000', '40,41%'],
            ['KALIMANTAN SELATAN', '1.064.220.000.000', '2.622.890.000.000', '40,57%', '418.490.000.000', '981.725.000.000', '42,63%', '1.482.710.000.000', '3.604.615.000.000', '41,13%'],
            ['KALIMANTAN TIMUR', '1.482.900.000.000', '3.961.240.000.000', '37,44%', '592.740.000.000', '1.447.180.000.000', '40,96%', '2.075.640.000.000', '5.408.420.000.000', '38,38%'],
            ['KALIMANTAN UTARA', '476.815.000.000', '1.184.530.000.000', '40,25%', '188.620.000.000', '443.905.000.000', '42,49%', '665.435.000.000', '1.628.435.000.000', '40,86%'],
            ['SULAWESI UTARA', '801.360.000.000', '2.062.790.000.000', '38,85%', '315.480.000.000', '765.440.000.000', '41,21%', '1.116.840.000.000', '2.828.230.000.000', '39,49%'],
            ['SULAWESI TENGAH', '884.775.000.000', '2.234.610.000.000', '39,59%', '347.320.000.000', '836.905.000.000', '41,50%', '1.232.095.000.000', '3.071.515.000.000', '40,11%'],
            ['SULAWESI SELATAN', '1.792.540.000.000', '4.532.870.000.000', '39,54%', '704.625.000.000', '1.694.380.000.000', '41,59%', '2.497.165.000.000', '6.227.250.000.000', '40,10%'],
            ['SULAWESI TENGGARA', '734.920.000.000', '1.875.660.000.000', '39,18%', '291.480.000.000', '712.300.000.000', '40,92%', '1.026.400.000.000', '2.587.960.000.000', '39,66%'],
            ['GORONTALO', '428.315.000.000', '1.062.870.000.000', '40,30%', '169.740.000.000', '395.280.000.000', '42,94%', '598.055.000.000', '1.458.150.000.000', '41,02%'],
            ['SULAWESI BARAT', '386.450.000.000', '972.620.000.000', '39,73%', '153.610.000.000', '366.940.000.000', '41,86%', '540.060.000.000', '1.339.560.000.000', '40,32%'],
            ['MALUKU', '621.775.000.000', '1.587.430.000.000', '39,17%', '247.850.000.000', '606.720.000.000', '40,85%', '869.625.000.000', '2.194.150.000.000', '39,63%'],
            ['MALUKU UTARA', '548.610.000.000', '1.362.940.000.000', '40,25%', '216.430.000.000', '507.620.000.000', '42,64%', '765.040.000.000', '1.870.560.000.000', '40,90%'],
            ['PAPUA', '1.142.780.000.000', '3.096.420.000.000', '36,91%', '456.220.000.000', '1.169.540.000.000', '39,01%', '1.599.000.000.000', '4.265.960.000.000', '37,48%'],
            ['PAPUA BARAT', '672.930.000.000', '1.746.210.000.000', '38,54%', '267.520.000.000', '658.400.000.000', '40,63%', '940.450.000.000', '2.404.610.000.000', '39,11%'],
            ['PAPUA BARAT DAYA', '516.440.000.000', '1.278.930.000.000', '40,38%', '204.770.000.000', '477.615.000.000', '42,87%', '721.210.000.000', '1.756.545.000.000', '41,06%'],
            ['PAPUA TENGAH', '598.275.000.000', '1.534.680.000.000', '38,98%', '236.910.000.000', '579.440.000.000', '40,89%', '835.185.000.000', '2.114.120.000.000', '39,51%'],
            ['PAPUA PEGUNUNGAN', '442.710.000.000', '1.138.520.000.000', '38,88%', '176.480.000.000', '431.690.000.000', '40,88%', '619.190.000.000', '1.570.210.000.000', '39,43%'],
            ['PAPUA SELATAN', '489.365.000.000', '1.219.770.000.000', '40,12%', '194.250.000.000', '458.905.000.000', '42,33%', '683.615.000.000', '1.678.675.000.000', '40,72%'],
        ];
    }

    private static function fallbackTotals(): array
    {
        return ['55.898.715.000.000', '138.267.260.000.000', '40,43%', '21.349.895.000.000', '51.444.725.000.000', '41,50%', '77.248.610.000.000', '189.711.985.000.000', '40,72%'];
    }

    private static function formatMoney($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    private static function formatPercent($value): string
    {
        return number_format((float) $value, 2, ',', '.') . '%';
    }

    private static function latestSyncDate(): ?Carbon
    {
        if (!Schema::hasTable('rekapitulasi_nasional')) {
            return null;
        }

        $latestSync = RekapitulasiNasional::query()->max('scraped_at');

        if ($latestSync === null) {
            return null;
        }

        return Carbon::parse($latestSync);
    }

    private static function formatIndonesianDate(Carbon $date): string
    {
        $months = [
            1 => 'JANUARI',
            2 => 'FEBRUARI',
            3 => 'MARET',
            4 => 'APRIL',
            5 => 'MEI',
            6 => 'JUNI',
            7 => 'JULI',
            8 => 'AGUSTUS',
            9 => 'SEPTEMBER',
            10 => 'OKTOBER',
            11 => 'NOVEMBER',
            12 => 'DESEMBER',
        ];

        return $date->day . ' ' . $months[$date->month] . ' ' . $date->year;
    }

    private static function sortRowsByTotalPercentage(array $rows): array
    {
        usort($rows, function (array $left, array $right): int {
            return static::percentToFloat($right[9]) <=> static::percentToFloat($left[9]);
        });

        return $rows;
    }

    private static function percentToFloat(string $value): float
    {
        return (float) str_replace(',', '.', rtrim($value, '%'));
    }

    private static function percentage(float $realisasi, float $perencanaan): float
    {
        if ($perencanaan <= 0) {
            return 0.0;
        }

        return round(($realisasi / $perencanaan) * 100, 2);
    }
}
