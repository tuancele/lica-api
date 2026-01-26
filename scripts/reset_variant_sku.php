<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$chunk = 1000;
$total = DB::table('variants')->count();
$updated = 0;

// Precompute product variant counts
$productCounts = DB::table('variants')
    ->select('product_id', DB::raw('count(*) as c'))
    ->groupBy('product_id')
    ->pluck('c', 'product_id');

$now = Carbon::now();

DB::table('variants')
    ->select('id', 'product_id')
    ->orderBy('id')
    ->chunk($chunk, function ($rows) use (&$updated, $productCounts, $now) {
        foreach ($rows as $row) {
            $pid = (int) $row->product_id;
            $vid = (int) $row->id;
            $count = (int) ($productCounts[$pid] ?? 1);

            // If product has only 1 variant -> LC{PRODUCT_ID}VN
            // If product has many variants -> LC{PRODUCT_ID}VN-V{VARIANT_ID}
            $newSku = ($count > 1)
                ? 'LC'.$pid.'VN-V'.$vid
                : 'LC'.$pid.'VN';

            DB::table('variants')->where('id', $vid)->update([
                'sku' => $newSku,
                'updated_at' => $now,
            ]);
            $updated++;
        }
    });

$duplicateCount = DB::table('variants')
    ->select('sku', DB::raw('count(*) as c'))
    ->groupBy('sku')
    ->having('c', '>', 1)
    ->count();

echo "total_variants={$total}\n";
echo "updated={$updated}\n";
echo "duplicate_skus_after_update={$duplicateCount}\n";
