<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$s = \App\Models\Swakelola::first();
echo json_encode($s);
echo PHP_EOL;
