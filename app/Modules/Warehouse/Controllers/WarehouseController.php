<?php

declare(strict_types=1);
namespace App\Modules\Warehouse\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Modules\Order\Models\OrderDetail;
use App\Modules\Warehouse\Models\Warehouse;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
	public function index(Request $request)
    {
        $user = Auth::user();
        if ($user && empty($user->api_token)) {
            $user->api_token = bin2hex(random_bytes(20));
            $user->save();
        }
        $apiToken = $user?->api_token ?? '';

        active('warehouse','warehouse');
        $data['list'] = Product::select('id','name', 'slug','image')->where('type','product')->where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
	            $query->where('name','like','%'.$request->get('keyword').'%');
	        }
        })->orderBy('created_at','desc')->paginate(10);
        $data['apiToken'] = $apiToken;
        return view('Warehouse::index',$data);
    }
    public function statistical(Request $request){
        return view('Warehouse::statistical',$data);
    }
    public function revenue(Request $request){
        $user = Auth::user();
        if ($user && empty($user->api_token)) {
            $user->api_token = bin2hex(random_bytes(20));
            $user->save();
        }
        $apiToken = $user?->api_token ?? '';

        active('statistical','revenue');
        $data['list'] = Variant::join('posts', 'posts.id', '=', 'variants.product_id')->select('variants.*','posts.name as name', 'posts.slug as slug')->where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
                $query->where('posts.name','like','%'.$request->get('keyword').'%')->orWhere('variants.sku','like','%'.$request->get('keyword').'%');;
            }
        })->orderBy('variants.created_at','desc')->paginate(10);
        $variantIds = $data['list']->pluck('id');
        $importTotals = DB::table('stock_receipt_items')
            ->join('stock_receipts', 'stock_receipt_items.receipt_id', '=', 'stock_receipts.id')
            ->where('stock_receipts.type', 'import')
            ->whereIn('stock_receipt_items.variant_id', $variantIds)
            ->selectRaw('stock_receipt_items.variant_id, SUM(stock_receipt_items.quantity * stock_receipt_items.unit_price) as total')
            ->groupBy('stock_receipt_items.variant_id')
            ->pluck('total', 'variant_id');
        $exportTotals = DB::table('stock_receipt_items')
            ->join('stock_receipts', 'stock_receipt_items.receipt_id', '=', 'stock_receipts.id')
            ->where('stock_receipts.type', 'export')
            ->whereIn('stock_receipt_items.variant_id', $variantIds)
            ->selectRaw('stock_receipt_items.variant_id, SUM(stock_receipt_items.quantity * stock_receipt_items.unit_price) as total')
            ->groupBy('stock_receipt_items.variant_id')
            ->pluck('total', 'variant_id');
        $data['importTotals'] = $importTotals;
        $data['exportTotals'] = $exportTotals;
        $data['apiToken'] = $apiToken;
        return view('Warehouse::revenue',$data);
    }
    public function quantity(Request $request){
        $user = Auth::user();
        if ($user && empty($user->api_token)) {
            $user->api_token = bin2hex(random_bytes(20));
            $user->save();
        }
        $apiToken = $user?->api_token ?? '';

        active('statistical','quantity');
        $data['list'] = Variant::join('posts', 'posts.id', '=', 'variants.product_id')->select('variants.*','posts.name as name', 'posts.slug as slug')->where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
                $query->where('posts.name','like','%'.$request->get('keyword').'%')->orWhere('variants.sku','like','%'.$request->get('keyword').'%');;
            }
        })->orderBy('variants.created_at','desc')->paginate(10);
        $data['apiToken'] = $apiToken;
        return view('Warehouse::quantity',$data);
    }
    
    /**
     * Get variant stock for web (session authentication)
     * Used in product edit page
     * 
     * @param int $variantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVariantStockWeb($variantId)
    {
        try {
            $stock = app(InventoryServiceInterface::class)->getStock((int) $variantId);
            $currentStock = (int) ($stock->availableStock ?? 0);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'variant_id' => (int) $variantId,
                    'current_stock' => $currentStock,
                    'import_total' => (int) ($stock->physicalStock ?? 0),
                    'export_total' => (int) ($stock->reservedStock ?? 0),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin tồn kho thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}