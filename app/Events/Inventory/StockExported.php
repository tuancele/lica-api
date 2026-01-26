<?php

declare(strict_types=1);

namespace App\Events\Inventory;

use App\Models\StockReceipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockExported
{
    use Dispatchable, SerializesModels;

    public function __construct(public StockReceipt $receipt) {}
}
