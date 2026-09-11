<?php

namespace App\Services;

use App\Support\LegacyIsbImportDisabled;

class TenderParticipantService
{

    public static function getByCode($code)
    {
        LegacyIsbImportDisabled::forService();
    }

}
