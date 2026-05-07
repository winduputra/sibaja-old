<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterEkatalogV6PaketsColumnTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use raw SQL to change column types without requiring Doctrine DBAL
        DB::statement('ALTER TABLE ekatalog_v6_pakets MODIFY total_harga DECIMAL(20,2) NULL');
        DB::statement('ALTER TABLE ekatalog_v6_pakets MODIFY ongkir DECIMAL(20,2) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert back to original types
        DB::statement('ALTER TABLE ekatalog_v6_pakets MODIFY total_harga DOUBLE(8,2) NULL');
        DB::statement('ALTER TABLE ekatalog_v6_pakets MODIFY ongkir DOUBLE(8,2) NULL');
    }
}
