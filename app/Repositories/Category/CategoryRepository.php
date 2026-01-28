<?php

declare(strict_types=1);

namespace App\Repositories\Category;

use App\Modules\Category\Models\Category;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for Category data access (posts.type = category).
 */
class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function model(): string
    {
        return Category::class;
    }

    public function getActiveRoots(): Collection
    {
        return $this->model->newQuery()
            ->where('type', 'category')
            ->where('status', '1')
            ->whereNull('cat_id')
            ->orderBy('sort', 'asc')
            ->get();
    }

    public function getChildren(int $categoryId): Collection
    {
        return $this->model->newQuery()
            ->where('type', 'category')
            ->where('status', '1')
            ->where('cat_id', $categoryId)
            ->orderBy('sort', 'asc')
            ->get();
    }

    public function getTree(): Collection
    {
        return $this->model->newQuery()
            ->where('type', 'category')
            ->where('status', '1')
            ->whereNull('cat_id')
            ->with(['children' => function ($q) {
                $q->where('status', '1')->orderBy('sort', 'asc');
            }])
            ->orderBy('sort', 'asc')
            ->get();
    }
}


