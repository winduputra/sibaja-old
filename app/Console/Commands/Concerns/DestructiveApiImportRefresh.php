<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

trait DestructiveApiImportRefresh
{
    /**
     * @param array<int, class-string<Model>|string> $targets
     */
    protected function truncateTargets(array $targets): void
    {
        foreach (array_values(array_unique($targets)) as $target) {
            if (class_exists($target) && is_subclass_of($target, Model::class)) {
                $target::truncate();
                continue;
            }

            DB::table($target)->truncate();
        }
    }

    protected function reportDestructiveImportFailure(Throwable $throwable, string $commandName): int
    {
        $this->error("Perintah {$commandName} gagal: {$throwable->getMessage()}");
        $this->error('Silakan coba lagi dengan menjalankan perintah ini.');

        Log::error("Destructive API import failed for {$commandName}", [
            'exception' => $throwable,
        ]);

        return Command::FAILURE;
    }
}
