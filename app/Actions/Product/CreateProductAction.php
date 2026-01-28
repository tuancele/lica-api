<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\DTOs\Product\CreateProductDTO;
use App\Enums\ProductType;
use App\Modules\Product\Models\Product;
use App\Repositories\Product\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a Product with basic fields (Phase 2 architecture pattern).
 */
class CreateProductAction
{
    public function __construct(
        private ProductRepositoryInterface $products
    ) {}

    public function execute(CreateProductDTO $dto): Product
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            $data['type'] = ProductType::PRODUCT->value;

            /** @var Product $product */
            $product = $this->products->create($data);

            return $product;
        });
    }
}


