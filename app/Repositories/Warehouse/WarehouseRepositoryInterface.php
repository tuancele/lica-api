<?php

declare(strict_types=1);

namespace App\Repositories\Warehouse;

use App\Models\WarehouseV2;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository contract for Warehouse V2 (warehouses_v2).
 */
interface WarehouseRepositoryInterface extends RepositoryInterface
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
     * Paginate warehouses with filters (code/name/active).
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator;
}


