<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Themes\Website\Models\Cart;
use App\Services\Cart\Contracts\CartPricingServiceInterface;
use App\Services\Pricing\PriceEngineService;

/**
 * CartPricingService is a thin wrapper around PriceEngineService.
 *
 * Phase 2 goal: gradually move all price calculation branches from CartService
 * to this service while keeping existing behavior unchanged.
 */
class CartPricingService implements CartPricingServiceInterface
{
    public function __construct(
        private readonly PriceEngineService $priceEngine
    ) {
    }

    /**
     * Thin wrapper to keep all price-engine calls in one place.
     *
     * This does NOT change any behavior; it only delegates to PriceEngineService.
     */
    public function calculateItemPriceWithQuantity(int $productId, int $variantId, int $quantity): array
    {
        return $this->priceEngine->calculatePriceWithQuantity($productId, $variantId, $quantity);
    }

    public function recalculateCart(Cart $cart): Cart
    {
        // NOTE: For now we keep CartService as source of truth.
        // This method will be filled gradually in next steps of Phase 2.
        // Returning cart unchanged to avoid behavior change.
        return $cart;
    }
}


