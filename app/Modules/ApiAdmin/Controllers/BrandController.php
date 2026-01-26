<?php

declare(strict_types=1);

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Brand\BrandResource;
use App\Modules\Brand\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Brand API Controller for Admin.
 *
 * Handles all brand management API endpoints following RESTful standards
 * Base URL: /admin/api/brands
 */
class BrandController extends Controller
{
    /**
     * Get paginated list of brands with filters.
     *
     * GET /admin/api/brands
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

            // Get pagination parameters
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

            // Build query
            $query = Brand::withCount('product');

            // Apply filters
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['keyword'])) {
                $query->where('name', 'like', '%'.$filters['keyword'].'%');
            }

            // Order by name ASC (matching old controller behavior)
            $query->orderBy('name', 'asc');

            // Paginate results
            $brands = $query->paginate($perPage);

            // Format response using BrandResource
            $formattedBrands = BrandResource::collection($brands->items());

            return response()->json([
                'success' => true,
                'data' => $formattedBrands,
                'pagination' => [
                    'current_page' => $brands->currentPage(),
                    'per_page' => $brands->perPage(),
                    'total' => $brands->total(),
                    'last_page' => $brands->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get brands list failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get brands list',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get single brand details.
     *
     * GET /admin/api/brands/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $brand = Brand::with(['user', 'product'])
                ->find($id);

            if (! $brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Brand not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new BrandResource($brand),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get brand details failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'brand_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get brand details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Create a new brand.
     *
     * POST /admin/api/brands
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:brands,slug',
                'content' => 'nullable|string',
                'image' => 'nullable|string',
                'banner' => 'nullable|string',
                'logo' => 'nullable|string',
                'gallery' => 'nullable|array',
                'gallery.*' => 'string',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string|max:500',
                'status' => 'required|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Generate slug if not provided
            $slug = $request->slug;
            if (empty($slug)) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;
                while (Brand::where('slug', $slug)->exists()) {
                    $slug = $originalSlug.'-'.$counter;
                    $counter++;
                }
            }

            // Handle gallery
            $gallery = $this->processGallery($request);

            // Create brand
            $brand = Brand::create([
                'name' => $request->name,
                'slug' => $slug,
                'content' => $request->content,
                'image' => $request->image,
                'banner' => $request->banner,
                'logo' => $request->logo,
                'gallery' => json_encode($gallery),
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'status' => $request->status,
                'sort' => $request->sort ?? 0,
                'user_id' => Auth::id(),
            ]);

            // Load relations
            $brand->load(['user', 'product']);

            return response()->json([
                'success' => true,
                'message' => 'Brand created successfully',
                'data' => new BrandResource($brand),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create brand failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create brand',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update an existing brand.
     *
     * PUT /admin/api/brands/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $brand = Brand::find($id);

            if (! $brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Brand not found',
                ], 404);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:brands,slug,'.$id,
                'content' => 'nullable|string',
                'image' => 'nullable|string',
                'banner' => 'nullable|string',
                'logo' => 'nullable|string',
                'gallery' => 'nullable|array',
                'gallery.*' => 'string',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string|max:500',
                'status' => 'sometimes|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Handle gallery
            $gallery = null;
            if ($request->has('gallery') || $request->has('imageOther') || $request->has('r2_session_key')) {
                $gallery = $this->processGallery($request);
            }

            // Prepare update data
            $updateData = [];
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('slug')) {
                $updateData['slug'] = $request->slug;
            }
            if ($request->has('content')) {
                $updateData['content'] = $request->content;
            }
            if ($request->has('image')) {
                $updateData['image'] = $request->image;
            }
            if ($request->has('banner')) {
                $updateData['banner'] = $request->banner;
            }
            if ($request->has('logo')) {
                $updateData['logo'] = $request->logo;
            }
            if ($gallery !== null) {
                $updateData['gallery'] = json_encode($gallery);
            }
            if ($request->has('seo_title')) {
                $updateData['seo_title'] = $request->seo_title;
            }
            if ($request->has('seo_description')) {
                $updateData['seo_description'] = $request->seo_description;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('sort')) {
                $updateData['sort'] = $request->sort;
            }
            $updateData['user_id'] = Auth::id();

            // Update brand
            $brand->update($updateData);

            // Load relations
            $brand->load(['user', 'product']);

            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully',
                'data' => new BrandResource($brand),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update brand failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'brand_id' => $id,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update brand',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Delete a brand.
     *
     * DELETE /admin/api/brands/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $brand = Brand::find($id);

            if (! $brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Brand not found',
                ], 404);
            }

            // Check if brand has products
            $productCount = $brand->product()->count();
            if ($productCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete brand. It has {$productCount} associated product(s).",
                ], 422);
            }

            $brand->delete();

            return response()->json([
                'success' => true,
                'message' => 'Brand deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Delete brand failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'brand_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete brand',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update brand status.
     *
     * PATCH /admin/api/brands/{id}/status
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
                    'errors' => $validator->errors(),
                ], 422);
            }

            $brand = Brand::find($id);

            if (! $brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Brand not found',
                ], 404);
            }

            $brand->update([
                'status' => $request->status,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Brand status updated successfully',
                'data' => new BrandResource($brand->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update brand status failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'brand_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update brand status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Bulk actions (hide/show/delete).
     *
     * POST /admin/api/brands/bulk-action
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:brands,id',
                'action' => 'required|in:0,1,2', // 0=hide, 1=show, 2=delete
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $ids = $request->ids;
            $action = $request->action;
            $affected = 0;

            DB::beginTransaction();

            try {
                if ($action == 0) {
                    // Hide
                    $affected = Brand::whereIn('id', $ids)->update([
                        'status' => '0',
                        'user_id' => Auth::id(),
                    ]);
                } elseif ($action == 1) {
                    // Show
                    $affected = Brand::whereIn('id', $ids)->update([
                        'status' => '1',
                        'user_id' => Auth::id(),
                    ]);
                } else {
                    // Delete - check for products first
                    $brandsWithProducts = Brand::whereIn('id', $ids)
                        ->whereHas('product')
                        ->pluck('id')
                        ->toArray();

                    if (! empty($brandsWithProducts)) {
                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot delete brands with associated products',
                            'brand_ids' => $brandsWithProducts,
                        ], 422);
                    }

                    $affected = Brand::whereIn('id', $ids)->delete();
                }

                DB::commit();

                $actionNames = ['hidden', 'shown', 'deleted'];

                return response()->json([
                    'success' => true,
                    'message' => "Successfully {$actionNames[$action]} {$affected} brand(s)",
                    'affected_count' => $affected,
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Bulk action failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Upload brand images.
     *
     * POST /admin/api/brands/upload
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'files' => 'required',
                'files.*' => 'mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $uploadedFiles = [];

            if ($request->hasFile('files')) {
                $files = is_array($request->file('files'))
                    ? $request->file('files')
                    : [$request->file('files')];

                foreach ($files as $file) {
                    $name = $file->getClientOriginalName();
                    $path = '/uploads/images/image/';
                    $file->move(public_path('uploads/images/image'), $name);
                    $uploadedFiles[] = $path.$name;
                }
            }

            if (empty($uploadedFiles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files uploaded',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $uploadedFiles,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Upload brand images failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Process gallery from request (handles both form data and R2 session URLs).
     */
    private function processGallery(Request $request): array
    {
        // Handle form gallery array
        $imageOther = $request->imageOther ?? [];
        $imageOther = array_filter($imageOther, function ($url) {
            return ! empty($url) &&
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });
        $imageOther = array_values($imageOther);

        // Handle R2 session URLs
        $sessionKeyInput = $request->input('r2_session_key');
        $sessionUrls = [];
        if ($sessionKeyInput) {
            $sessionKeys = is_array($sessionKeyInput) ? $sessionKeyInput : explode(',', $sessionKeyInput);
            $sessionKeys = array_filter(array_map('trim', $sessionKeys));
            foreach ($sessionKeys as $sessionKey) {
                $urlsFromKey = Session::get($sessionKey, []);
                if (! empty($urlsFromKey)) {
                    if (is_array($urlsFromKey)) {
                        $sessionUrls = array_merge($sessionUrls, $urlsFromKey);
                    } else {
                        $sessionUrls[] = $urlsFromKey;
                    }
                }
            }
        }

        // Merge form URLs and session URLs
        $sessionUrls = array_filter($sessionUrls, function ($url) {
            return ! empty($url) &&
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });

        $allUrls = array_merge($imageOther, $sessionUrls);
        $gallery = array_values(array_unique($allUrls));

        // Clear session URLs after processing
        if ($sessionKeyInput) {
            $sessionKeys = is_array($sessionKeyInput) ? $sessionKeyInput : explode(',', $sessionKeyInput);
            $sessionKeys = array_filter(array_map('trim', $sessionKeys));
            foreach ($sessionKeys as $sessionKey) {
                Session::forget($sessionKey);
            }
        }

        return $gallery;
    }
}
