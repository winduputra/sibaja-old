<?php

namespace App\Console\Commands;

use App\Models\Satker;
use App\Services\InaprocRekapNasionalBrowser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BelanjaPengadaanSyncCommand extends Command
{
    private const YEAR = '2026';
    private const SELECTOR = 'p.font-bold.tabular-nums';
    private const URL_TEMPLATE = 'https://data.inaproc.id/profil-pengadaan?tahun=2026&jenis=3&instansi=D264&satker=%s&view=Nilai';

    protected $signature = 'inaproc:sync-belanja-pengadaan {--dry-run} {--limit=0} {--timeout=90}';
    protected $description = 'Scrape per-satker Belanja Pengadaan values from rendered Inaproc pages';

    public function handle(InaprocRekapNasionalBrowser $browser): int
    {
        $timeout = (int) $this->option('timeout');
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');
        $synced = 0;
        $errors = 0;

        $satkers = Satker::query()
            ->select('id', 'kd_satker', 'kd_satker_str')
            ->where('tahun_anggaran', self::YEAR)
            ->whereNotNull('kd_satker_str')
            ->where('kd_satker_str', '!=', '')
            ->whereRaw("TRIM(kd_satker_str) != ''")
            ->orderBy('kd_satker')
            ->orderBy('kd_satker_str');

        if ($limit > 0) {
            $satkers->limit($limit);
        }

        foreach ($satkers->get() as $satker) {
            $code = trim((string) $satker->kd_satker_str);
            $url = sprintf(self::URL_TEMPLATE, $code);

            try {
                $text = $browser->extractFirst($url, self::SELECTOR, $timeout);
                $digits = preg_replace('/\D+/', '', $text);

                if ($digits === '') {
                    throw new RuntimeException('Belanja Pengadaan kosong untuk satker ' . $code . '.');
                }

                $value = (int) $digits;

                if (!$dryRun) {
                    $satker->update(['belanja_pengadaan' => $value]);
                }

                $synced++;
                $line = 'OK ' . $code . ': ' . $value;
                $this->info($line);
                Log::info('Belanja Pengadaan satker synced', [
                    'code' => $code,
                    'url' => $url,
                    'value' => $value,
                    'dry_run' => $dryRun,
                ]);
            } catch (\Throwable $throwable) {
                $errors++;
                $line = 'ERROR ' . $code . ': ' . $throwable->getMessage();
                $this->error($line);
                Log::error('Belanja Pengadaan satker sync failed', [
                    'code' => $code,
                    'url' => $url,
                    'error' => $throwable->getMessage(),
                ]);
            }
        }

        $line = "Selesai. Synced: {$synced}. Errors: {$errors}.";
        $this->line($line);

        if ($dryRun) {
            $this->warn('Dry run mode - tidak ada data yang disimpan.');
        }

        return Command::SUCCESS;
    }
}
