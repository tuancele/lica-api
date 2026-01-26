<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rate\RateResource;
use App\Modules\Rate\Models\Rate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('product_id') && $request->product_id !== '') $filters['product_id'] = $request->product_id;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Rate::with('product');
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['product_id'])) $query->where('product_id', $filters['product_id']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            $query->orderBy('created_at', 'desc');
            
            $rates = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => RateResource::collection($rates->items()),
                'pagination' => [
                    'current_page' => $rates->currentPage(),
                    'per_page' => $rates->perPage(),
                    'total' => $rates->total(),
                    'last_page' => $rates->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get rates list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get rates list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $rate = Rate::with('product')->find($id);
            if (!$rate) return response()->json(['success' => false, 'message' => 'Rate not found'], 404);
            return response()->json(['success' => true, 'data' => new RateResource($rate)], 200);
        } catch (\Exception $e) {
            Log::error('Get rate details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get rate details'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $rate = Rate::find($id);
            if (!$rate) return response()->json(['success' => false, 'message' => 'Rate not found'], 404);
            $rate->delete();
            return response()->json(['success' => true, 'message' => 'Rate deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete rate failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete rate'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $rate = Rate::find($id);
            if (!$rate) return response()->json(['success' => false, 'message' => 'Rate not found'], 404);
            $rate->update(['status' => $request->status]);
            return response()->json(['success' => true, 'message' => 'Rate status updated successfully', 'data' => new RateResource($rate->fresh()->load('product'))], 200);
        } catch (\Exception $e) {
            Log::error('Update rate status failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update rate status'], 500);
        }
    }
}

