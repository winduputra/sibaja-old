<?php

namespace App\Console\Commands;

use App\Models\KontrakData;
use App\Models\TenderPengumumanData;
use App\Models\TenderSelesaiNilaiData;
use App\Services\InaprocinaproApiClient;
use App\Transformers\TenderTransformer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TenderSyncCommand extends Command
{
    protected $signature = 'inaproc:sync-tender {--type=all} {--tahun=2026} {--all-years} {--dry-run} {--limit=0}';
    protected $description = 'Sync Tender (Pengumuman, EkontrakKontrak, SelesaiNilai) from INAPROC API';

    protected $synced = 0;
    protected $errors = 0;
    protected $skipped = 0;
    protected $dryRun = false;
    protected $limit = 0;
    protected $tahun = '2026';

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $this->limit = (int) $this->option('limit');
        $type = $this->option('type');
        $allYears = $this->option('all-years');

        // Determine which tahun(s) to sync
        $tahunList = [];
        if ($allYears) {
            // Get supported years from config for tender endpoints
            $config = config('api.inaproc.endpoints');
            $supportedYears = $config['tender_pengumuman']['supported_years'] ?? [2026];
            $tahunList = $supportedYears;
            $this->info("Syncing all supported years: " . implode(', ', $tahunList));
        } else {
            $this->tahun = $this->option('tahun') ?? '2026';
            $tahunList = [$this->tahun];
        }

        // Reset counters for all years
        $this->synced = 0;
        $this->errors = 0;
        $this->skipped = 0;

        try {
            // Loop through each year
            foreach ($tahunList as $tahun) {
                $this->tahun = (string)$tahun;
                $this->info("\n╔═══════════════════════════════════════════╗");
                $this->info("║ Syncing Tender Data for Year: {$tahun}");
                $this->info("╚═══════════════════════════════════════════╝");
                $this->line("Type: $type");
                $this->line("Dry Run: " . ($this->dryRun ? 'Yes' : 'No'));
                if ($this->limit > 0) {
                    $this->line("Limit: $this->limit");
                }
                $this->line("");

                if (\in_array($type, ['all', 'pengumuman'])) {
                    $this->syncPengumuman();
                }

                if (\in_array($type, ['all', 'ekontrak'])) {
                    $this->syncEkontrakKontrak();
                }

                if (\in_array($type, ['all', 'selesai'])) {
                    $this->syncSelesaiNilai();
                }
            }

            $this->info("\n╔═══════════════════════════════════════════╗");
            $this->info("║ Tender Sync Completed!");
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
            Log::error("TenderSyncCommand failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function syncPengumuman(): void
    {
        $this->line("\n> Syncing Tender Pengumuman...");
        $apiClient = new InaprocinaproApiClient();
        $endpoint = 'tender/pengumuman';
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
                        // Validate required fields
                        $required = ['hps', 'jenis_pengadaan', 'kd_tender', 'mtd_pemilihan',
                                   'nama_paket', 'nama_satker', 'pagu', 'status_tender'];

                        foreach ($required as $field) {
                            if (!\array_key_exists($field, $item) || $item[$field] === null) {
                                throw new \Exception("Missing required field: $field");
                            }
                        }

                        $transformed = TenderTransformer::pengumuman($item, $tahun);

                        if (!$this->dryRun) {
                            TenderPengumumanData::updateOrCreate(
                                ['kd_tender' => $transformed['kd_tender']],
                                $transformed
                            );
                        }

                        $localSynced++;
                        $itemCount++;

                        if ($localSynced % 50 === 0) {
                            $this->info("  Processed: $localSynced tender pengumuman");
                        }

                    } catch (\Exception $e) {
                        $this->error("  Error: " . $e->getMessage());
                        $this->errors++;
                    }
                }
            }
        );

        $this->info("  ✓ Tender Pengumuman synced: $localSynced");
        $this->synced += $localSynced;
    }

    protected function syncEkontrakKontrak(): void
    {
        $this->line("\n> Syncing Tender Ekontrak Kontrak...");
        $apiClient = new InaprocinaproApiClient();
        $endpoint = 'tender/tender-ekontrak-kontrak';
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
                        $required = ['kd_tender'];

                        foreach ($required as $field) {
                            if (!\array_key_exists($field, $item) || $item[$field] === null) {
                                throw new \Exception("Missing required field: $field");
                            }
                        }

                        $transformed = TenderTransformer::ekontrakKontrak($item, $tahun);

                        if (!$this->dryRun) {
                            KontrakData::updateOrCreate(
                                ['kd_tender' => $transformed['kd_tender']],
                                $transformed
                            );
                        }

                        $localSynced++;
                        $itemCount++;

                        if ($localSynced % 50 === 0) {
                            $this->info("  Processed: $localSynced ekontrak kontrak");
                        }

                    } catch (\Exception $e) {
                        $this->error("  Error: " . $e->getMessage());
                        $this->errors++;
                    }
                }
            }
        );

        $this->info("  ✓ Tender Ekontrak Kontrak synced: $localSynced");
        $this->synced += $localSynced;
    }

    protected function syncSelesaiNilai(): void
    {
        $this->line("\n> Syncing Tender Selesai Nilai...");
        $apiClient = new InaprocinaproApiClient();
        $endpoint = 'tender/tender-selesai-nilai';
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
                        // Validate only critical required fields
                        // All nilai_* fields are optional - they may not exist for all records
                        $required = ['hps', 'kd_tender', 'nama_satker', 'pagu'];

                        foreach ($required as $field) {
                            if (!\array_key_exists($field, $item) || $item[$field] === null) {
                                throw new \Exception("Missing required field: $field");
                            }
                        }

                        $transformed = TenderTransformer::selesaiNilai($item, $tahun);

                        if (!$this->dryRun) {
                            TenderSelesaiNilaiData::updateOrCreate(
                                ['kd_tender' => $transformed['kd_tender']],
                                $transformed
                            );
                        }

                        $localSynced++;
                        $itemCount++;

                        if ($localSynced % 50 === 0) {
                            $this->info("  Processed: $localSynced selesai nilai");
                        }

                    } catch (\Exception $e) {
                        $this->error("  Error: " . $e->getMessage());
                        $this->errors++;
                    }
                }
            }
        );

        $this->info("  ✓ Tender Selesai Nilai synced: $localSynced");
        $this->synced += $localSynced;
    }
}
