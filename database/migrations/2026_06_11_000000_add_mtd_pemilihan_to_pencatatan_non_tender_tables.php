<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMtdPemilihanToPencatatanNonTenderTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('non_tender_pencatatan') && !Schema::hasColumn('non_tender_pencatatan', 'mtd_pemilihan')) {
            Schema::table('non_tender_pencatatan', function (Blueprint $table) {
                $table->string('mtd_pemilihan')->nullable()->after('nama_paket');
            });
        }

        if (Schema::hasTable('non_tender_realisasi') && !Schema::hasColumn('non_tender_realisasi', 'mtd_pemilihan')) {
            Schema::table('non_tender_realisasi', function (Blueprint $table) {
                $table->string('mtd_pemilihan')->nullable()->after('nama_paket');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('non_tender_pencatatan') && Schema::hasColumn('non_tender_pencatatan', 'mtd_pemilihan')) {
            Schema::table('non_tender_pencatatan', function (Blueprint $table) {
                $table->dropColumn('mtd_pemilihan');
            });
        }

        if (Schema::hasTable('non_tender_realisasi') && Schema::hasColumn('non_tender_realisasi', 'mtd_pemilihan')) {
            Schema::table('non_tender_realisasi', function (Blueprint $table) {
                $table->dropColumn('mtd_pemilihan');
            });
        }
    }
}
