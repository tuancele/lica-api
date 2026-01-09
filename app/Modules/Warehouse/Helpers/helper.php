<?php
if (! function_exists('countProduct')) {
    function countProduct($variant,$type){
        $total = 0;
        $product = App\Modules\Warehouse\Models\ProductWarehouse::select('qty')->where([['variant_id',$variant],['type',$type]])->get();
        $total = array_sum(array_column($product->toArray(), 'qty'));
        return $total;
    }
}
if (! function_exists('countPrice')) {
    function countPrice($variant,$type){
        $total = 0;
        $product = App\Modules\Warehouse\Models\ProductWarehouse::select('price')->where([['variant_id',$variant],['type',$type]])->get();
        $total = array_sum(array_column($product->toArray(), 'price'));
        return $total;
    }
}