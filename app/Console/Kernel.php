<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // INAPROC API Sync Commands - Daily at off-peak hours (2 AM)
        // Order matters: Satker first (parent table), then child tables

        $schedule->command('inaproc:sync-rup --type=satker')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('INAPROC: RUP Master Satker sync failed');
            })
            ->onSuccess(function () {
                \Log::info('INAPROC: RUP Master Satker synced successfully');
            });

        $schedule->command('inaproc:sync-rup --type=penyedia')
            ->dailyAt('02:15')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('INAPROC: RUP Paket Penyedia sync failed');
            })
            ->onSuccess(function () {
                \Log::info('INAPROC: RUP Paket Penyedia synced successfully');
            });

        $schedule->command('inaproc:sync-rup --type=swakelola')
            ->dailyAt('02:20')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('INAPROC: RUP Paket Swakelola sync failed');
            })
            ->onSuccess(function () {
                \Log::info('INAPROC: RUP Paket Swakelola synced successfully');
            });

        $schedule->command('inaproc:sync-rup --type=history-kaji-ulang')
            ->dailyAt('02:25')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('INAPROC: RUP History Kaji Ulang sync failed');
            })
            ->onSuccess(function () {
                \Log::info('INAPROC: RUP History Kaji Ulang synced successfully');
            });

        $schedule->command('inaproc:sync-tender')
            ->dailyAt('02:35')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('INAPROC: Tender sync failed');
            })
            ->onSuccess(function () {
                \Log::info('INAPROC: Tender synced successfully');
            });

        $schedule->command('inaproc:sync-non-tender')
            ->dailyAt('02:50')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('INAPROC: Non-Tender sync failed');
            })
            ->onSuccess(function () {
                \Log::info('INAPROC: Non-Tender synced successfully');
            });

        $schedule->command('inaproc:sync-ekatalog-v6')
            ->dailyAt('03:05')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('INAPROC: E-Katalog V6 sync failed');
            })
            ->onSuccess(function () {
                \Log::info('INAPROC: E-Katalog V6 synced successfully');
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
