<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrateToInaprocApi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // TRUNCATE tables to clear old API data
        // This completely removes all existing data - ensure backup is taken first!
        $tables = [
            'satkers',
            'penyedias',
            'tender_pengumuman_data',
            'tender_selesai_nilai_data',
            'non_tender_pengumuman',
            'non_tender_selesai',
            'non_tender_contract',
            'non_tender_realisasi',
            'ekatalog_v6_pakets',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("TRUNCATE TABLE `{$table}`");
            }
        }

        // Add metadata columns to satkers table
        if (Schema::hasTable('satkers')) {
            Schema::table('satkers', function (Blueprint $table) {
                // Add new column for tracking tahun_aktif as JSON (can store multiple years)
                if (!Schema::hasColumn('satkers', 'tahun_aktif_json')) {
                    $table->json('tahun_aktif_json')->nullable()->after('tahun_anggaran')->comment('Array of active years');
                }

                // Add metadata columns
                if (!Schema::hasColumn('satkers', 'sync_source')) {
                    $table->string('sync_source')->default('inaproc_v1')->after('kode_eselon')->comment('Data source API');
                }
                if (!Schema::hasColumn('satkers', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable()->after('sync_source');
                }
                if (!Schema::hasColumn('satkers', 'migrated_at')) {
                    $table->timestamp('migrated_at')->nullable()->after('last_synced_at')->comment('When migrated to new API');
                }
            });
        }

        // Add metadata columns to penyedias table
        if (Schema::hasTable('penyedias')) {
            Schema::table('penyedias', function (Blueprint $table) {
                if (!Schema::hasColumn('penyedias', 'sync_source')) {
                    $table->string('sync_source')->default('inaproc_v1')->after('urarian_pekerjaan')->comment('Data source API');
                }
                if (!Schema::hasColumn('penyedias', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable()->after('sync_source');
                }
                if (!Schema::hasColumn('penyedias', 'migrated_at')) {
                    $table->timestamp('migrated_at')->nullable()->after('last_synced_at');
                }
            });
        }

        // Add metadata columns to tender_pengumuman_data table
        if (Schema::hasTable('tender_pengumuman_data')) {
            Schema::table('tender_pengumuman_data', function (Blueprint $table) {
                if (!Schema::hasColumn('tender_pengumuman_data', 'sync_source')) {
                    $table->string('sync_source')->default('inaproc_v1')->comment('Data source API');
                }
                if (!Schema::hasColumn('tender_pengumuman_data', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable();
                }
                if (!Schema::hasColumn('tender_pengumuman_data', 'migrated_at')) {
                    $table->timestamp('migrated_at')->nullable();
                }
            });
        }

        // Add metadata columns to tender_selesai_nilai_data table
        if (Schema::hasTable('tender_selesai_nilai_data')) {
            Schema::table('tender_selesai_nilai_data', function (Blueprint $table) {
                if (!Schema::hasColumn('tender_selesai_nilai_data', 'sync_source')) {
                    $table->string('sync_source')->default('inaproc_v1')->comment('Data source API');
                }
                if (!Schema::hasColumn('tender_selesai_nilai_data', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable();
                }
                if (!Schema::hasColumn('tender_selesai_nilai_data', 'migrated_at')) {
                    $table->timestamp('migrated_at')->nullable();
                }
            });
        }

        // Add metadata columns to non_tender_pengumuman table
        if (Schema::hasTable('non_tender_pengumuman')) {
            Schema::table('non_tender_pengumuman', function (Blueprint $table) {
                if (!Schema::hasColumn('non_tender_pengumuman', 'sync_source')) {
                    $table->string('sync_source')->default('inaproc_v1')->comment('Data source API');
                }
                if (!Schema::hasColumn('non_tender_pengumuman', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable();
                }
                if (!Schema::hasColumn('non_tender_pengumuman', 'migrated_at')) {
                    $table->timestamp('migrated_at')->nullable();
                }
            });
        }

        // Add metadata columns to non_tender_selesai table
        if (Schema::hasTable('non_tender_selesai')) {
            Schema::table('non_tender_selesai', function (Blueprint $table) {
                if (!Schema::hasColumn('non_tender_selesai', 'sync_source')) {
                    $table->string('sync_source')->default('inaproc_v1')->comment('Data source API');
                }
                if (!Schema::hasColumn('non_tender_selesai', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable();
                }
                if (!Schema::hasColumn('non_tender_selesai', 'migrated_at')) {
                    $table->timestamp('migrated_at')->nullable();
                }
            });
        }

        // Add metadata columns to non_tender_contract table
        if (Schema::hasTable('non_tender_contract')) {
            Schema::table('non_tender_contract', function (Blueprint $table) {
                if (!Schema::hasColumn('non_tender_contract', 'sync_source')) {
                    $table->string('sync_source')->default('inaproc_v1')->comment('Data source API');
                }
                if (!Schema::hasColumn('non_tender_contract', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable();
                }
                if (!Schema::hasColumn('non_tender_contract', 'migrated_at')) {
                    $table->timestamp('migrated_at')->nullable();
                }
            });
        }

        // Add metadata columns to non_tender_realisasi table
        if (Schema::hasTable('non_tender_realisasi')) {
            Schema::table('non_tender_realisasi', function (Blueprint $table) {
                if (!Schema::hasColumn('non_tender_realisasi', 'sync_source')) {
                    $table->string('sync_source')->default('inaproc_v1')->comment('Data source API');
                }
                if (!Schema::hasColumn('non_tender_realisasi', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable();
                }
                if (!Schema::hasColumn('non_tender_realisasi', 'migrated_at')) {
                    $table->timestamp('migrated_at')->nullable();
                }
            });
        }

        // Add metadata columns to ekatalog_v6_pakets table
        if (Schema::hasTable('ekatalog_v6_pakets')) {
            Schema::table('ekatalog_v6_pakets', function (Blueprint $table) {
                if (!Schema::hasColumn('ekatalog_v6_pakets', 'sync_source')) {
                    $table->string('sync_source')->default('inaproc_v1')->comment('Data source API');
                }
                if (!Schema::hasColumn('ekatalog_v6_pakets', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable();
                }
                if (!Schema::hasColumn('ekatalog_v6_pakets', 'migrated_at')) {
                    $table->timestamp('migrated_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Rollback: drop added columns (but keep tables)
        $columns = ['sync_source', 'last_synced_at', 'migrated_at'];

        foreach (['satkers', 'penyedias', 'tender_pengumuman_data', 'tender_selesai_nilai_data',
                  'non_tender_pengumuman', 'non_tender_selesai', 'non_tender_contract',
                  'non_tender_realisasi', 'ekatalog_v6_pakets'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    foreach ($columns as $column) {
                        if (Schema::hasColumn($table->getTable(), $column)) {
                            $table->dropColumn($column);
                        }
                    }
                    // Drop tahun_aktif_json for satkers
                    if ($table->getTable() === 'satkers' &&
                        Schema::hasColumn($table->getTable(), 'tahun_aktif_json')) {
                        $table->dropColumn('tahun_aktif_json');
                    }
                });
            }
        }
    }
}
