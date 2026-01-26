<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Selling\SellingResource;
use App\Modules\Selling\Models\Selling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SellingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Selling::where('type', 'selling')->with('product');
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            
            $sellings = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => SellingResource::collection($sellings->items()),
                'pagination' => [
                    'current_page' => $sellings->currentPage(),
                    'per_page' => $sellings->perPage(),
                    'total' => $sellings->total(),
                    'last_page' => $sellings->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get sellings list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get sellings list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $selling = Selling::where('type', 'selling')->with('product')->find($id);
            if (!$selling) return response()->json(['success' => false, 'message' => 'Selling record not found'], 404);
            return response()->json(['success' => true, 'data' => new SellingResource($selling)], 200);
        } catch (\Exception $e) {
            Log::error('Get selling details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get selling details'], 500);
        }
    }
}

