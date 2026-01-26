<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Marketing\MarketingCampaignResource;
use App\Http\Resources\Marketing\MarketingCampaignProductResource;
use App\Modules\Marketing\Models\MarketingCampaign;
use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Modules\Product\Models\Product;
use App\Services\Promotion\ProductStockValidatorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MarketingCampaignController extends Controller
{
    protected ProductStockValidatorInterface $productStockValidator;

    public function __construct(ProductStockValidatorInterface $productStockValidator)
    {
        $this->productStockValidator = $productStockValidator;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = MarketingCampaign::withCount('products');
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            $query->latest();
            
            $campaigns = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => MarketingCampaignResource::collection($campaigns->items()),
                'pagination' => [
                    'current_page' => $campaigns->currentPage(),
                    'per_page' => $campaigns->perPage(),
                    'total' => $campaigns->total(),
                    'last_page' => $campaigns->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get marketing campaigns list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get campaigns list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $campaign = MarketingCampaign::with(['user', 'products.product'])->find($id);
            if (!$campaign) return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
            return response()->json(['success' => true, 'data' => new MarketingCampaignResource($campaign)], 200);
        } catch (\Exception $e) {
            Log::error('Get campaign details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get campaign details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'start_at' => 'required|date',
                'end_at' => 'required|date|after:start_at',
                'status' => 'required|in:0,1',
                'products' => 'nullable|array',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.price' => 'required|numeric|min:0',
                'products.*.limit' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $start = Carbon::parse($request->start_at);
            $end = Carbon::parse($request->end_at);
            
            DB::beginTransaction();
            try {
                $campaign = MarketingCampaign::create([
                    'name' => $request->name,
                    'start_at' => $request->start_at,
                    'end_at' => $request->end_at,
                    'status' => $request->status,
                    'user_id' => Auth::id(),
                ]);
                
                if ($request->has('products') && is_array($request->products)) {
                    foreach ($request->products as $productData) {
                        $productId = $productData['product_id'];
                        
                        if ($this->checkProductOverlap($productId, $start, $end, $campaign->id)) {
                            continue;
                        }
                        
                        $stock = $this->productStockValidator->getProductStock($productId);
                        if ($stock <= 0) {
                            Log::warning("Product has no stock, skipped from MarketingCampaign", [
                                'product_id' => $productId,
                                'campaign_id' => $campaign->id,
                            ]);
                            continue;
                        }
                        
                        MarketingCampaignProduct::create([
                            'campaign_id' => $campaign->id,
                            'product_id' => $productId,
                            'price' => str_replace(',', '', $productData['price']),
                            'limit' => $productData['limit'] ?? 0,
                        ]);
                    }
                }
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Campaign created successfully',
                    'data' => new MarketingCampaignResource($campaign->load(['user', 'products.product'])),
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Create campaign failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create campaign'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $campaign = MarketingCampaign::find($id);
            if (!$campaign) return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'start_at' => 'sometimes|required|date',
                'end_at' => 'sometimes|required|date|after:start_at',
                'status' => 'sometimes|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('start_at')) $updateData['start_at'] = $request->start_at;
            if ($request->has('end_at')) $updateData['end_at'] = $request->end_at;
            if ($request->has('status')) $updateData['status'] = $request->status;
            $updateData['user_id'] = Auth::id();
            
            $campaign->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data' => new MarketingCampaignResource($campaign->fresh()->load(['user', 'products.product'])),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update campaign failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update campaign'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $campaign = MarketingCampaign::find($id);
            if (!$campaign) return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
            
            DB::beginTransaction();
            try {
                MarketingCampaignProduct::where('campaign_id', $id)->delete();
                $campaign->delete();
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Campaign deleted successfully'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Delete campaign failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete campaign'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $campaign = MarketingCampaign::find($id);
            if (!$campaign) return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
            $campaign->update(['status' => $request->status, 'user_id' => Auth::id()]);
            return response()->json(['success' => true, 'message' => 'Campaign status updated successfully', 'data' => new MarketingCampaignResource($campaign->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update campaign status failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update campaign status'], 500);
        }
    }

    public function addProducts(Request $request, int $id): JsonResponse
    {
        try {
            $campaign = MarketingCampaign::find($id);
            if (!$campaign) return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.price' => 'required|numeric|min:0',
                'products.*.limit' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $start = Carbon::parse($campaign->start_at);
            $end = Carbon::parse($campaign->end_at);
            
            DB::beginTransaction();
            try {
                foreach ($request->products as $productData) {
                    $productId = $productData['product_id'];
                    
                    if ($this->checkProductOverlap($productId, $start, $end, $campaign->id)) {
                        continue;
                    }
                    
                    $stock = $this->productStockValidator->getProductStock($productId);
                    if ($stock <= 0) {
                        continue;
                    }
                    
                    MarketingCampaignProduct::create([
                        'campaign_id' => $id,
                        'product_id' => $productId,
                        'price' => str_replace(',', '', $productData['price']),
                        'limit' => $productData['limit'] ?? 0,
                    ]);
                }
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Products added successfully',
                    'data' => new MarketingCampaignResource($campaign->fresh()->load(['user', 'products.product'])),
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Add products to campaign failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add products'], 500);
        }
    }

    public function removeProduct(int $id, int $productId): JsonResponse
    {
        try {
            $campaign = MarketingCampaign::find($id);
            if (!$campaign) return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
            
            MarketingCampaignProduct::where('campaign_id', $id)
                ->where('product_id', $productId)
                ->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Product removed successfully',
                'data' => new MarketingCampaignResource($campaign->fresh()->load(['user', 'products.product'])),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Remove product from campaign failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove product'], 500);
        }
    }

    public function searchProducts(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'keyword' => 'required|string|min:1',
                'limit' => 'nullable|integer|min:1|max:50',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $limit = $request->get('limit', 20);
            $products = Product::where('name', 'like', '%' . $request->keyword . '%')
                ->where('status', '1')
                ->select('id', 'name', 'slug', 'image')
                ->limit($limit)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'image' => getImage($product->image),
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $products,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Search products failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to search products'], 500);
        }
    }

    private function checkProductOverlap($productId, $start, $end, $excludeCampaignId = null): bool
    {
        $query = MarketingCampaignProduct::where('product_id', $productId)
            ->whereHas('campaign', function ($q) use ($start, $end) {
                $q->where('status', '1')
                    ->where(function ($q2) use ($start, $end) {
                        $q2->whereBetween('start_at', [$start, $end])
                            ->orWhereBetween('end_at', [$start, $end])
                            ->orWhere(function ($q3) use ($start, $end) {
                                $q3->where('start_at', '<=', $start)
                                    ->where('end_at', '>=', $end);
                            });
                    });
            });
        
        if ($excludeCampaignId) {
            $query->where('campaign_id', '!=', $excludeCampaignId);
        }
        
        return $query->exists();
    }
}

