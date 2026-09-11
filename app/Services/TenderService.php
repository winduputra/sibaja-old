<?php

namespace App\Services;

use App\Support\LegacyIsbImportDisabled;

class TenderService
{
    public static function getAll($year = null, $lpse = "121")
    {
        LegacyIsbImportDisabled::forService();
    }

    public static function getDone($year = null, $lpse = "121")
    {
        LegacyIsbImportDisabled::forService();
    }
}
