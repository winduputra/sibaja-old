<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateAllCommand extends Command
{
    protected $signature = 'update:all {--tahun=2026} {--all-years} {--dry-run} {--limit=0} {--only=} {--skip=}';
    protected $description = 'Sync ALL data from INAPROC API (RUP, Tender, Non-Tender, Pencatatan Swakelola, E-Katalog)';

    protected $totalSynced = 0;
    protected $totalErrors = 0;
    protected $totalSkipped = 0;
    protected $startTime;

    public function handle()
    {
        $this->startTime = microtime(true);

        $tahun = $this->option('tahun');
        $allYears = $this->option('all-years');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        $only = $this->option('only');
        $skip = $this->option('skip');

        // Parse only and skip options
        $onlyModules = $only ? array_map('trim', explode(',', $only)) : null;
        $skipModules = $skip ? array_map('trim', explode(',', $skip)) : [];

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
            // Define all modules to sync
            $modules = [
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
                'ekatalog-v5' => [
                    'name' => 'E-Katalog V5 Data',
                    'command' => 'ekatalog:update',
                    'options' => [],
                    'passes_global_options' => false,
                ],
                'ekatalog' => [
                    'name' => 'E-Katalog V6 Data',
                    'command' => 'inaproc:sync-ekatalog-v6',
                    'options' => [],
                ],
            ];

            // Filter modules based on --only and --skip
            $modulesToRun = [];
            foreach ($modules as $key => $module) {
                $shouldRun = true;

                // Check --only filter
                if ($onlyModules !== null && !in_array($key, $onlyModules)) {
                    $shouldRun = false;
                }

                // Check --skip filter
                if (in_array($key, $skipModules)) {
                    $shouldRun = false;
                }

                if ($shouldRun) {
                    $modulesToRun[$key] = $module;
                }
            }

            if (empty($modulesToRun)) {
                $this->error("❌ No modules to run!");
                return 1;
            }

            $this->info("📦 Modules to sync: " . implode(', ', array_keys($modulesToRun)));
            $this->line("");

            // Run each module
            $moduleCount = count($modulesToRun);
            $currentModule = 0;

            foreach ($modulesToRun as $key => $module) {
                $currentModule++;
                $this->printModuleHeader($module['name'], $currentModule, $moduleCount);

                // Build command options
                $passesGlobalOptions = $module['passes_global_options'] ?? true;
                $options = $passesGlobalOptions ? [
                    '--tahun' => $tahun,
                ] : [];

                // Add module-specific options
                foreach ($module['options'] as $optionKey => $optionValue) {
                    $options['--' . $optionKey] = $optionValue;
                }

                if ($passesGlobalOptions && $allYears) {
                    $options['--all-years'] = true;
                }

                if ($passesGlobalOptions && $dryRun) {
                    $options['--dry-run'] = true;
                }

                if ($passesGlobalOptions && $limit > 0) {
                    $options['--limit'] = $limit;
                }

                // Run the command
                try {
                    $exitCode = $this->call($module['command'], $options);
                    
                    if ($exitCode === 0) {
                        $this->info("✅ {$module['name']} synced successfully!");
                    } else {
                        $this->error("⚠️ {$module['name']} finished with warnings/errors");
                        $this->totalErrors++;
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error syncing {$module['name']}: " . $e->getMessage());
                    $this->totalErrors++;
                    Log::error("UpdateAllCommand: Error syncing {$key}", ['error' => $e->getMessage()]);
                }

                $this->line("");
            }

            $this->printSummary();

            if ($dryRun) {
                $this->warn("⚠️ Dry run mode - no data was saved to database");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Fatal error: " . $e->getMessage());
            Log::error("UpdateAllCommand failed", ['error' => $e->getMessage()]);
            return 1;
        }
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
        $this->line("  - Total Modules: 6");
        $this->line("  - With Errors: {$this->totalErrors}");
        $this->line("  - Duration: {$duration}s");
        $this->line("");
    }
}
