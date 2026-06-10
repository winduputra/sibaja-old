<?php

namespace App\Services;

use App\Models\Swakelola;
use App\Models\SwakelolaRealisasi;
use Illuminate\Support\Collection;

class PencatatanSwakelolaRealisasiService
{
    public function report(int $tahun): array
    {
        $planning = $this->planningBySatker($tahun);
        $realization = $this->realizationBySatker($tahun);

        $keys = $planning->keys()->merge($realization->keys())->unique()->sortBy(function ($key) use ($planning, $realization) {
            return $planning->get($key)['nama_opd'] ?? $realization->get($key)['nama_opd'] ?? $key;
        });

        $rows = $keys->map(function ($key) use ($planning, $realization) {
            $plan = $planning->get($key, [
                'nama_opd' => $realization->get($key)['nama_opd'] ?? '-',
                'paket' => 0,
                'pagu' => 0,
            ]);
            $real = $realization->get($key, [
                'nama_opd' => $plan['nama_opd'],
                'paket' => 0,
                'pagu' => 0,
            ]);

            $belumPaket = max($plan['paket'] - $real['paket'], 0);
            $belumPagu = max($plan['pagu'] - $real['pagu'], 0);

            return [
                'nama_opd' => $plan['nama_opd'] ?: $real['nama_opd'],
                'perencanaan_paket' => $plan['paket'],
                'perencanaan_pagu' => $plan['pagu'],
                'tercatat_paket' => $real['paket'],
                'tercatat_pagu' => $real['pagu'],
                'belum_tercatat_paket' => $belumPaket,
                'belum_tercatat_pagu' => $belumPagu,
                'persentase_tercatat' => $plan['pagu'] > 0 ? round(($real['pagu'] / $plan['pagu']) * 100, 2) : 0,
            ];
        })->values();

        return [
            'rows' => $rows,
            'summary' => $this->summary($rows),
            'apiError' => null,
        ];
    }

    public function availableYears(int $selectedYear): array
    {
        $years = Swakelola::where('kd_klpd', config('api.inaproc.kode_klpd', 'D264'))
            ->distinct()
            ->pluck('tahun_anggaran')
            ->map(fn ($year) => (int) $year)
            ->push($selectedYear)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return $years ?: [$selectedYear];
    }

    private function planningBySatker(int $tahun): Collection
    {
        return Swakelola::query()
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', config('api.inaproc.kode_klpd', 'D264'))
            ->where('status_umumkan_rup', 'Terumumkan')
            ->where('status_aktif_rup', 1)
            ->get(['kd_satker', 'nama_satker', 'kd_rup', 'pagu'])
            ->groupBy(fn ($item) => $this->satkerKey($item->kd_satker, $item->nama_satker))
            ->map(function ($items) {
                return [
                    'nama_opd' => $items->first()->nama_satker ?? '-',
                    'paket' => $items->pluck('kd_rup')->filter()->unique()->count() ?: $items->count(),
                    'pagu' => (float) $items->sum('pagu'),
                ];
            });
    }

    private function realizationBySatker(int $tahun): Collection
    {
        return SwakelolaRealisasi::query()
            ->where('tahun_anggaran', $tahun)
            ->where('kd_klpd', config('api.inaproc.kode_klpd', 'D264'))
            ->get(['kd_satker', 'nama_satker', 'kd_swakelola_pct', 'no_realisasi', 'nilai_realisasi'])
            ->groupBy(fn ($item) => $this->satkerKey($item->kd_satker, $item->nama_satker))
            ->map(function ($items) {
                return [
                    'nama_opd' => $items->first()->nama_satker ?? '-',
                    'paket' => $items->pluck('kd_swakelola_pct')->filter()->unique()->count() ?: $items->count(),
                    'pagu' => (float) $items->sum('nilai_realisasi'),
                ];
            });
    }

    private function summary(Collection $rows): array
    {
        $perencanaanPagu = (float) $rows->sum('perencanaan_pagu');
        $tercatatPagu = (float) $rows->sum('tercatat_pagu');

        return [
            'perencanaan_paket' => (int) $rows->sum('perencanaan_paket'),
            'perencanaan_pagu' => $perencanaanPagu,
            'tercatat_paket' => (int) $rows->sum('tercatat_paket'),
            'tercatat_pagu' => $tercatatPagu,
            'belum_tercatat_paket' => (int) $rows->sum('belum_tercatat_paket'),
            'belum_tercatat_pagu' => (float) $rows->sum('belum_tercatat_pagu'),
            'persentase_tercatat' => $perencanaanPagu > 0 ? round(($tercatatPagu / $perencanaanPagu) * 100, 2) : 0,
        ];
    }

    private function satkerKey($kdSatker, $namaSatker): string
    {
        return trim((string) ($kdSatker ?: $namaSatker));
    }

}
