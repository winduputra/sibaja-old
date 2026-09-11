<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DestructiveApiImportRefresh;
use App\Models\NonTenderPengumuman;
use App\Models\NonTenderSelesai;
use App\Models\NonTenderKontrak;
use App\Models\PencatatanNonTender;
use App\Models\RealisasiNonTender;
use App\Services\InaprocinaproApiClient;
use App\Transformers\NonTenderTransformer;
use Illuminate\Console\Command;

class NonTenderSyncCommand extends Command
{
    use DestructiveApiImportRefresh;

    protected $signature = 'inaproc:sync-non-tender {--type=all} {--tahun=2026} {--all-years} {--dry-run} {--limit=0}';
    protected $description = 'Sync Non-Tender data from INAPROC API';

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
            // Get supported years from config for non-tender endpoints
            $config = config('api.inaproc.endpoints');
            $supportedYears = $config['non_tender_pengumuman']['supported_years'] ?? [2026];
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
                $this->truncateTargets($this->getRefreshTargets($type));
            }

            // Loop through each year
            foreach ($tahunList as $tahun) {
                $this->tahun = (string)$tahun;
                $this->info("\n╔═══════════════════════════════════════════╗");
                $this->info("║ Syncing Non-Tender Data for Year: {$tahun}");
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

                if (\in_array($type, ['all', 'selesai'])) {
                    $this->syncSelesai();
                }

                if (\in_array($type, ['all', 'ekontrak'])) {
                    $this->syncEkontrakKontrak();
                }

                if (\in_array($type, ['all', 'pencatatan', 'perencanaan', 'planning'])) {
                    $this->syncPencatatanPerencanaan();
                }

                if (\in_array($type, ['all', 'pencatatan', 'realisasi', 'tercatat'])) {
                    $this->syncPencatatanRealisasi();
                }
            }

            $this->info("\n╔═══════════════════════════════════════════╗");
            $this->info("║ Non-Tender Sync Completed!");
            $this->info("╚═══════════════════════════════════════════╝");
            $this->line("Total synced: {$this->synced}");
            $this->line("Total errors: {$this->errors}");
            $this->line("Total skipped: {$this->skipped}");

            if ($this->dryRun) {
                $this->warn("Dry run mode - no data was saved");
            }

            return Command::SUCCESS;

        } catch (\Throwable $throwable) {
            return $this->reportDestructiveImportFailure($throwable, 'inaproc:sync-non-tender');
        }
    }

    /**
     * @return array<int, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    protected function getRefreshTargets(string $type): array
    {
        if ($type === 'all') {
            return [NonTenderPengumuman::class, NonTenderSelesai::class, NonTenderKontrak::class, PencatatanNonTender::class, RealisasiNonTender::class];
        }

        if ($type === 'pengumuman') {
            return [NonTenderPengumuman::class];
        }

        if ($type === 'selesai') {
            return [NonTenderSelesai::class];
        }

        if ($type === 'ekontrak') {
            return [NonTenderKontrak::class];
        }

        if ($type === 'pencatatan') {
            return [PencatatanNonTender::class, RealisasiNonTender::class];
        }

        if ($type === 'perencanaan' || $type === 'planning') {
            return [PencatatanNonTender::class];
        }

        if ($type === 'realisasi' || $type === 'tercatat') {
            return [RealisasiNonTender::class];
        }

        return [];
    }

    protected function syncPengumuman(): void
    {
        $this->line("\n> Syncing Non-Tender Pengumuman...");
        $apiClient = new InaprocinaproApiClient();
        $endpoint = 'tender/non-tender-pengumuman';

        $itemCount = 0;
        $localSynced = 0;

        $apiClient->paginate($endpoint,
            ['kode_klpd' => 'D264', 'tahun' => $this->tahun, 'limit' => 1000],
            function ($batch) use (&$itemCount, &$localSynced, &$tahun) {
                $tahun = $this->tahun;
                foreach ($batch as $item) {
                    if ($this->limit > 0 && $itemCount >= $this->limit) {
                        return;
                    }

                    $required = ['hps', 'jenis_pengadaan', 'kd_nontender', 'kd_satker',
                               'mtd_pemilihan', 'nama_paket', 'nama_satker', 'pagu', 'status_nontender'];

                    foreach ($required as $field) {
                        if (!\array_key_exists($field, $item) || $item[$field] === null) {
                            throw new \Exception("Missing required field: $field");
                        }
                    }

                    $transformed = NonTenderTransformer::pengumuman($item, $tahun);

                    if (!$this->dryRun) {
                        NonTenderPengumuman::updateOrCreate(
                            ['kd_nontender' => $transformed['kd_nontender']],
                            $transformed
                        );
                    }

                    $localSynced++;
                    $itemCount++;

                    if ($localSynced % 50 === 0) {
                        $this->info("  Processed: $localSynced pengumuman");
                    }
                }
            }
        );

        $this->info("  ✓ Non-Tender Pengumuman synced: $localSynced");
        $this->synced += $localSynced;
    }

    protected function syncSelesai(): void
    {
        $this->line("\n> Syncing Non-Tender Selesai...");
        $apiClient = new InaprocinaproApiClient();
        $endpoint = 'tender/non-tender-selesai';

        $itemCount = 0;
        $localSynced = 0;

        $apiClient->paginate($endpoint,
            ['kode_klpd' => 'D264', 'tahun' => $this->tahun, 'limit' => 1000],
            function ($batch) use (&$itemCount, &$localSynced, &$tahun) {
                $tahun = $this->tahun;
                foreach ($batch as $item) {
                    if ($this->limit > 0 && $itemCount >= $this->limit) {
                        return;
                    }

                    $required = ['hps', 'jenis_pengadaan', 'kd_nontender', 'kd_satker',
                               'mtd_pemilihan', 'nama_paket', 'nama_satker', 'pagu', 'status_nontender'];

                    foreach ($required as $field) {
                        if (!\array_key_exists($field, $item) || $item[$field] === null) {
                            throw new \Exception("Missing required field: $field");
                        }
                    }

                    $transformed = NonTenderTransformer::selesai($item, $tahun);

                    if (!$this->dryRun) {
                        NonTenderSelesai::updateOrCreate(
                            ['kd_nontender' => $transformed['kd_nontender']],
                            $transformed
                        );
                    }

                    $localSynced++;
                    $itemCount++;

                    if ($localSynced % 50 === 0) {
                        $this->info("  Processed: $localSynced selesai");
                    }
                }
            }
        );

        $this->info("  ✓ Non-Tender Selesai synced: $localSynced");
        $this->synced += $localSynced;
    }

    protected function syncEkontrakKontrak(): void
    {
        $this->line("\n> Syncing Non-Tender Ekontrak Kontrak...");
        $apiClient = new InaprocinaproApiClient();
        $endpoint = 'tender/non-tender-ekontrak-kontrak';

        $itemCount = 0;
        $localSynced = 0;

        $apiClient->paginate($endpoint,
            ['kode_klpd' => 'D264', 'tahun' => $this->tahun, 'limit' => 1000],
            function ($batch) use (&$itemCount, &$localSynced, &$tahun) {
                $tahun = $this->tahun;
                foreach ($batch as $item) {
                    if ($this->limit > 0 && $itemCount >= $this->limit) {
                        return;
                    }

                    // nilai_kontrak is optional - not all records have it
                    $required = ['apakah_addendum', 'jenis_kontrak', 'kd_nontender', 'kd_satker',
                               'mtd_pengadaan', 'nama_paket', 'nama_satker', 'status_kontrak', 'tgl_kontrak'];

                    foreach ($required as $field) {
                        if (!\array_key_exists($field, $item) || $item[$field] === null) {
                            throw new \Exception("Missing required field: $field");
                        }
                    }

                    $transformed = NonTenderTransformer::ekontrakKontrak($item, $tahun);

                    if (!$this->dryRun) {
                        NonTenderKontrak::updateOrCreate(
                            ['kd_nontender' => $transformed['kd_nontender']],
                            $transformed
                        );
                    }

                    $localSynced++;
                    $itemCount++;

                    if ($localSynced % 50 === 0) {
                        $this->info("  Processed: $localSynced ekontrak kontrak");
                    }
                }
            }
        );

        $this->info("  ✓ Non-Tender Ekontrak Kontrak synced: $localSynced");
        $this->synced += $localSynced;
    }

    protected function syncPencatatanPerencanaan(): void
    {
        $this->line("\n> Syncing Pencatatan Non-Tender Perencanaan...");
        $apiClient = new InaprocinaproApiClient();
        $endpoint = config('api.inaproc.endpoints.pencatatan_non_tender.path', 'tender/pencatatan-non-tender');

        $itemCount = 0;
        $localSynced = 0;

        $apiClient->paginate($endpoint,
            ['kode_klpd' => 'D264', 'tahun' => $this->tahun, 'limit' => 1000],
            function ($batch) use (&$itemCount, &$localSynced, &$tahun) {
                $tahun = $this->tahun;
                foreach ($batch as $item) {
                    if ($this->limit > 0 && $itemCount >= $this->limit) {
                        return;
                    }

                    $required = ['kd_nontender_pct', 'kd_satker', 'nama_paket', 'nama_satker', 'pagu'];

                    foreach ($required as $field) {
                        if (!\array_key_exists($field, $item) || $item[$field] === null) {
                            throw new \Exception("Missing required field: $field");
                        }
                    }

                    $transformed = NonTenderTransformer::pencatatan($item, $tahun);

                    if (!$this->dryRun) {
                        PencatatanNonTender::updateOrCreate(
                            ['kd_nontender_pct' => $transformed['kd_nontender_pct']],
                            $transformed
                        );
                    }

                    $localSynced++;
                    $itemCount++;

                    if ($localSynced % 50 === 0) {
                        $this->info("  Processed: $localSynced perencanaan");
                    }
                }
            }
        );

        $this->info("  ✓ Pencatatan Non-Tender Perencanaan synced: $localSynced");
        $this->synced += $localSynced;
    }

    protected function syncPencatatanRealisasi(): void
    {
        $this->line("\n> Syncing Pencatatan Non-Tender Realisasi...");
        $apiClient = new InaprocinaproApiClient();
        $endpoint = config('api.inaproc.endpoints.pencatatan_non_tender_realisasi.path', 'tender/pencatatan-non-tender-realisasi');

        $itemCount = 0;
        $localSynced = 0;

        $apiClient->paginate($endpoint,
            ['kode_klpd' => 'D264', 'tahun' => $this->tahun, 'limit' => 1000],
            function ($batch) use (&$itemCount, &$localSynced, &$tahun) {
                $tahun = $this->tahun;
                foreach ($batch as $item) {
                    if ($this->limit > 0 && $itemCount >= $this->limit) {
                        return;
                    }

                    // nilai_realisasi is optional - not all records have it
                    $required = ['jenis_realisasi', 'kd_nontender_pct', 'kd_satker',
                               'nama_paket', 'nama_satker', 'pagu'];

                    foreach ($required as $field) {
                        if (!\array_key_exists($field, $item) || $item[$field] === null) {
                            throw new \Exception("Missing required field: $field");
                        }
                    }

                    $transformed = NonTenderTransformer::pencatatanRealisasi($item, $tahun);

                    if (!$this->dryRun) {
                        $keys = [
                            'tahun_anggaran' => $transformed['tahun_anggaran'],
                            'kd_nontender_pct' => $transformed['kd_nontender_pct'],
                        ];

                        if (!empty($transformed['no_realisasi'])) {
                            $keys['no_realisasi'] = $transformed['no_realisasi'];
                        } else {
                            foreach (['tgl_realisasi', 'jenis_realisasi', 'nilai_realisasi', 'dok_realisasi', 'ket_realisasi'] as $field) {
                                if ($transformed[$field] !== null && $transformed[$field] !== '') {
                                    $keys[$field] = $transformed[$field];
                                }
                            }
                        }

                        RealisasiNonTender::updateOrCreate(
                            $keys,
                            $transformed
                        );
                    }

                    $localSynced++;
                    $itemCount++;

                    if ($localSynced % 50 === 0) {
                        $this->info("  Processed: $localSynced realisasi");
                    }
                }
            }
        );

        $this->info("  ✓ Pencatatan Non-Tender Realisasi synced: $localSynced");
        $this->synced += $localSynced;
    }
}
