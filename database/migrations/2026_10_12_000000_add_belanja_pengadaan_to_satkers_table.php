<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBelanjaPengadaanToSatkersTable extends Migration
{
    public function up(): void
    {
        Schema::table('satkers', function (Blueprint $table): void {
            $table->decimal('belanja_pengadaan', 20, 2)->nullable()->after('sync_source');
        });
    }

    public function down(): void
    {
        Schema::table('satkers', function (Blueprint $table): void {
            $table->dropColumn('belanja_pengadaan');
        });
    }
}
