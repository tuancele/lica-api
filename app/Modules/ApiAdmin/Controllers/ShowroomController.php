<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Showroom\ShowroomResource;
use App\Modules\Showroom\Models\Showroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ShowroomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('cat_id') && $request->cat_id !== '') $filters['cat_id'] = $request->cat_id;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Showroom::query();
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['cat_id'])) $query->where('cat_id', $filters['cat_id']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            $query->orderBy('sort', 'asc');
            
            $showrooms = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => ShowroomResource::collection($showrooms->items()),
                'pagination' => [
                    'current_page' => $showrooms->currentPage(),
                    'per_page' => $showrooms->perPage(),
                    'total' => $showrooms->total(),
                    'last_page' => $showrooms->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get showrooms list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get showrooms list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $showroom = Showroom::find($id);
            if (!$showroom) return response()->json(['success' => false, 'message' => 'Showroom not found'], 404);
            return response()->json(['success' => true, 'data' => new ShowroomResource($showroom)], 200);
        } catch (\Exception $e) {
            Log::error('Get showroom details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get showroom details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'image' => 'nullable|string',
                'address' => 'nullable|string',
                'phone' => 'nullable|string',
                'cat_id' => 'nullable|integer',
                'status' => 'required|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $showroom = Showroom::create([
                'name' => $request->name,
                'image' => $request->image,
                'address' => $request->address,
                'phone' => $request->phone,
                'cat_id' => $request->cat_id,
                'status' => $request->status,
                'sort' => $request->sort ?? 0,
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Showroom created successfully',
                'data' => new ShowroomResource($showroom),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create showroom failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create showroom'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $showroom = Showroom::find($id);
            if (!$showroom) return response()->json(['success' => false, 'message' => 'Showroom not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'image' => 'nullable|string',
                'address' => 'nullable|string',
                'phone' => 'nullable|string',
                'cat_id' => 'nullable|integer',
                'status' => 'sometimes|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('image')) $updateData['image'] = $request->image;
            if ($request->has('address')) $updateData['address'] = $request->address;
            if ($request->has('phone')) $updateData['phone'] = $request->phone;
            if ($request->has('cat_id')) $updateData['cat_id'] = $request->cat_id;
            if ($request->has('status')) $updateData['status'] = $request->status;
            if ($request->has('sort')) $updateData['sort'] = $request->sort;
            
            $showroom->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Showroom updated successfully',
                'data' => new ShowroomResource($showroom->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update showroom failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update showroom'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $showroom = Showroom::find($id);
            if (!$showroom) return response()->json(['success' => false, 'message' => 'Showroom not found'], 404);
            $showroom->delete();
            return response()->json(['success' => true, 'message' => 'Showroom deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete showroom failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete showroom'], 500);
        }
    }
}

