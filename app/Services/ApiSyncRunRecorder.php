<?php

namespace App\Services;

use App\Models\ApiSyncRun;
use InvalidArgumentException;

class ApiSyncRunRecorder
{
    public function record(array $attributes): ApiSyncRun
    {
        $this->assertValidStatus($attributes['status'] ?? null);

        $apiSyncRun = new ApiSyncRun();
        $apiSyncRun->fill($attributes);
        $apiSyncRun->save();

        return $apiSyncRun;
    }

    public function start(array $attributes = []): ApiSyncRun
    {
        return $this->record($this->withLifecycleAttributes($attributes, ApiSyncRun::STATUS_RUNNING, true, false));
    }

    public function succeed(ApiSyncRun $apiSyncRun, array $attributes = []): ApiSyncRun
    {
        return $this->updateLifecycleRun($apiSyncRun, $attributes, ApiSyncRun::STATUS_SUCCEEDED, true);
    }

    public function partial(ApiSyncRun $apiSyncRun, array $attributes = []): ApiSyncRun
    {
        return $this->updateLifecycleRun($apiSyncRun, $attributes, ApiSyncRun::STATUS_PARTIAL, true);
    }

    public function dryRun(ApiSyncRun $apiSyncRun, array $attributes = []): ApiSyncRun
    {
        return $this->updateLifecycleRun($apiSyncRun, $attributes, ApiSyncRun::STATUS_DRY_RUN, true);
    }

    public function fail(ApiSyncRun $apiSyncRun, array $attributes = []): ApiSyncRun
    {
        return $this->updateLifecycleRun($apiSyncRun, $attributes, ApiSyncRun::STATUS_FAILED, true);
    }

    public function updateProgress(ApiSyncRun $apiSyncRun, array $attributes = []): ApiSyncRun
    {
        $this->assertValidStatus($attributes['status'] ?? $apiSyncRun->status);

        $apiSyncRun->fill($attributes);
        $apiSyncRun->save();

        return $apiSyncRun;
    }

    public function latestSuccessfulGlobalRun(): ?ApiSyncRun
    {
        return ApiSyncRun::query()
            ->where('is_global', true)
            ->where('status', ApiSyncRun::STATUS_SUCCEEDED)
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->first();
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function withLifecycleAttributes(array $attributes, string $status, bool $setStartedAt, bool $setFinishedAt): array
    {
        $attributes['status'] = $status;

        if ($setStartedAt && !array_key_exists('started_at', $attributes)) {
            $attributes['started_at'] = now();
        }

        if ($setFinishedAt && !array_key_exists('finished_at', $attributes)) {
            $attributes['finished_at'] = now();
        }

        return $attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function updateLifecycleRun(ApiSyncRun $apiSyncRun, array $attributes, string $status, bool $setFinishedAt): ApiSyncRun
    {
        $apiSyncRun->fill($this->withLifecycleAttributes($attributes, $status, false, $setFinishedAt));
        $apiSyncRun->save();

        return $apiSyncRun;
    }

    private function assertValidStatus(?string $status): void
    {
        if ($status === null) {
            throw new InvalidArgumentException('Missing api sync run status.');
        }

        if (in_array($status, ApiSyncRun::STATUSES, true)) {
            return;
        }

        throw new InvalidArgumentException('Invalid api sync run status.');
    }
}
