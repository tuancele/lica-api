<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$now = Carbon::now();

$affected = DB::table('inventory_stocks')->update([
    'physical_stock' => 0,
    'reserved_stock' => 0,
    'flash_sale_hold' => 0,
    'deal_hold' => 0,
    'last_stock_check' => null,
    'last_movement_at' => null,
    'updated_at' => $now,
]);

echo "rows_updated={$affected}\n";

