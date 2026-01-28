<?php

declare(strict_types=1);

namespace App\Repositories\Warehouse;

use App\Models\WarehouseV2;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for Warehouse V2 data access.
 */
class WarehouseRepository extends BaseRepository implements WarehouseRepositoryInterface
{
    public function __construct(WarehouseV2 $model)
    {
        parent::__construct($model);
    }

    public function model(): string
    {
        return WarehouseV2::class;
    }

    public function getActive(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getDefault(): ?WarehouseV2
    {
        return WarehouseV2::getDefault();
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', '%'.$keyword.'%')
                    ->orWhere('name', 'like', '%'.$keyword.'%')
                    ->orWhere('address', 'like', '%'.$keyword.'%');
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('is_default', 'desc')
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->appends($filters);
    }
}


