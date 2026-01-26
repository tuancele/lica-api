<?php

declare(strict_types=1);
if (! function_exists('countProductWarehouse')) {
    function countProductWarehouse($id,$type){
        $total = 0;
        $list = App\Modules\Product\Models\Variant::select('id')->where('product_id',$id)->get();
        if(isset($list) && !empty($list)){
            foreach ($list as  $value) {
                $product = App\Modules\Warehouse\Models\ProductWarehouse::select('qty')->where([['variant_id',$value->id],['type',$type]])->get();
                $total = $total + array_sum(array_column($product->toArray(), 'qty'));
            }
        }
        return $total;
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