<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DestructiveApiImportRefresh;
use App\Models\EkatalogV6Paket;
use App\Services\InaprocinaproApiClient;
use App\Transformers\EkatalogV6Transformer;
use Illuminate\Console\Command;

class EkatalogV6SyncCommand extends Command
{
    use DestructiveApiImportRefresh;

    protected $signature = 'inaproc:sync-ekatalog-v6 {--tahun=2026} {--all-years} {--dry-run} {--limit=0}';
    protected $description = 'Sync E-Katalog V6 Paket E-Purchasing from INAPROC API';

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
        $allYears = $this->option('all-years');

        // Determine which tahun(s) to sync
        $tahunList = [];
        if ($allYears) {
            // Get supported years from config for ekatalog endpoint
            $config = config('api.inaproc.endpoints');
            $supportedYears = $config['ekatalog_v6']['supported_years'] ?? [2026];
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
            if (!$this->dryRun) {
                $this->truncateTargets([EkatalogV6Paket::class]);
            }

            // Loop through each year
            foreach ($tahunList as $tahun) {
                $this->tahun = (string)$tahun;
                $this->info("\n╔═══════════════════════════════════════════╗");
                $this->info("║ Syncing E-Katalog V6 Data for Year: {$tahun}");
                $this->info("╚═══════════════════════════════════════════╝");
                $this->line("Dry Run: " . ($this->dryRun ? 'Yes' : 'No'));
                if ($this->limit > 0) {
                    $this->line("Limit: $this->limit");
                }
                $this->line("");

                $this->syncPaketEPurchasing();
            }

            $this->info("\n╔═══════════════════════════════════════════╗");
            $this->info("║ E-Katalog V6 Sync Completed!");
            $this->info("╚═══════════════════════════════════════════╝");
            $this->line("Total synced: {$this->synced}");
            $this->line("Total errors: {$this->errors}");
            $this->line("Total skipped: {$this->skipped}");

            if ($this->dryRun) {
                $this->warn("Dry run mode - no data was saved");
            }

            return Command::SUCCESS;

        } catch (\Throwable $throwable) {
            return $this->reportDestructiveImportFailure($throwable, 'inaproc:sync-ekatalog-v6');
        }
    }

    protected function syncPaketEPurchasing(): void
    {
        $this->line("\n> Syncing E-Katalog V6 Paket E-Purchasing...");
        $apiClient = new InaprocinaproApiClient();
        $endpoint = 'ekatalog/paket-e-purchasing';
        $tahun = $this->tahun;

        $itemCount = 0;
        $statusFilters = ['PAYMENT_OUTSIDE_SYSTEM', 'COMPLETED', 'ON_PROCESS', 'ON_ADDENDUM'];

        $apiClient->paginate($endpoint,
            ['kode_klpd' => 'D264', 'tahun' => $tahun, 'limit' => 1000],
            function ($batch) use (&$itemCount, $statusFilters) {
                foreach ($batch as $item) {
                    if ($this->limit > 0 && $itemCount >= $this->limit) {
                        return;
                    }

                    // Check status filter
                    $status = $item['status'] ?? null;
                    if (!$status || !\in_array($status, $statusFilters)) {
                        $this->skipped++;
                        continue;
                    }

                    // Validate required fields
                    $required = ['count_product', 'order_date', 'order_id', 'status',
                               'total', 'total_qty', 'kode_satker', 'nama_satker'];

                    foreach ($required as $field) {
                        if (!\array_key_exists($field, $item) || $item[$field] === null) {
                            throw new \Exception("Missing required field: $field");
                        }
                    }

                    $transformed = EkatalogV6Transformer::paketEPurchasing($item);

                    if (!$this->dryRun) {
                        EkatalogV6Paket::updateOrCreate(
                            ['kd_paket' => $transformed['kd_paket'] ?? null],
                            $transformed
                        );
                    }

                    $this->synced++;
                    $itemCount++;

                    if ($this->synced % 50 === 0) {
                        $this->info("  Processed: {$this->synced} paket e-purchasing");
                    }
                }
            }
        );

        $this->info("  ✓ E-Katalog V6 Paket E-Purchasing synced: {$this->synced}");
    }
}
