<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Page\PageResource;
use App\Modules\Page\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Page::query();
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            $query->orderBy('name', 'asc');
            
            $pages = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => PageResource::collection($pages->items()),
                'pagination' => [
                    'current_page' => $pages->currentPage(),
                    'per_page' => $pages->perPage(),
                    'total' => $pages->total(),
                    'last_page' => $pages->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get pages list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get pages list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $page = Page::with('user')->find($id);
            if (!$page) return response()->json(['success' => false, 'message' => 'Page not found'], 404);
            return response()->json(['success' => true, 'data' => new PageResource($page)], 200);
        } catch (\Exception $e) {
            Log::error('Get page details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get page details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:posts,slug',
                'image' => 'nullable|string',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'status' => 'required|in:0,1',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string|max:500',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $slug = $request->slug;
            if (empty($slug)) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;
                while (Page::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
            
            $page = Page::create([
                'name' => $request->name,
                'slug' => $slug,
                'image' => $request->image,
                'description' => $request->description,
                'content' => $request->content,
                'status' => $request->status,
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'type' => 'page',
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Page created successfully',
                'data' => new PageResource($page->load('user')),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create page failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create page'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $page = Page::find($id);
            if (!$page) return response()->json(['success' => false, 'message' => 'Page not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:posts,slug,' . $id,
                'image' => 'nullable|string',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'status' => 'sometimes|in:0,1',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string|max:500',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('slug')) $updateData['slug'] = $request->slug;
            if ($request->has('image')) $updateData['image'] = $request->image;
            if ($request->has('description')) $updateData['description'] = $request->description;
            if ($request->has('content')) $updateData['content'] = $request->content;
            if ($request->has('status')) $updateData['status'] = $request->status;
            if ($request->has('seo_title')) $updateData['seo_title'] = $request->seo_title;
            if ($request->has('seo_description')) $updateData['seo_description'] = $request->seo_description;
            $updateData['user_id'] = Auth::id();
            
            $page->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Page updated successfully',
                'data' => new PageResource($page->fresh()->load('user')),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update page failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update page'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $page = Page::find($id);
            if (!$page) return response()->json(['success' => false, 'message' => 'Page not found'], 404);
            $page->delete();
            return response()->json(['success' => true, 'message' => 'Page deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete page failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete page'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $page = Page::find($id);
            if (!$page) return response()->json(['success' => false, 'message' => 'Page not found'], 404);
            $page->update(['status' => $request->status, 'user_id' => Auth::id()]);
            return response()->json(['success' => true, 'message' => 'Page status updated successfully', 'data' => new PageResource($page->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update page status failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update page status'], 500);
        }
    }

    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:posts,id',
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
                    $affected = Page::whereIn('id', $ids)->update(['status' => '0', 'user_id' => Auth::id()]);
                } elseif ($action == 1) {
                    $affected = Page::whereIn('id', $ids)->update(['status' => '1', 'user_id' => Auth::id()]);
                } else {
                    $affected = Page::whereIn('id', $ids)->delete();
                }
                DB::commit();
                $actionNames = ['hidden', 'shown', 'deleted'];
                return response()->json(['success' => true, 'message' => "Successfully {$actionNames[$action]} {$affected} page(s)", 'affected_count' => $affected], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Bulk action failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Bulk action failed'], 500);
        }
    }
}

