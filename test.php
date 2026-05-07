<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$r = \App\Models\Penyedia::where('kd_rup', '61019127')->first();
if ($r) {
    echo "kd_rup: " . $r->kd_rup . "\n";
    echo "status_aktif_rup: " . $r->status_aktif_rup . "\n";
    echo "last_synced_at: " . ($r->last_synced_at ? $r->last_synced_at->toDateTimeString() : 'NULL') . "\n";
    echo "updated_at: " . $r->updated_at->toDateTimeString() . "\n";
} else {
    echo "Not found\n";
}
