<?php

declare(strict_types=1);

namespace App\Services\Warehouse;

use App\Models\WarehouseV2;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service layer for Warehouse V2 (multi-warehouse metadata).
 *
 * Phase 2: keep logic thin, mainly delegating to WarehouseRepository.
 */
class WarehouseV2Service implements WarehouseV2ServiceInterface
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses
    ) {
    }

    public function getActive(): Collection
    {
        return $this->warehouses->getActive();
    }

    public function getDefault(): ?WarehouseV2
    {
        return $this->warehouses->getDefault();
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->warehouses->paginateWithFilters($filters, $perPage);
    }
}


