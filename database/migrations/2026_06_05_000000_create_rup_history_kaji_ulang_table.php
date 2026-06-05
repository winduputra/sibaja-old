<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rup_history_kaji_ulang', function (Blueprint $table) {
            $table->id();
            $table->string('tahun_anggaran')->nullable()->index();
            $table->string('kd_klpd')->nullable()->index();
            $table->string('nama_klpd')->nullable();
            $table->string('jenis_klpd')->nullable();
            $table->string('jenis_paket')->nullable()->index();
            $table->string('jenis_revisi')->nullable()->index();
            $table->bigInteger('kd_rup_baru')->nullable()->index();
            $table->bigInteger('kd_rup_lama')->nullable()->index();
            $table->string('kd_satker')->nullable()->index();
            $table->string('kd_satker_str')->nullable();
            $table->text('nama_satker')->nullable();
            $table->timestamp('tgl_kaji_ulang')->nullable()->index();
            $table->text('alasan_kajiulang')->nullable();
            $table->timestamp('last_update_ref')->nullable();
            $table->string('payload_hash', 64)->unique();
            $table->json('raw_payload')->nullable();
            $table->string('sync_source')->default('inaproc_v1');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rup_history_kaji_ulang');
    }
};
