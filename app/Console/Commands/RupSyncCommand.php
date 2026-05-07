<?php

namespace App\Console\Commands;

use App\Models\Satker;
use App\Models\Penyedia;
use App\Services\InaprocinaproApiClient;
use App\Transformers\RupTransformer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RupSyncCommand extends Command
{
    protected $signature = 'inaproc:sync-rup {--type=all} {--tahun=2026} {--all-years} {--dry-run} {--limit=0}';
    protected $description = 'Sync RUP (Master Satker, Paket Penyedia, Paket Swakelola) from INAPROC API';

    protected $synced = 0;
    protected $errors = 0;
    protected $skipped = 0;
    protected $dryRun = false;
    protected $limit = 0;
    protected $tahun = '2026';
    protected $syncStartTime;

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $this->limit = (int) $this->option('limit');
        $type = $this->option('type');
        $allYears = $this->option('all-years');

        // Determine which tahun(s) to sync for penyedia/swakelola
        $tahunList = [];
        if ($allYears) {
            // Get supported years from config for penyedia
            $config = config('api.inaproc.endpoints');
            $supportedYears = $config['rup_paket_penyedia']['supported_years'] ?? [2026];
            $tahunList = $supportedYears;
            $this->info("Syncing all supported years: " . implode(', ', $tahunList));
        } else {
            $this->tahun = $this->option('tahun') ?? '2026';
            $tahunList = [$this->tahun];
        }

        $this->syncStartTime = now();

        // Reset counters
        $this->synced = 0;
        $this->errors = 0;
        $this->skipped = 0;

        try {
            // Master Satker is always 2026
            if (\in_array($type, ['all', 'satker'])) {
                $this->info("\n╔═══════════════════════════════════════════╗");
                $this->info("║ Syncing RUP Master Satker (2026 only)");
                $this->info("╚═══════════════════════════════════════════╝");
                $this->syncMasterSatker();
            }

            // Penyedia dan Swakelola loop through years
            foreach ($tahunList as $tahun) {
                $this->tahun = (string)$tahun;

                if (\in_array($type, ['all', 'penyedia'])) {
                    $this->info("\n╔═══════════════════════════════════════════╗");
                    $this->info("║ Syncing RUP Paket Penyedia for Year: {$tahun}");
                    $this->info("╚═══════════════════════════════════════════╝");
                    $this->syncPaketPenyedia();

                    if (!$this->dryRun && $this->limit === 0) {
                        $this->cleanupPenyedia($tahun);
                    }
                }

                if (\in_array($type, ['all', 'swakelola'])) {
                    $this->info("\n╔═══════════════════════════════════════════╗");
                    $this->info("║ Syncing RUP Paket Swakelola for Year: {$tahun}");
                    $this->info("╚═══════════════════════════════════════════╝");
                    $this->syncPaketSwakelola();

                    if (!$this->dryRun && $this->limit === 0) {
                        $this->cleanupSwakelola($tahun);
                    }
                }
            }

            $this->info("\n╔═══════════════════════════════════════════╗");
            $this->info("║ RUP Sync Completed!");
            $this->info("╚═══════════════════════════════════════════╝");
            $this->line("Total synced: {$this->synced}");
            $this->line("Total errors: {$this->errors}");
            $this->line("Total skipped: {$this->skipped}");

            if ($this->dryRun) {
                $this->warn("Dry run mode - no data was saved");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            Log::error("RupSyncCommand failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function cleanupPenyedia(string $tahun): void
    {
        $this->line("> Cleaning up stale Penyedia data for year $tahun...");
        $deleted = Penyedia::where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->where(function ($query) {
                $query->where('last_synced_at', '<', $this->syncStartTime)
                      ->orWhereNull('last_synced_at');
            })
            ->delete();

        if ($deleted > 0) {
            $this->warn("  Deleted $deleted stale paket penyedia (not found in latest API response)");
        } else {
            $this->info("  No stale data found.");
        }
    }

    protected function cleanupSwakelola(string $tahun): void
    {
        $this->line("> Cleaning up stale Swakelola data for year $tahun...");
        $deleted = \App\Models\Swakelola::where('tahun_anggaran', $tahun)
            ->where('kd_klpd', 'D264')
            ->where(function ($query) {
                $query->where('last_synced_at', '<', $this->syncStartTime)
                      ->orWhereNull('last_synced_at');
            })
            ->delete();

        if ($deleted > 0) {
            $this->warn("  Deleted $deleted stale paket swakelola (not found in latest API response)");
        } else {
            $this->info("  No stale data found.");
        }
    }

    protected function syncMasterSatker(): void
    {
        $this->line("\n> Syncing Master Satker...");
        $apiClient = new \App\Services\InaprocinaproApiClient();
        $endpoint = 'rup/master-satker';

        $itemCount = 0;
        $localSynced = 0;

        $apiClient->paginate($endpoint, ['kode_klpd' => 'D264', 'tahun' => '2026', 'limit' => 1000],
            function ($batch) use (&$itemCount, &$localSynced) {
                foreach ($batch as $item) {
                    if ($this->limit > 0 && $itemCount >= $this->limit) {
                        return;
                    }

                    try {
                        // Check skip condition: tahun_aktif must include 2026
                        $tahunAktif = $item['tahun_aktif'] ?? [];
                        if (!\is_array($tahunAktif)) {
                            $tahunAktif = \explode(',', $tahunAktif);
                        }

                        if (!\in_array(2026, \array_map('intval', $tahunAktif))) {
                            $this->skipped++;
                            continue;
                        }

                        $transformed = RupTransformer::masterSatker($item);

                        if (!$this->dryRun) {
                            Satker::updateOrCreate(
                                ['kd_satker' => $transformed['kd_satker']],
                                $transformed
                            );
                        }

                        $localSynced++;
                        $itemCount++;

                        if ($localSynced % 50 === 0) {
                            $this->info("  Processed: $localSynced master satker");
                        }

                    } catch (\Exception $e) {
                        $this->error("  Error: " . $e->getMessage());
                        $this->errors++;
                    }
                }
            }
        );

        $this->info("  ✓ Master Satker synced: $localSynced");
        $this->synced += $localSynced;
    }

    protected function syncPaketPenyedia(): void
    {
        $this->line("\n> Syncing Paket Penyedia...");
        $apiClient = new \App\Services\InaprocinaproApiClient();
        $endpoint = 'rup/paket-penyedia-terumumkan';
        $tahun = $this->tahun;

        $itemCount = 0;
        $localSynced = 0;

        $apiClient->paginate($endpoint,
            ['kode_klpd' => 'D264', 'tahun' => $tahun, 'limit' => 1000],
            function ($batch) use (&$itemCount, &$localSynced, $tahun) {
                foreach ($batch as $item) {
                    if ($this->limit > 0 && $itemCount >= $this->limit) {
                        return;
                    }

                    try {
                        // Validate filters
                        if (!($item['status_aktif_rup'] ?? false) || ($item['status_umumkan_rup'] ?? '') !== 'Terumumkan') {
                            $this->skipped++;
                            continue;
                        }

                        $transformed = RupTransformer::paketPenyedia($item, $tahun);

                        if (!$this->dryRun) {
                            Penyedia::updateOrCreate(
                                ['kd_rup' => $transformed['kd_rup']],
                                $transformed
                            );
                        }

                        $localSynced++;
                        $itemCount++;

                        if ($localSynced % 50 === 0) {
                            $this->info("  Processed: $localSynced paket penyedia");
                        }

                    } catch (\Exception $e) {
                        $this->error("  Error: " . $e->getMessage());
                        $this->errors++;
                    }
                }
            }
        );

        $this->info("  ✓ Paket Penyedia synced: $localSynced");
        $this->synced += $localSynced;
    }

    protected function syncPaketSwakelola(): void
    {
        $this->line("\n> Syncing Paket Swakelola...");
        $apiClient = new \App\Services\InaprocinaproApiClient();
        $endpoint = 'rup/paket-swakelola-terumumkan';
        $tahun = $this->tahun;

        $itemCount = 0;
        $localSynced = 0;

        $apiClient->paginate($endpoint,
            ['kode_klpd' => 'D264', 'tahun' => $tahun, 'limit' => 1000],
            function ($batch) use (&$itemCount, &$localSynced, $tahun) {
                foreach ($batch as $item) {
                    if ($this->limit > 0 && $itemCount >= $this->limit) {
                        return;
                    }

                    try {
                        // Validate filters
                        if (!($item['status_aktif_rup'] ?? false) || ($item['status_umumkan_rup'] ?? '') !== 'Terumumkan') {
                            $this->skipped++;
                            continue;
                        }

                        $transformed = RupTransformer::paketSwakelola($item, $tahun);

                        if (!$this->dryRun) {
                            \App\Models\Swakelola::updateOrCreate(
                                ['kd_rup' => $transformed['kd_rup'], 'tahun_anggaran' => $tahun],
                                $transformed
                            );
                        }

                        $localSynced++;
                        $itemCount++;

                        if ($localSynced % 50 === 0) {
                            $this->info("  Processed: $localSynced paket swakelola");
                        }

                    } catch (\Exception $e) {
                        $this->error("  Error: " . $e->getMessage());
                        $this->errors++;
                    }
                }
            }
        );

        $this->info("  ✓ Paket Swakelola synced: $localSynced");
        $this->synced += $localSynced;
    }
}
