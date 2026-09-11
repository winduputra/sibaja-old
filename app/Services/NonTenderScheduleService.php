<?php

namespace App\Services;

use App\Support\LegacyIsbImportDisabled;

class NonTenderScheduleService
{

    public static function getByCode($code)
    {
        LegacyIsbImportDisabled::forService();
    }

}
