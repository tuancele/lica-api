<?php

declare(strict_types=1);
if (! function_exists('countProductWarehouse')) {
    function countProductWarehouse($id, $type)
    {
        // Delegate legacy ProductWarehouse aggregation to WarehouseService
        /** @var \App\Services\Warehouse\WarehouseServiceInterface $warehouseService */
        $warehouseService = app(\App\Services\Warehouse\WarehouseServiceInterface::class);

        return $warehouseService->getLegacyProductWarehouseQuantity((int) $id, (string) $type);
    }
}
// if (! function_exists('countPrice')) {
//     function countPrice($variant,$type){
//         $total = 0;
//         $product = App\Modules\Warehouse\Models\ProductWarehouse::select('price')->where([['variant_id',$variant],['type',$type]])->get();
//         $total = array_sum(array_column($product->toArray(), 'price'));
//         return $total;
//     }
// }
