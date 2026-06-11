<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRekapitulasiNasionalTable extends Migration
{
    public function up()
    {
        Schema::create('rekapitulasi_nasional', function (Blueprint $table) {
            $table->id();
            $table->string('province_code', 20)->unique();
            $table->string('province_name', 100);
            $table->text('source_url');
            $table->decimal('penyedia_realisasi', 20, 2)->default(0);
            $table->decimal('penyedia_perencanaan', 20, 2)->default(0);
            $table->decimal('penyedia_persentase', 6, 2)->default(0);
            $table->decimal('swakelola_realisasi', 20, 2)->default(0);
            $table->decimal('swakelola_perencanaan', 20, 2)->default(0);
            $table->decimal('swakelola_persentase', 6, 2)->default(0);
            $table->decimal('total_realisasi', 20, 2)->default(0);
            $table->decimal('total_perencanaan', 20, 2)->default(0);
            $table->decimal('total_persentase', 6, 2)->default(0);
            $table->string('raw_text_hash', 64)->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rekapitulasi_nasional');
    }
}
