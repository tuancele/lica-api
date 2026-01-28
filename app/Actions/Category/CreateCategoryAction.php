<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\DTOs\Category\CreateCategoryDTO;
use App\Modules\Category\Models\Category;
use App\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a Category using repository + DTO.
 */
class CreateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    public function execute(CreateCategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($dto) {
            /** @var Category $category */
            $category = $this->categoryRepository->create($dto->toArray());

            return $category;
        });
    }
}


