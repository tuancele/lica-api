<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Compare\CompareResource;
use App\Modules\Compare\Models\Compare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CompareController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('store_id') && $request->store_id !== '') $filters['store_id'] = $request->store_id;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 20);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;
            
            $query = Compare::with('store');
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['store_id'])) $query->where('store_id', $filters['store_id']);
            if (isset($filters['keyword'])) {
                $keyword = $filters['keyword'];
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                      ->orWhere('brand', 'like', '%' . $keyword . '%');
                });
            }
            $query->orderBy('created_at', 'desc');
            
            $compares = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => CompareResource::collection($compares->items()),
                'pagination' => [
                    'current_page' => $compares->currentPage(),
                    'per_page' => $compares->perPage(),
                    'total' => $compares->total(),
                    'last_page' => $compares->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get compares list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get compares list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $compare = Compare::with('store')->find($id);
            if (!$compare) return response()->json(['success' => false, 'message' => 'Compare record not found'], 404);
            return response()->json(['success' => true, 'data' => new CompareResource($compare)], 200);
        } catch (\Exception $e) {
            Log::error('Get compare details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get compare details'], 500);
        }
    }
}

