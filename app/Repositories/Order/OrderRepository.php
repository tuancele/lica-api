<?php

declare(strict_types=1);

namespace App\Repositories\Order;

use App\Modules\Order\Models\Order;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repository for Order read operations.
 */
class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function model(): string
    {
        return Order::class;
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['ship'])) {
            $query->where('ship', $filters['ship']);
        }

        if (! empty($filters['code'])) {
            $query->where('code', $filters['code']);
        }

        if (! empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('email', 'like', '%'.$keyword.'%')
                    ->orWhere('phone', 'like', '%'.$keyword.'%');
            });
        }

        return $query->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($filters);
    }

    public function findByCodeWithDetails(string $code): ?Order
    {
        return $this->model->newQuery()
            ->where('code', $code)
            ->with(['detail', 'province', 'district', 'ward', 'promotion', 'member'])
            ->first();
    }
}


