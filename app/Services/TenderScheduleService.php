<?php

namespace App\Services;

use App\Support\LegacyIsbImportDisabled;

class TenderScheduleService
{

    public static function getByCode($code)
    {
        LegacyIsbImportDisabled::forService();
    }

}
