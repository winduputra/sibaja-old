<?php

namespace App\Providers;

use App\Services\ApiSyncRunRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }


    public function boot()
    {
        $nonTenderYears = [];
        $tenderYears = [];
        $lastGlobalApiSync = null;

        if (Schema::hasTable('non_tender_pengumuman')) {
            $nonTenderYears = DB::table('non_tender_pengumuman')
                ->selectRaw("YEAR(tgl_buat_paket) as year")
                ->distinct()
                ->pluck('year')
                ->map(fn($year) => (int) $year)
                ->sortDesc()
                ->values()
                ->toArray();
        }

        if (Schema::hasTable('tender_pengumuman_data')) {
            $tenderYears = DB::table('tender_pengumuman_data')
                ->selectRaw("YEAR(tgl_buat_paket) as year")
                ->distinct()
                ->pluck('year')
                ->map(fn($year) => (int) $year)
                ->sortDesc()
                ->values()
                ->toArray();
        }

        if (Schema::hasTable('api_sync_runs')) {
            $lastGlobalApiSync = (new ApiSyncRunRecorder())->latestSuccessfulGlobalRun();
        }

        // Bagikan ke semua view
        View::share([
            'nonTenderYears' => $nonTenderYears,
            'tenderYears' => $tenderYears,
            'lastGlobalApiSync' => $lastGlobalApiSync,
        ]);

    }
}
