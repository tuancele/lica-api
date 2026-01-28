<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Modules\Order\Models\Order;
use App\Repositories\Order\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service layer for Order module (read-focused for Phase 2).
 *
 * Centralizes query logic using OrderRepository to keep controllers thin.
 */
class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders
    ) {
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->orders->paginateWithFilters($filters, $perPage);
    }

    public function findByCodeWithDetails(string $code): ?Order
    {
        return $this->orders->findByCodeWithDetails($code);
    }
}


