<?php

declare(strict_types=1);

namespace App\Services\Warehouse;

use App\Models\WarehouseV2;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for Warehouse V2 operations (warehouses_v2 table).
 */
interface WarehouseV2ServiceInterface
{
    /**
     * Get all active warehouses.
     */
    public function getActive(): Collection;

    /**
     * Get default warehouse (if any).
     */
    public function getDefault(): ?WarehouseV2;

    /**
     * Paginate warehouses with filters (code/name/is_active).
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator;
}


