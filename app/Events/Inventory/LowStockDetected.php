<?php

declare(strict_types=1);
namespace App\Events\Inventory;
use App\Models\InventoryStock;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockDetected { use Dispatchable, SerializesModels; public function __construct(public InventoryStock $stock) {} }
