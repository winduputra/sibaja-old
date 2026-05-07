<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRupFieldsToEkatalogV6Pakets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ekatalog_v6_pakets', function (Blueprint $table) {
            $table->string('rup_code')->nullable()->after('kd_paket');
            $table->string('rup_name')->nullable()->after('rup_code');
            $table->string('kode_penyedia')->nullable()->after('rup_name');
            $table->string('rekan_id')->nullable()->after('kode_penyedia');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ekatalog_v6_pakets', function (Blueprint $table) {
            $table->dropColumn(['rup_code', 'rup_name', 'kode_penyedia', 'rekan_id']);
        });
    }
}
