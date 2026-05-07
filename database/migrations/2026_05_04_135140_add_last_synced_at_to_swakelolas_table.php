<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastSyncedAtToSwakelolasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('swakelolas', function (Blueprint $table) {
            $table->timestamp('last_synced_at')->nullable()->after('status_umumkan_rup');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('swakelolas', function (Blueprint $table) {
            $table->dropColumn('last_synced_at');
        });
    }
}
