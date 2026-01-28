<?php

declare(strict_types=1);

namespace App\Repositories\Brand;

use App\Modules\Brand\Models\Brand;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for Brand data access.
 */
class BrandRepository extends BaseRepository implements BrandRepositoryInterface
{
    public function __construct(Brand $model)
    {
        parent::__construct($model);
    }

    public function model(): string
    {
        return Brand::class;
    }

    public function getActive(int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->where('status', 1)
            ->orderBy('sort', 'desc')
            ->limit($limit)
            ->get();
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['keyword'])) {
            $query->where('name', 'like', '%'.$filters['keyword'].'%');
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        $query->orderBy('sort', 'desc')->orderBy('id', 'desc');

        return $query->paginate($perPage)->appends($filters);
    }
}


