<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('swakelola_pencatatan')) {
            return;
        }

        DB::statement('ALTER TABLE swakelola_pencatatan MODIFY kd_rup VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('swakelola_pencatatan')) {
            return;
        }

        DB::statement('ALTER TABLE swakelola_pencatatan MODIFY kd_rup BIGINT NULL');
    }
};
