<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterEkatalogV5PaketsNumericColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE ekatalog_v5_pakets MODIFY kuantitas DECIMAL(20,2) NULL');
        DB::statement('ALTER TABLE ekatalog_v5_pakets MODIFY harga_satuan DECIMAL(20,2) NULL');
        DB::statement('ALTER TABLE ekatalog_v5_pakets MODIFY ongkos_kirim DECIMAL(20,2) NULL');
        DB::statement('ALTER TABLE ekatalog_v5_pakets MODIFY total_harga DECIMAL(20,2) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE ekatalog_v5_pakets MODIFY kuantitas DOUBLE(8,2) NULL');
        DB::statement('ALTER TABLE ekatalog_v5_pakets MODIFY harga_satuan DOUBLE(8,2) NULL');
        DB::statement('ALTER TABLE ekatalog_v5_pakets MODIFY ongkos_kirim DOUBLE(8,2) NULL');
        DB::statement('ALTER TABLE ekatalog_v5_pakets MODIFY total_harga DOUBLE(8,2) NULL');
    }
}
