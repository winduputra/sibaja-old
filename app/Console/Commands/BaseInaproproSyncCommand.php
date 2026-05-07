<?php

namespace App\Console\Commands;

use App\Services\InaprocinaproApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

abstract class BaseInaproproSyncCommand extends Command
{
    protected $synced = 0;
    protected $errors = 0;
    protected $skipped = 0;
    protected $dryRun = false;
    protected $limit = 0;

    /**
     * Get the API endpoint to sync from
     */
    abstract protected function getEndpoint(): string;

    /**
     * Get the transformer class to use for field mapping
     */
    abstract protected function getTransformer(): string;

    /**
     * Get the model class to save data to
     */
    abstract protected function getModel(): string;

    /**
     * Get the endpoint configuration from config/api.php
     */
    abstract protected function getEndpointConfig(): array;

    /**
     * Apply any additional filters/conditions
     */
    protected function applyFilters(array $item): array
    {
        return $item;
    }

    /**
     * Validate data item before saving
     */
    protected function validateItem(array $item): bool
    {
        $endpoint = $this->getEndpointConfig();
        $required = $endpoint['required_fields'] ?? [];

        foreach ($required as $field) {
            if (!\array_key_exists($field, $item) || $item[$field] === null) {
                $this->logError("Missing required field: {$field}", $item);
                return false;
            }
        }

        return true;
    }

    /**
     * Handle the sync command
     */
    final public function handle()
    {
        $this->dryRun = $this->option('dry-run') ?? false;
        $this->limit = (int) ($this->option('limit') ?? 0);

        $this->info("=== INAPROC API Sync Command ===");
        $this->info("Endpoint: " . $this->getEndpoint());
        $this->line("Dry Run: " . ($this->dryRun ? 'Yes' : 'No'));
        if ($this->limit > 0) {
            $this->line("Limit: " . $this->limit);
        }
        $this->line("");

        try {
            $this->syncData();

            $this->info("Sync completed!");
            $this->line("Total synced: {$this->synced}");
            $this->line("Total errors: {$this->errors}");
            $this->line("Total skipped: {$this->skipped}");

            if ($this->dryRun) {
                $this->warn("Dry run mode - no data was saved to database");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Main sync logic
     */
    protected function syncData(): void
    {
        $apiClient = new InaprocinaproApiClient();
        $transformer = $this->getTransformer();
        $model = $this->getModel();
        $endpoint = $this->getEndpoint();
        $endpointConfig = $this->getEndpointConfig();
        $params = $endpointConfig['params'] ?? [];

        $itemCount = 0;

        $apiClient->paginate($endpoint, $params, function ($batch) use (
            $transformer,
            $model,
            &$itemCount
        ) {
            foreach ($batch as $item) {
                if ($this->limit > 0 && $itemCount >= $this->limit) {
                    $this->info("Limit reached: {$this->limit} items processed");
                    return;
                }

                try {
                    // Skip item if needed
                    if ($this->shouldSkipItem($item)) {
                        $this->skipped++;
                        continue;
                    }

                    // Validate required fields
                    if (!$this->validateItem($item)) {
                        $this->errors++;
                        continue;
                    }

                    // Transform data
                    $transformed = \call_user_func([$transformer, $this->getTransformMethod()], $item);

                    if (!$this->dryRun) {
                        // Save to database using updateOrCreate
                        $uniqueKey = $endpointConfig['unique_key'] ?? ['id'];
                        $uniqueData = [];

                        foreach ($uniqueKey as $key) {
                            $uniqueData[$key] = $transformed[$key] ?? null;
                        }

                        $model::updateOrCreate($uniqueData, $transformed);
                    }

                    $this->synced++;
                    $itemCount++;

                    // Progress indicator
                    if ($this->synced % 50 === 0) {
                        $this>info("Processed: {$this->synced} items");
                    }

                } catch (\Exception $e) {
                    $this->logError("Error processing item: " . $e->getMessage(), $item);
                    $this->errors++;
                }
            }
        });
    }

    /**
     * Check if item should be skipped
     */
    protected function shouldSkipItem(array $item): bool
    {
        $endpoint = $this->getEndpointConfig();

        if (isset($endpoint['skip_condition'])) {
            $skipCondition = $endpoint['skip_condition'];

            if (\is_callable($skipCondition)) {
                return $skipCondition($item);
            }
        }

        return false;
    }

    /**
     * Get the transformer method to call (e.g., 'pengumuman', 'ekontrakKontrak')
     * Override in subclass if needed
     */
    protected function getTransformMethod(): string
    {
        // Default: use endpoint name parts
        $parts = \explode('_', \str_replace('rup_', '', \str_replace('tender_', '', $this->getEndpoint())));

        // Convert snake_case to camelCase
        return \lcfirst(\str_replace(' ', '', \ucwords(\str_replace('_', ' ', \implode('_', $parts)))));
    }

    /**
     * Log error with context
     */
    protected function logError(string $message, array $context = []): void
    {
        \Log::error($message, ['context' => $context]);
        $this->error($message);
    }

    /**
     * Get common options
     */
    protected function getOptions()
    {
        return \array_merge(parent::getOptions(), [
            ['dry-run', null, null, 'Preview changes without saving'],
            ['limit', 'l', null, 'Maximum items to process'],
        ]);
    }
}
