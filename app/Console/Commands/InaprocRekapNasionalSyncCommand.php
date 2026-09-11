<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DestructiveApiImportRefresh;
use App\Models\RekapitulasiNasional;
use App\Services\InaprocRekapNasionalBrowser;
use App\Services\InaprocRekapNasionalTextParser;
use Illuminate\Console\Command;

class InaprocRekapNasionalSyncCommand extends Command
{
    use DestructiveApiImportRefresh;

    protected $signature = 'inaproc:sync-rekap-nasional {--province=} {--dry-run} {--limit=0} {--timeout=}';
    protected $description = 'Scrape Rekapitulasi Nasional from rendered Inaproc Streamlit pages using Playwright';

    public function handle(InaprocRekapNasionalBrowser $browser, InaprocRekapNasionalTextParser $parser): int
    {
        $provinces = $this->selectedProvinces();
        $timeout = (int) ($this->option('timeout') ?: config('inaproc_rekap_nasional.timeout_seconds', 90));
        $dryRun = (bool) $this->option('dry-run');
        $synced = 0;
        $errors = 0;

        if (empty($provinces)) {
            $this->warn('Tidak ada provinsi yang cocok dengan opsi command.');
            return 1;
        }

        try {
            if (!$dryRun) {
                $this->truncateTargets([RekapitulasiNasional::class]);
            }

            foreach ($provinces as $index => $province) {
                $this->line('Scraping ' . $province['name'] . ' (' . $province['code'] . ')...');

                $innerText = $browser->scrape($province['url'], $timeout);
                $parsed = $parser->parse($innerText);
                $payload = array_merge($parsed, [
                    'province_code' => $province['code'],
                    'province_name' => $province['name'],
                    'source_url' => $province['url'],
                    'scraped_at' => now(),
                ]);

                if (!$dryRun) {
                    RekapitulasiNasional::updateOrCreate(
                        ['province_code' => $province['code']],
                        $payload
                    );
                }

                $synced++;
                $this->info('  OK: ' . $this->formatMoney($payload['total_realisasi']) . ' / ' . $this->formatMoney($payload['total_perencanaan']));

                if ($index < count($provinces) - 1) {
                    sleep((int) config('inaproc_rekap_nasional.sleep_seconds', 3));
                }
            }

            $this->line("Selesai. Synced: {$synced}. Errors: {$errors}.");

            if ($dryRun) {
                $this->warn('Dry run mode - tidak ada data yang disimpan.');
            }

            return Command::SUCCESS;
        } catch (\Throwable $throwable) {
            return $this->reportDestructiveImportFailure($throwable, 'inaproc:sync-rekap-nasional');
        }
    }

    private function selectedProvinces(): array
    {
        $provinces = config('inaproc_rekap_nasional.provinces', []);
        $provinceFilter = $this->option('province');
        $limit = (int) $this->option('limit');

        if ($provinceFilter) {
            $provinceFilter = strtoupper($provinceFilter);
            $provinces = array_values(array_filter($provinces, function ($province) use ($provinceFilter) {
                return strtoupper($province['code']) === $provinceFilter
                    || strtoupper($province['name']) === $provinceFilter;
            }));
        }

        if ($limit > 0) {
            $provinces = array_slice($provinces, 0, $limit);
        }

        return $provinces;
    }

    private function formatMoney($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }
}
