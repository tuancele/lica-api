<?php

declare(strict_types=1);

namespace App\Repositories\Order;

use App\Modules\Order\Models\Order;
use App\Repositories\Contracts.RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repository contract for Orders (read-focused for Phase 2).
 */
interface OrderRepositoryInterface extends RepositoryInterface
{
    /**
     * Paginate orders with filters similar to OrderController::index.
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find order by code with details.
     */
    public function findByCodeWithDetails(string $code): ?Order;
}


