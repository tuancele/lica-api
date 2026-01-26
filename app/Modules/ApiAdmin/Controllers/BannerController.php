<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Banner\BannerResource;
use App\Modules\Banner\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
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
            
            $query = Banner::where('type', 'banner');
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['cat_id'])) $query->where('cat_id', $filters['cat_id']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            $query->orderBy('name', 'asc');
            
            $banners = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => BannerResource::collection($banners->items()),
                'pagination' => [
                    'current_page' => $banners->currentPage(),
                    'per_page' => $banners->perPage(),
                    'total' => $banners->total(),
                    'last_page' => $banners->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get banners list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get banners list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $banner = Banner::where('type', 'banner')->with('user')->find($id);
            if (!$banner) return response()->json(['success' => false, 'message' => 'Banner not found'], 404);
            return response()->json(['success' => true, 'data' => new BannerResource($banner)], 200);
        } catch (\Exception $e) {
            Log::error('Get banner details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get banner details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'image' => 'nullable|string',
                'link' => 'nullable|string',
                'cat_id' => 'nullable|integer',
                'status' => 'required|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $banner = Banner::create([
                'name' => $request->name,
                'image' => $request->image,
                'link' => $request->link,
                'cat_id' => $request->cat_id,
                'type' => 'banner',
                'status' => $request->status,
                'sort' => $request->sort ?? 0,
                'user_id' => Auth::id(),
            ]);
            
            Cache::forget('home_banners_v1');
            
            return response()->json([
                'success' => true,
                'message' => 'Banner created successfully',
                'data' => new BannerResource($banner->load('user')),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create banner failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create banner'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $banner = Banner::where('type', 'banner')->find($id);
            if (!$banner) return response()->json(['success' => false, 'message' => 'Banner not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'image' => 'nullable|string',
                'link' => 'nullable|string',
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
            if ($request->has('link')) $updateData['link'] = $request->link;
            if ($request->has('cat_id')) $updateData['cat_id'] = $request->cat_id;
            if ($request->has('status')) $updateData['status'] = $request->status;
            if ($request->has('sort')) $updateData['sort'] = $request->sort;
            $updateData['user_id'] = Auth::id();
            
            $banner->update($updateData);
            Cache::forget('home_banners_v1');
            
            return response()->json([
                'success' => true,
                'message' => 'Banner updated successfully',
                'data' => new BannerResource($banner->fresh()->load('user')),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update banner failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update banner'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $banner = Banner::where('type', 'banner')->find($id);
            if (!$banner) return response()->json(['success' => false, 'message' => 'Banner not found'], 404);
            $banner->delete();
            Cache::forget('home_banners_v1');
            return response()->json(['success' => true, 'message' => 'Banner deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete banner failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete banner'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $banner = Banner::where('type', 'banner')->find($id);
            if (!$banner) return response()->json(['success' => false, 'message' => 'Banner not found'], 404);
            $banner->update(['status' => $request->status, 'user_id' => Auth::id()]);
            Cache::forget('home_banners_v1');
            return response()->json(['success' => true, 'message' => 'Banner status updated successfully', 'data' => new BannerResource($banner->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update banner status failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update banner status'], 500);
        }
    }

    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:medias,id',
                'action' => 'required|in:0,1,2',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            DB::beginTransaction();
            try {
                $ids = $request->ids;
                $action = $request->action;
                if ($action == 0) {
                    $affected = Banner::where('type', 'banner')->whereIn('id', $ids)->update(['status' => '0', 'user_id' => Auth::id()]);
                } elseif ($action == 1) {
                    $affected = Banner::where('type', 'banner')->whereIn('id', $ids)->update(['status' => '1', 'user_id' => Auth::id()]);
                } else {
                    $affected = Banner::where('type', 'banner')->whereIn('id', $ids)->delete();
                }
                DB::commit();
                Cache::forget('home_banners_v1');
                $actionNames = ['hidden', 'shown', 'deleted'];
                return response()->json(['success' => true, 'message' => "Successfully {$actionNames[$action]} {$affected} banner(s)", 'affected_count' => $affected], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Bulk action failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Bulk action failed'], 500);
        }
    }

    public function updateSort(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sort' => 'required|array',
                'sort.*.id' => 'required|integer|exists:medias,id',
                'sort.*.sort' => 'required|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            DB::beginTransaction();
            try {
                foreach ($request->sort as $item) {
                    Banner::where('id', $item['id'])->where('type', 'banner')->update(['sort' => $item['sort'], 'user_id' => Auth::id()]);
                }
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Banner sort order updated successfully'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Update banner sort failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update banner sort order'], 500);
        }
    }
}

