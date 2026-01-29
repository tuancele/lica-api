<?php

declare(strict_types=1);

namespace App\Themes\website\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Brand\Models\Brand;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Warehouse\WarehouseServiceInterface;

class BrandController extends Controller
{
    public function index($url)
    {
        $detail = Brand::where([['slug', $url], ['status', '1']])->first();
        if (isset($detail) && ! empty($detail)) {
            $data['detail'] = $detail;
            $data['galleries'] = json_decode($detail->gallery ?? '[]', true) ?? [];
            $data['total'] = Product::select('id')->where([['type', 'product'], ['status', '1'], ['brand_id', $detail->id]])->get()->count();
            
            // Note: Removed ['stock', '1'] filter - stock is now managed by Warehouse V2
            $products = Product::where([['type', 'product'], ['status', '1'], ['brand_id', $detail->id]])->paginate(30);
            
            // Attach warehouse stock to products
            $warehouseService = app(WarehouseServiceInterface::class);
            $products->getCollection()->transform(function ($product) use ($warehouseService) {
                try {
                    $firstVariant = Variant::where('product_id', $product->id)
                        ->orderBy('position', 'asc')
                        ->orderBy('id', 'asc')
                        ->first();
                    if ($firstVariant) {
                        $stockInfo = $warehouseService->getVariantStock($firstVariant->id);
                        $product->warehouse_stock = (int) ($stockInfo['available_stock'] ?? 0);
                        $product->stock_display = (int) ($stockInfo['available_stock'] ?? 0);
                    } else {
                        $product->warehouse_stock = 0;
                        $product->stock_display = 0;
                    }
                } catch (\Throwable $e) {
                    $product->warehouse_stock = 0;
                    $product->stock_display = 0;
                }
                return $product;
            });
            
            $data['products'] = $products;
            // Note: Removed $data['stocks'] - out of stock products are now determined by warehouse_stock <= 0
            // Frontend will use isProductOutOfStock() helper to check stock status

            return view('Website::brand.index', $data);
        } else {
            return view('Website::404');
        }
    }
}
