<?php

namespace App\Console\Commands;

use App\Models\ApiSyncRun;
use App\Services\ApiSyncRunRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class UpdateAllCommand extends Command
{
    protected $signature = 'update:all {--tahun=2026} {--all-years} {--dry-run} {--limit=0} {--only=} {--skip=}';
    protected $description = 'Sync ALL data from INAPROC API (RUP, Tender, Non-Tender, Pencatatan Swakelola, E-Katalog, Rekap Nasional)';

    protected $totalSynced = 0;
    protected $totalErrors = 0;
    protected $totalSkipped = 0;
    protected $totalModules = 0;
    protected $startTime;

    public function handle(): int
    {
        $this->startTime = microtime(true);

        $tahun = $this->option('tahun');
        $allYears = (bool) $this->option('all-years');
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $only = $this->option('only');
        $skip = $this->option('skip');
        $onlyModules = $this->parseModuleList($only);
        $skipModules = $this->parseModuleList($skip) ?? [];
        $syncRun = null;

        $this->printHeader();
        $this->line("Options:");
        $this->line("  - Year: " . ($allYears ? "ALL SUPPORTED YEARS" : $tahun));
        $this->line("  - Dry Run: " . ($dryRun ? "YES" : "NO"));
        $this->line("  - Limit: " . ($limit > 0 ? $limit : "UNLIMITED"));
        if ($only) {
            $this->line("  - Only: " . $only);
        }
        if ($skip) {
            $this->line("  - Skip: " . $skip);
        }
        $this->line("");

        try {
            $modules = $this->moduleDefinitions();
            $modulesToRun = $this->filterModulesToRun($modules, $onlyModules, $skipModules);

            if (empty($modulesToRun)) {
                $this->error("❌ No modules to run!");
                return 1;
            }

            $this->info("📦 Modules to sync: " . implode(', ', array_keys($modulesToRun)));
            $this->line("");

            $recorder = $this->syncRunRecorder();
            $syncRun = $this->startSyncRun(
                $recorder,
                $modulesToRun,
                $tahun,
                $allYears,
                $dryRun,
                $limit,
                $onlyModules,
                $skipModules
            );

            $this->totalModules = count($modulesToRun);
            $currentModule = 0;

            foreach ($modulesToRun as $key => $module) {
                $currentModule++;
                $this->printModuleHeader($module['name'], $currentModule, $this->totalModules);

                $options = $this->buildChildOptions($module, $tahun, $allYears, $dryRun, $limit);

                try {
                    $exitCode = Artisan::call($module['command'], $options);
                } catch (\Throwable $throwable) {
                    $this->failSyncRun($recorder, $syncRun, $key, $throwable->getMessage());
                    $this->error("❌ Error syncing {$module['name']}: " . $throwable->getMessage());
                    $this->error('Silakan coba lagi dengan menjalankan perintah berikut:');
                    $this->error('php artisan ' . $this->formatRetryCommand($module['command'], $options));
                    Log::error("UpdateAllCommand: Error syncing {$key}", ['error' => $throwable->getMessage()]);

                    return Command::FAILURE;
                }

                if ($exitCode !== Command::SUCCESS) {
                    $this->failSyncRun(
                        $recorder,
                        $syncRun,
                        $key,
                        "{$module['name']} finished with exit code {$exitCode}"
                    );
                    $this->error("❌ {$module['name']} finished with exit code {$exitCode}");
                    $this->error('Silakan coba lagi dengan menjalankan perintah berikut:');
                    $this->error('php artisan ' . $this->formatRetryCommand($module['command'], $options));
                    Log::error("UpdateAllCommand: Non-zero exit for {$key}", ['exit_code' => $exitCode]);

                    return Command::FAILURE;
                }

                $this->info("✅ {$module['name']} synced successfully!");

                $this->line("");
            }

            $this->printSummary();

            if ($dryRun) {
                $this->warn("⚠️ Dry run mode - no data was saved to database");
            }

            $this->finalizeSyncRun($recorder, $syncRun, $modulesToRun, $onlyModules, $skipModules, $limit, $dryRun);

            return Command::SUCCESS;

        } catch (\Throwable $throwable) {
            $this->failSyncRunIfRunning($recorder ?? null, $syncRun, $throwable->getMessage());
            $this->error("Fatal error: " . $throwable->getMessage());
            Log::error("UpdateAllCommand failed", ['error' => $throwable->getMessage()]);
            return Command::FAILURE;
        }
    }

    /**
     * @param array<string, array{name: string, command: string, options: array<string, mixed>, passes_year_options?: bool}> $modules
     * @return array<string, array{name: string, command: string, options: array<string, mixed>, passes_year_options?: bool}>
     */
    protected function filterModulesToRun(array $modules, ?array $onlyModules, array $skipModules): array
    {
        $modulesToRun = [];

        foreach ($modules as $key => $module) {
            $shouldRun = true;

            if ($onlyModules !== null && !in_array($key, $onlyModules, true)) {
                $shouldRun = false;
            }

            if (in_array($key, $skipModules, true)) {
                $shouldRun = false;
            }

            if ($shouldRun) {
                $modulesToRun[$key] = $module;
            }
        }

        return $modulesToRun;
    }

    /**
     * @return array<string, array{name: string, command: string, options: array<string, mixed>, passes_year_options?: bool}>
     */
    protected function moduleDefinitions(): array
    {
        return [
            'rup' => [
                'name' => 'RUP Data',
                'command' => 'inaproc:sync-rup',
                'options' => ['type' => 'all'],
            ],
            'tender' => [
                'name' => 'Tender Data',
                'command' => 'inaproc:sync-tender',
                'options' => ['type' => 'all'],
            ],
            'non-tender' => [
                'name' => 'Non-Tender Data',
                'command' => 'inaproc:sync-non-tender',
                'options' => ['type' => 'all'],
            ],
            'pencatatan-swakelola' => [
                'name' => 'Pencatatan Swakelola Data',
                'command' => 'inaproc:sync-pencatatan-swakelola',
                'options' => ['type' => 'all'],
            ],
            'ekatalog' => [
                'name' => 'E-Katalog V6 Data',
                'command' => 'inaproc:sync-ekatalog-v6',
                'options' => [],
            ],
            'rekap-nasional' => [
                'name' => 'Rekapitulasi Nasional Data',
                'command' => 'inaproc:sync-rekap-nasional',
                'options' => [],
                'passes_year_options' => false,
            ],
        ];
    }

    /**
     * @param array<string, array{name: string, command: string, options: array<string, mixed>, passes_year_options?: bool}> $module
     * @return array<string, int|string|bool>
     */
    protected function buildChildOptions(array $module, $tahun, bool $allYears, bool $dryRun, int $limit): array
    {
        $passesYearOptions = $module['passes_year_options'] ?? true;
        $options = $passesYearOptions ? ['--tahun' => $tahun] : [];

        foreach ($module['options'] as $optionKey => $optionValue) {
            $options['--' . $optionKey] = $optionValue;
        }

        if ($passesYearOptions && $allYears) {
            $options['--all-years'] = true;
        }

        if ($dryRun) {
            $options['--dry-run'] = true;
        }

        if ($limit > 0) {
            $options['--limit'] = $limit;
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeRunOptions($tahun, bool $allYears, bool $dryRun, int $limit, ?array $onlyModules, array $skipModules): array
    {
        return [
            'tahun' => is_numeric($tahun) ? (int) $tahun : $tahun,
            'all-years' => $allYears,
            'dry-run' => $dryRun,
            'limit' => $limit,
            'only' => $onlyModules === null ? null : implode(',', $onlyModules),
            'skip' => $skipModules === [] ? null : implode(',', $skipModules),
        ];
    }

    /**
     * @return array<int, string>|null
     */
    protected function parseModuleList($value): ?array
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $modules = array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $module): bool => $module !== ''));

        return $modules === [] ? null : $modules;
    }

    /**
     * @return array<string, mixed>
     */
    protected function startSyncRun(ApiSyncRunRecorder $recorder, array $modulesToRun, $tahun, bool $allYears, bool $dryRun, int $limit, ?array $onlyModules, array $skipModules): ApiSyncRun
    {
        return $recorder->start([
            'command' => 'update:all',
            'is_global' => false,
            'modules' => array_keys($modulesToRun),
            'options' => $this->normalizeRunOptions($tahun, $allYears, $dryRun, $limit, $onlyModules, $skipModules),
            'started_at' => now(),
        ]);
    }

    protected function finalizeSyncRun(ApiSyncRunRecorder $recorder, ?ApiSyncRun $syncRun, array $modulesToRun, ?array $onlyModules, array $skipModules, int $limit, bool $dryRun): void
    {
        if (!$syncRun instanceof ApiSyncRun) {
            return;
        }

        if ($dryRun) {
            $recorder->dryRun($syncRun, ['is_global' => false, 'finished_at' => now()]);

            return;
        }

        if ($this->isGlobalRun($modulesToRun, $onlyModules, $skipModules, $limit)) {
            $recorder->succeed($syncRun, ['is_global' => true, 'finished_at' => now()]);

            return;
        }

        $recorder->partial($syncRun, ['is_global' => false, 'finished_at' => now()]);
    }

    protected function failSyncRun(ApiSyncRunRecorder $recorder, ?ApiSyncRun $syncRun, string $failedModule, string $errorMessage): void
    {
        if (!$syncRun instanceof ApiSyncRun) {
            return;
        }

        $recorder->fail($syncRun, [
            'failed_module' => $failedModule,
            'error_message' => $errorMessage,
            'is_global' => false,
            'finished_at' => now(),
        ]);
    }

    protected function failSyncRunIfRunning(?ApiSyncRunRecorder $recorder, ?ApiSyncRun $syncRun, string $errorMessage): void
    {
        if (!$recorder instanceof ApiSyncRunRecorder || !$syncRun instanceof ApiSyncRun) {
            return;
        }

        if ($syncRun->status !== ApiSyncRun::STATUS_RUNNING) {
            return;
        }

        $recorder->fail($syncRun, [
            'error_message' => $errorMessage,
            'is_global' => false,
            'finished_at' => now(),
        ]);
    }

    protected function isGlobalRun(array $modulesToRun, ?array $onlyModules, array $skipModules, int $limit): bool
    {
        return $onlyModules === null
            && $skipModules === []
            && $limit === 0
            && array_keys($modulesToRun) === array_keys($this->moduleDefinitions())
            && count($modulesToRun) === 6;
    }

    protected function syncRunRecorder(): ApiSyncRunRecorder
    {
        return app(ApiSyncRunRecorder::class);
    }

    protected function formatRetryCommand(string $command, array $options): string
    {
        $segments = [$command];

        foreach ($options as $option => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $segments[] = $option;
                }

                continue;
            }

            $segments[] = $option . '=' . $value;
        }

        return implode(' ', $segments);
    }

    protected function printHeader(): void
    {
        $this->line("");
        $this->info("╔════════════════════════════════════════════════╗");
        $this->info("║   🚀 SIBAJA DATA SYNC - UPDATE ALL              ║");
        $this->info("║   Sync all data from INAPROC API                ║");
        $this->info("╚════════════════════════════════════════════════╝");
        $this->line("");
    }

    protected function printModuleHeader(string $name, int $current, int $total): void
    {
        $this->line("");
        $this->info("┌────────────────────────────────────────────────┐");
        $this->info("│ [{$current}/{$total}] {$name}");
        $this->info("└────────────────────────────────────────────────┘");
    }

    protected function printSummary(): void
    {
        $duration = round(microtime(true) - $this->startTime, 2);

        $this->line("");
        $this->info("╔════════════════════════════════════════════════╗");
        $this->info("║   ✨ UPDATE ALL COMPLETED                       ║");
        $this->info("╚════════════════════════════════════════════════╝");
        $this->line("");
        $this->line("📊 Summary:");
        $this->line("  - Total Modules: {$this->totalModules}");
        $this->line("  - With Errors: {$this->totalErrors}");
        $this->line("  - Duration: {$duration}s");
        $this->line("");
    }
}
