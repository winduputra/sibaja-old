<?php

namespace App\Console\Commands;

use App\Models\PencatatanSwakelola;
use App\Models\SwakelolaRealisasi;
use App\Services\InaprocinaproApiClient;
use App\Transformers\SwakelolaTransformer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PencatatanSwakelolaSyncCommand extends Command
{
    protected $signature = 'inaproc:sync-pencatatan-swakelola {--type=all} {--tahun=2026} {--all-years} {--dry-run} {--limit=0}';
    protected $description = 'Sync Pencatatan Swakelola realization from INAPROC API; planning uses local RUP data';

    private int $synced = 0;
    private int $errors = 0;
    private int $skipped = 0;
    private bool $dryRun = false;
    private int $limit = 0;
    private string $tahun = '2026';

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $this->limit = (int) $this->option('limit');
        $type = $this->option('type');
        $tahunList = $this->tahunList();

        try {
            foreach ($tahunList as $tahun) {
                $this->tahun = (string) $tahun;
                $this->info("\nSyncing Pencatatan Swakelola for Year: {$this->tahun}");

                if (in_array($type, ['perencanaan', 'planning'])) {
                    $this->warn('> Pencatatan Swakelola Perencanaan uses local RUP data; API planning sync skipped.');
                }

                if (in_array($type, ['all', 'realisasi', 'tercatat'])) {
                    $this->syncRealisasi();
                }
            }

            $this->line("Total synced: {$this->synced}");
            $this->line("Total errors: {$this->errors}");
            $this->line("Total skipped: {$this->skipped}");

            if ($this->dryRun) {
                $this->warn('Dry run mode - no data was saved');
            }

            return $this->errors > 0 ? 1 : 0;
        } catch (\Throwable $exception) {
            $this->error('Sync failed: ' . $exception->getMessage());
            Log::error('PencatatanSwakelolaSyncCommand failed', ['error' => $exception->getMessage()]);

            return 1;
        }
    }

    private function tahunList(): array
    {
        if (!$this->option('all-years')) {
            return [$this->option('tahun') ?? '2026'];
        }

        return config('api.inaproc.endpoints.pencatatan_swakelola.supported_years', [2026]);
    }

    private function syncPerencanaan(): void
    {
        $this->line('> Syncing Pencatatan Swakelola Perencanaan...');
        $endpoint = config('api.inaproc.endpoints.pencatatan_swakelola.path', 'tender/pencatatan-swakelola');
        $localSynced = 0;
        $itemCount = 0;

        (new InaprocinaproApiClient())->paginate($endpoint, $this->apiParams(), function ($batch) use (&$localSynced, &$itemCount) {
            foreach ($batch as $item) {
                if ($this->limit > 0 && $itemCount >= $this->limit) {
                    return;
                }

                try {
                    $this->validateRequired($item, ['kd_swakelola_pct', 'kd_satker', 'nama_paket', 'nama_satker', 'pagu']);
                    $transformed = SwakelolaTransformer::pencatatan($item, $this->tahun);

                    if (!$this->dryRun) {
                        PencatatanSwakelola::updateOrCreate(
                            ['kd_swakelola_pct' => $transformed['kd_swakelola_pct']],
                            $transformed
                        );
                    }

                    $localSynced++;
                    $itemCount++;
                } catch (\Throwable $exception) {
                    $this->errors++;
                    $this->error('  Error: ' . $exception->getMessage());
                }
            }
        });

        $this->info("  Pencatatan Swakelola Perencanaan synced: {$localSynced}");
        $this->synced += $localSynced;
    }

    private function syncRealisasi(): void
    {
        $this->line('> Syncing Pencatatan Swakelola Realisasi...');
        $endpoint = config('api.inaproc.endpoints.pencatatan_swakelola_realisasi.path', 'tender/pencatatan-swakelola-realisasi');
        $localSynced = 0;
        $itemCount = 0;

        (new InaprocinaproApiClient())->paginate($endpoint, $this->apiParams(), function ($batch) use (&$localSynced, &$itemCount) {
            foreach ($batch as $item) {
                if ($this->limit > 0 && $itemCount >= $this->limit) {
                    return;
                }

                try {
                    $this->validateRequired($item, ['jenis_realisasi', 'kd_swakelola_pct', 'kd_satker']);
                    $transformed = $this->fillSatkerFromPlanning(
                        SwakelolaTransformer::pencatatanRealisasi($item, $this->tahun)
                    );

                    if (!$this->dryRun) {
                        SwakelolaRealisasi::updateOrCreate(
                            [
                                'tahun_anggaran' => $transformed['tahun_anggaran'],
                                'kd_swakelola_pct' => $transformed['kd_swakelola_pct'],
                                'no_realisasi' => $transformed['no_realisasi'],
                            ],
                            $transformed
                        );
                    }

                    $localSynced++;
                    $itemCount++;
                } catch (\Throwable $exception) {
                    $this->errors++;
                    $this->error('  Error: ' . $exception->getMessage());
                }
            }
        });

        $this->info("  Pencatatan Swakelola Realisasi synced: {$localSynced}");
        $this->synced += $localSynced;
    }

    private function apiParams(): array
    {
        return [
            'kode_klpd' => config('api.inaproc.kode_klpd', 'D264'),
            'tahun' => $this->tahun,
            'limit' => config('api.inaproc.limit_per_request', 1000),
        ];
    }

    private function validateRequired(array $item, array $fields): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $item) || $item[$field] === null) {
                $this->skipped++;
                throw new \Exception("Missing required field: {$field}");
            }
        }
    }

    private function fillSatkerFromPlanning(array $data): array
    {
        if (!empty($data['nama_satker']) && !empty($data['kd_satker'])) {
            return $data;
        }

        if (Schema::hasTable('swakelola_pencatatan')) {
            $planning = PencatatanSwakelola::query()
                ->where('tahun_anggaran', $data['tahun_anggaran'])
                ->where('kd_swakelola_pct', $data['kd_swakelola_pct'])
                ->first(['kd_satker', 'nama_satker']);

            if ($planning) {
                $data['kd_satker'] = $data['kd_satker'] ?: $planning->kd_satker;
                $data['nama_satker'] = $data['nama_satker'] ?: $planning->nama_satker;
            }
        }

        $data['nama_satker'] = $data['nama_satker'] ?: ($data['kd_satker'] ?: '-');

        return $data;
    }
}
