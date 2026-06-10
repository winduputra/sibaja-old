<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('swakelola_pencatatan', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun_anggaran')->nullable();
            $table->string('kd_klpd')->nullable();
            $table->string('nama_klpd')->nullable();
            $table->string('jenis_klpd')->nullable();
            $table->string('kd_satker')->nullable();
            $table->string('kd_satker_str')->nullable();
            $table->string('nama_satker')->nullable();
            $table->bigInteger('kd_lpse')->nullable();
            $table->bigInteger('kd_swakelola_pct')->unique();
            $table->string('kd_rup')->nullable();
            $table->text('nama_paket')->nullable();
            $table->decimal('pagu', 20, 2)->nullable();
            $table->decimal('total_realisasi', 20, 2)->nullable();
            $table->decimal('nilai_pdn_pct', 20, 2)->nullable();
            $table->decimal('nilai_umk_pct', 20, 2)->nullable();
            $table->string('sumber_dana')->nullable();
            $table->longText('uraian_pekerjaan')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swakelola_pencatatan');
    }
};
