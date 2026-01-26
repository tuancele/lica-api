<?php

declare(strict_types=1);
namespace App\Themes\Website\Models;

use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Modules\Marketing\Models\MarketingCampaign;
use App\Themes\Website\Models\Facebook;
use Carbon\Carbon;

class Cart
{
    public $items = [];
    public $totalQty = 0;
    public $totalPrice = 0;

    public function __construct($oldCart)
    {
        if ($oldCart) {
            // IMPORTANT: Deep copy items array to avoid reference issues
            // When Cart is unserialized from session, we need to ensure fresh copy
            if (is_object($oldCart)) {
                // Deep copy items array
                $this->items = [];
                if (isset($oldCart->items) && is_array($oldCart->items)) {
                    foreach ($oldCart->items as $key => $item) {
                        $this->items[$key] = $item; // PHP arrays are copied by value
                    }
                }
                $this->totalQty = $oldCart->totalQty ?? 0;
                $this->totalPrice = $oldCart->totalPrice ?? 0;
            } elseif (is_array($oldCart)) {
                // Handle case where oldCart is already an array (from session)
                $this->items = $oldCart['items'] ?? [];
                $this->totalQty = $oldCart['totalQty'] ?? 0;
                $this->totalPrice = $oldCart['totalPrice'] ?? 0;
            }
        }
    }
    
    /**
     * Custom serialization for session storage
     * Ensures Cart object is properly serialized
     */
    public function __sleep()
    {
        return ['items', 'totalQty', 'totalPrice'];
    }
    
    /**
     * Custom unserialization from session
     * Ensures items array is properly restored
     */
    public function __wakeup()
    {
        // Ensure items is always an array
        if (!is_array($this->items)) {
            $this->items = [];
        }
        // Recalculate totals if needed
        if (empty($this->items)) {
            $this->totalQty = 0;
            $this->totalPrice = 0;
        }
    }

    public function add($item, $id, $qty, $is_deal = 0)
    {
        $date = strtotime(date('Y-m-d H:i:s'));
        $nowDate = Carbon::now();

        // Base price (do not use legacy "sale" field)
        $unit_price = (float) ($item->price ?? 0);

        // 2. Check Marketing Campaign (Priority > Base/Sale)
        $campaignProduct = MarketingCampaignProduct::where('product_id', $item->product_id)
            ->whereHas('campaign', function ($q) use ($nowDate) {
                $q->where('status', 1)
                  ->where('start_at', '<=', $nowDate)
                  ->where('end_at', '>=', $nowDate);
            })->first();

        if ($campaignProduct) {
            $unit_price = $campaignProduct->price;
        }

        // 3. Check Flash Sale (Priority > Campaign)
        $flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();
        if (isset($flash) && !empty($flash)) {
            $product = ProductSale::select('product_id', 'price_sale', 'number', 'buy')
                ->where([['flashsale_id', $flash->id], ['product_id', $item->product_id]])
                ->first();
            
            if (isset($product) && !empty($product)) {
                if ($product->buy < $product->number) {
                    $unit_price = $product->price_sale;
                }
            }
        }
        
        // Deal items: capture dealsale_id/deal_id if available for downstream order detail
        $dealId = null;
        $dealSaleId = null;
        if ($is_deal == 1) {
            $now = strtotime(date('Y-m-d H:i:s'));
            $saleDealRow = \App\Modules\Deal\Models\SaleDeal::where('product_id', $item->product_id)
                ->whereHas('deal', function ($q) use ($now) {
                    $q->where('status', '1')
                        ->where('start', '<=', $now)
                        ->where('end', '>=', $now);
                })
                ->where('status', '1')
                ->first();
            if ($saleDealRow) {
                $dealId = $saleDealRow->deal_id;
                $dealSaleId = $saleDealRow->id;
                $item->price = $saleDealRow->price; // align price with deal
            }
        }

        $cart = [
            'qty' => 0,
            'price' => $unit_price,
            'item' => $item,
            'is_deal' => $is_deal,
            'deal_id' => $dealId,
            'dealsale_id' => $dealSaleId,
        ];
        
        if ($this->items) {
            if (array_key_exists($id, $this->items)) {
                $cart = $this->items[$id];
                // Update unit price in case it changed (e.g. Flash sale started since last add)
                $cart['price'] = $unit_price; 
            }
        }

        $cart['qty'] = $cart['qty'] + $qty;
        // Ensure price is set
        $cart['price'] = $unit_price;

        // Tracking
        $dataf = array(
            'product_id' => $item->product_id,
            'price' => $unit_price,
            'url' => getSlug($item->slug),
            'event' => 'AddToCart',
        );
        Facebook::track($dataf);

        $this->items[$id] = $cart;
        $this->totalQty += $qty;
        
        // Recalculate Total Price
        $this->totalPrice = 0;
        foreach($this->items as $i) {
            $this->totalPrice += ($i['price'] * $i['qty']);
        }
    }

    public function update($id, $qty)
    {
        if (!isset($this->items[$id])) {
            return;
        }

        if ($qty <= 0) {
            $this->removeItem($id);
            return;
        }

        $this->items[$id]['qty'] = $qty;

        // Recalculate Total
        $this->totalQty = 0;
        $this->totalPrice = 0;
        foreach($this->items as $i) {
            $this->totalQty += $i['qty'];
            $this->totalPrice += ($i['price'] * $i['qty']);
        }
    }

    public function reduceByOne($id)
    {
        if (!isset($this->items[$id])) {
            return;
        }

        $this->items[$id]['qty']--;
        
        if ($this->items[$id]['qty'] <= 0) {
            unset($this->items[$id]);
        }

        // Recalculate Total
        $this->totalQty = 0;
        $this->totalPrice = 0;
        foreach($this->items as $i) {
            $this->totalQty += $i['qty'];
            $this->totalPrice += ($i['price'] * $i['qty']);
        }
    }

    public function removeItem($id)
    {
        if (!isset($this->items[$id])) {
            return;
        }
        
        // IMPORTANT: Create a new array without the removed item
        // This ensures we don't modify the original array reference
        // which could cause all items to be removed
        $newItems = [];
        foreach ($this->items as $key => $item) {
            if ($key != $id) {
                $newItems[$key] = $item;
            }
        }
        $this->items = $newItems;

        // Recalculate Total
        $this->totalQty = 0;
        $this->totalPrice = 0;
        foreach($this->items as $i) {
            $this->totalQty += $i['qty'];
            $this->totalPrice += ($i['price'] * $i['qty']);
        }
    }
}
