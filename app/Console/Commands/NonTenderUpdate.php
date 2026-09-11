<?php

namespace App\Console\Commands;

use App\Support\LegacyIsbImportDisabled;
use Illuminate\Console\Command;

class NonTenderUpdate extends Command
{
    protected $signature = 'nontender:update';
    protected $description = 'Dinonaktifkan: impor legacy ISB non-tender.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
