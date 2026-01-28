<?php

declare(strict_types=1);

namespace App\Services\Cart\Contracts;

use App\Themes\Website\Models\Cart;

/**
 * Contract for pricing logic of Cart (Flash Sale + deal + normal price).
 */
interface CartPricingServiceInterface
{
    /**
     * Delegate to PriceEngineService for item pricing with quantity.
     */
    public function calculateItemPriceWithQuantity(int $productId, int $variantId, int $quantity): array;

    /**
     * Recalculate all prices for a given cart.
     *
     * For now this works with the Session-based Website Cart model.
     */
    public function recalculateCart(Cart $cart): Cart;
}


