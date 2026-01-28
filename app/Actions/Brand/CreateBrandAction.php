<?php

declare(strict_types=1);

namespace App\Actions\Brand;

use App\DTOs\Brand\CreateBrandDTO;
use App\Modules\Brand\Models\Brand;
use App\Repositories\Brand\BrandRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a Brand using repository + DTO.
 */
class CreateBrandAction
{
    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository
    ) {
    }

    public function execute(CreateBrandDTO $dto): Brand
    {
        return DB::transaction(function () use ($dto) {
            /** @var Brand $brand */
            $brand = $this->brandRepository->create($dto->toArray());

            return $brand;
        });
    }
}


