<?php

declare(strict_types=1);

namespace App\Repositories\Brand;

use App\Modules\Brand\Models\Brand;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository contract for Brand entities.
 */
interface BrandRepositoryInterface extends RepositoryInterface
{
    /**
     * Get active brands.
     */
    public function getActive(int $limit = 50): Collection;

    /**
     * Paginate brands with optional filters.
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator;
}


