<?php

namespace App\Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
class ProductWarehouse extends Model
{
    protected $table = "product_warehouse";

    protected $fillable = [
        'warehouse_id',
        'variant_id',
        'price',
        'qty',
        'type',
        'physical_stock',
        'flash_sale_stock',
        'deal_stock',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'available_stock',
    ];

    protected $casts = [
        'warehouse_id' => 'int',
        'variant_id' => 'int',
        'qty' => 'int',
        'physical_stock' => 'int',
        'flash_sale_stock' => 'int',
        'deal_stock' => 'int',
    ];

    /**
     * Recalculate and persist available stock based on physical - flash - deal.
     */
    public function syncAvailableStock(): void
    {
        $physical = $this->physical_stock ?? 0;
        $flash = $this->flash_sale_stock ?? 0;
        $deal = $this->deal_stock ?? 0;
        $available = max(0, (int) $physical - (int) $flash - (int) $deal);
        $this->qty = $available;
        
        // Cập nhật stock field ở variants table để đồng bộ legacy data
        if ($this->variant_id) {
            \DB::table('variants')->where('id', $this->variant_id)->update(['stock' => (int) $physical]);
        }
    }

    /**
     * Computed available stock = physical - flash sale hold - deal hold.
     */
    /**
     * Computed available stock = physical - flash sale hold - deal hold.
     * Logic chuẩn hóa: Lượng còn lại thực sự có thể bán được.
     */
    public function getAvailableStockAttribute(): int
    {
        $physical = (int) ($this->physical_stock ?? 0);
        $flash = (int) ($this->flash_sale_stock ?? 0);
        $deal = (int) ($this->deal_stock ?? 0);
        
        $available = $physical - $flash - $deal;
        return $available > 0 ? $available : 0;
    }

    public function variant(){
        return $this->belongsTo('App\Modules\Product\Models\Variant','variant_id','id');
    }
    public function warehouse(){
        return $this->belongsTo('App\Modules\Warehouse\Models\Warehouse','warehouse_id','id');
    }
}
