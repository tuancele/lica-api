<?php

declare(strict_types=1);
namespace App\Services\Gmc;

use App\Modules\Product\Models\Variant;

class GmcOfferId
{
    public function forVariant(Variant $variant): string
    {
        $strategy = (string) config('gmc.offer_id_strategy', 'sku');

        if ($strategy === 'variant_id') {
            return (string) $variant->id;
        }

        $sku = trim((string) ($variant->sku ?? ''));
        if ($sku !== '') {
            return $sku;
        }

        return (string) $variant->id;
    }
}



