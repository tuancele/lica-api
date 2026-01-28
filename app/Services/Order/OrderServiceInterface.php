<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Modules\Order\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for Order-related business operations.
 */
interface OrderServiceInterface
{
    /**
     * Paginate orders with filters (used by admin OrderController + API).
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find order by code with details and relations.
     */
    public function findByCodeWithDetails(string $code): ?Order;
}


