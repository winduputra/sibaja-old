<?php

namespace App\Services;

use App\Support\LegacyIsbImportDisabled;

class SatkerService
{
    public static function getMaster($year = null, $klpd = "D264")
    {
        LegacyIsbImportDisabled::forService();
    }
}
