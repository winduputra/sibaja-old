<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('command')->default('update:all');
            $table->string('status');
            $table->boolean('is_global')->default(false);
            $table->json('modules')->nullable();
            $table->json('options')->nullable();
            $table->string('failed_module')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['is_global', 'status', 'finished_at'], 'api_sync_runs_global_status_finished_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_runs');
    }
};
