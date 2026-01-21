<?php
namespace App\Jobs\Inventory;

use App\Services\Inventory\Contracts\InventoryServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredReservationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(InventoryServiceInterface $inventory): void
    {
        $count = $inventory->releaseExpiredReservations();
        Log::info("Released {$count} expired reservations");
    }
}
