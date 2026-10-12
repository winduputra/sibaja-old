<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kontrak_data', function (Blueprint $table) {
            $table->timestamp('tgl_sppbj')->nullable()->after('no_sppbj');
            $table->timestamp('tgl_spmk_spp')->nullable()->after('tgl_kontrak_akhir');
            $table->timestamp('tgl_bap')->nullable()->after('tgl_spmk_spp');
            $table->timestamp('tgl_bast')->nullable()->after('tgl_bap');
        });
    }

    public function down(): void
    {
        Schema::table('kontrak_data', function (Blueprint $table) {
            $table->dropColumn(['tgl_sppbj', 'tgl_spmk_spp', 'tgl_bap', 'tgl_bast']);
        });
    }
};
