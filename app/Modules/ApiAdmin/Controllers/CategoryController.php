<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Category\CategoryResource;
use App\Modules\Category\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Category API Controller for Admin
 * 
 * Handles all category management API endpoints following RESTful standards
 * Base URL: /admin/api/categories
 */
class CategoryController extends Controller
{
    /**
     * Get paginated list of categories with tree structure support
     * 
     * GET /admin/api/categories
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Prepare filters from query parameters
            $filters = [];
            
            if ($request->has('status') && $request->status !== '') {
                $filters['status'] = $request->status;
            }
            
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }
            
            // Get tree structure flag
            $tree = $request->boolean('tree', false);
            
            // Build query
            $query = Category::where('type', 'category');
            
            // Apply filters
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            if (isset($filters['keyword'])) {
                $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            }
            
            // Order by sort ASC
            $query->orderBy('sort', 'asc')->orderBy('id', 'asc');
            
            if ($tree) {
                // Return tree structure
                $categories = $query->get();
                $treeData = $this->buildTree($categories);
                
                return response()->json([
                    'success' => true,
                    'data' => CategoryResource::collection($treeData),
                ], 200);
            } else {
                // Return flat list with pagination
                $perPage = (int) $request->get('limit', 10);
                $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
                
                $categories = $query->paginate($perPage);
                
                return response()->json([
                    'success' => true,
                    'data' => CategoryResource::collection($categories->items()),
                    'pagination' => [
                        'current_page' => $categories->currentPage(),
                        'per_page' => $categories->perPage(),
                        'total' => $categories->total(),
                        'last_page' => $categories->lastPage(),
                    ],
                ], 200);
            }
            
        } catch (\Exception $e) {
            Log::error('Get categories list failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get categories list',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get single category details
     * 
     * GET /admin/api/categories/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = Category::where('type', 'category')
                ->with(['user', 'children'])
                ->find($id);
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get category details failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'category_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get category details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create a new category
     * 
     * POST /admin/api/categories
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:posts,slug',
                'image' => 'nullable|string',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'status' => 'required|in:0,1',
                'feature' => 'nullable|in:0,1',
                'cat_id' => 'nullable|integer|exists:posts,id',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string|max:500',
                'sort' => 'nullable|integer|min:0',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Generate slug if not provided
            $slug = $request->slug;
            if (empty($slug)) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;
                while (Category::where('slug', $slug)->where('type', 'category')->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
            
            // Create category
            $category = Category::create([
                'name' => $request->name,
                'slug' => $slug,
                'image' => $request->image,
                'description' => $request->description,
                'content' => $request->content,
                'status' => $request->status,
                'feature' => $request->feature ?? '0',
                'type' => 'category',
                'cat_id' => $request->cat_id ?? 0,
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'sort' => $request->sort ?? 0,
                'user_id' => Auth::id(),
            ]);
            
            // Load relations
            $category->load(['user', 'children']);
            
            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => new CategoryResource($category),
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Create category failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update an existing category
     * 
     * PUT /admin/api/categories/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $category = Category::where('type', 'category')->find($id);
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }
            
            // Validation
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:posts,slug,' . $id,
                'image' => 'nullable|string',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'status' => 'sometimes|in:0,1',
                'feature' => 'nullable|in:0,1',
                'cat_id' => 'nullable|integer|exists:posts,id',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string|max:500',
                'sort' => 'nullable|integer|min:0',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Prevent circular reference (category cannot be its own parent)
            if ($request->has('cat_id') && $request->cat_id == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category cannot be its own parent'
                ], 422);
            }
            
            // Prepare update data
            $updateData = [];
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('slug')) {
                $updateData['slug'] = $request->slug;
            }
            if ($request->has('image')) {
                $updateData['image'] = $request->image;
            }
            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }
            if ($request->has('content')) {
                $updateData['content'] = $request->content;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('feature')) {
                $updateData['feature'] = $request->feature;
            }
            if ($request->has('cat_id')) {
                $updateData['cat_id'] = $request->cat_id;
            }
            if ($request->has('seo_title')) {
                $updateData['seo_title'] = $request->seo_title;
            }
            if ($request->has('seo_description')) {
                $updateData['seo_description'] = $request->seo_description;
            }
            if ($request->has('sort')) {
                $updateData['sort'] = $request->sort;
            }
            $updateData['user_id'] = Auth::id();
            
            // Update category
            $category->update($updateData);
            
            // Load relations
            $category->load(['user', 'children']);
            
            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => new CategoryResource($category),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Update category failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'category_id' => $id,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a category
     * 
     * DELETE /admin/api/categories/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $category = Category::where('type', 'category')->find($id);
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }
            
            // Check if category has children
            $childrenCount = Category::where('type', 'category')
                ->where('cat_id', $id)
                ->count();
            
            if ($childrenCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete category. It has {$childrenCount} child category(ies)."
                ], 422);
            }
            
            $category->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Delete category failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'category_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update category status
     * 
     * PATCH /admin/api/categories/{id}/status
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:0,1',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $category = Category::where('type', 'category')->find($id);
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }
            
            $category->update([
                'status' => $request->status,
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Category status updated successfully',
                'data' => new CategoryResource($category->fresh()),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Update category status failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'category_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk actions (hide/show/delete)
     * 
     * POST /admin/api/categories/bulk-action
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:posts,id',
                'action' => 'required|in:0,1,2', // 0=hide, 1=show, 2=delete
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $ids = $request->ids;
            $action = $request->action;
            $affected = 0;
            
            DB::beginTransaction();
            
            try {
                if ($action == 0) {
                    // Hide
                    $affected = Category::where('type', 'category')
                        ->whereIn('id', $ids)
                        ->update([
                            'status' => '0',
                            'user_id' => Auth::id(),
                        ]);
                } elseif ($action == 1) {
                    // Show
                    $affected = Category::where('type', 'category')
                        ->whereIn('id', $ids)
                        ->update([
                            'status' => '1',
                            'user_id' => Auth::id(),
                        ]);
                } else {
                    // Delete - check for children first
                    $categoriesWithChildren = Category::where('type', 'category')
                        ->whereIn('id', $ids)
                        ->whereHas('children')
                        ->pluck('id')
                        ->toArray();
                    
                    if (!empty($categoriesWithChildren)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot delete categories with child categories',
                            'category_ids' => $categoriesWithChildren
                        ], 422);
                    }
                    
                    $affected = Category::where('type', 'category')
                        ->whereIn('id', $ids)
                        ->delete();
                }
                
                DB::commit();
                
                $actionNames = ['hidden', 'shown', 'deleted'];
                
                return response()->json([
                    'success' => true,
                    'message' => "Successfully {$actionNames[$action]} {$affected} category(ies)",
                    'affected_count' => $affected,
                ], 200);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Bulk action failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update category sort order
     * 
     * PATCH /admin/api/categories/sort
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSort(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sort' => 'required|array',
                'sort.*.id' => 'required|integer|exists:posts,id',
                'sort.*.sort' => 'required|integer|min:0',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            try {
                foreach ($request->sort as $item) {
                    Category::where('id', $item['id'])
                        ->where('type', 'category')
                        ->update([
                            'sort' => $item['sort'],
                            'user_id' => Auth::id(),
                        ]);
                }
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Category sort order updated successfully',
                ], 200);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Update category sort failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category sort order',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update category tree structure
     * 
     * POST /admin/api/categories/tree
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateTree(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sortable' => 'required|array',
                'sortable.*.item_id' => 'required|integer|exists:posts,id',
                'sortable.*.parent_id' => 'required|integer',
                'sortable.*.sort' => 'nullable|integer|min:0',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            try {
                foreach ($request->sortable as $order => $value) {
                    $id = (int) $value['item_id'];
                    $parentId = (int) $value['parent_id'];
                    $sort = isset($value['sort']) ? (int) $value['sort'] : $order;
                    
                    // Prevent circular reference
                    if ($id == $parentId) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Category cannot be its own parent',
                            'category_id' => $id
                        ], 422);
                    }
                    
                    Category::where('id', $id)
                        ->where('type', 'category')
                        ->update([
                            'cat_id' => $parentId,
                            'sort' => $sort,
                            'user_id' => Auth::id(),
                        ]);
                }
                
                DB::commit();
                
                // Return updated tree structure
                $categories = Category::where('type', 'category')
                    ->where('status', '1')
                    ->orderBy('sort', 'asc')
                    ->get();
                $treeData = $this->buildTree($categories);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Category tree structure updated successfully',
                    'data' => CategoryResource::collection($treeData),
                ], 200);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Update category tree failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category tree structure',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Build tree structure from flat category list
     * 
     * @param \Illuminate\Support\Collection $categories
     * @param int $parentId
     * @return \Illuminate\Support\Collection
     */
    private function buildTree($categories, $parentId = 0)
    {
        return $categories->filter(function ($category) use ($parentId) {
            return ($category->cat_id ?? 0) == $parentId;
        })->map(function ($category) use ($categories) {
            $category->setRelation('children', $this->buildTree($categories, $category->id));
            return $category;
        })->values();
    }
}

