<?php

declare(strict_types=1);

namespace App\Repositories\Category;

use App\Modules\Category\Models\Category;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository contract for Category (post type = category).
 */
interface CategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all active root categories (no parent).
     */
    public function getActiveRoots(): Collection;

    /**
     * Get children for a given category.
     */
    public function getChildren(int $categoryId): Collection;

    /**
     * Get full category tree (eager loaded children).
     */
    public function getTree(): Collection;
}


