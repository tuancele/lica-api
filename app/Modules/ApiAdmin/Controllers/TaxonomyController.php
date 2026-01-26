<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxonomy\StoreTaxonomyRequest;
use App\Http\Requests\Taxonomy\TaxonomyBulkActionRequest;
use App\Http\Requests\Taxonomy\TaxonomyStatusRequest;
use App\Http\Requests\Taxonomy\UpdateTaxonomyRequest;
use App\Http\Resources\Taxonomy\CategoryResource;
use App\Modules\Taxonomy\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaxonomyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('limit', 20);
            $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

            $query = Category::query()
                ->where('type', 'taxonomy');

            if ($request->filled('status')) {
                $query->where('status', (int) $request->get('status'));
            }
            if ($request->filled('keyword')) {
                $keyword = (string) $request->get('keyword');
                $query->where('name', 'like', '%' . $keyword . '%');
            }
            if ($request->filled('parent_id')) {
                $query->where('cat_id', (int) $request->get('parent_id'));
            }
            if ($request->filled('is_home')) {
                $query->where('is_home', (int) $request->get('is_home'));
            }
            if ($request->filled('feature')) {
                $query->where('feature', (int) $request->get('feature'));
            }

            $query->orderBy('sort', 'asc')->orderBy('id', 'asc');

            $items = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => CategoryResource::collection($items),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('List taxonomy categories failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '获取分类列表失败',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = Category::where('type', 'taxonomy')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('Show taxonomy category failed', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Danh muc khong ton tai',
            ], 404);
        }
    }

    public function store(StoreTaxonomyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $item = Category::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'image' => $data['image'] ?? null,
                'content' => $data['content'] ?? null,
                'status' => (int) ($data['status'] ?? 0),
                'feature' => (int) ($data['feature'] ?? 0),
                'is_home' => (int) ($data['is_home'] ?? 0),
                'tracking' => $data['tracking'] ?? null,
                'type' => 'taxonomy',
                'cat_id' => $data['cat_id'] ?? 0,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tao danh muc thanh cong',
                'data' => new CategoryResource($item),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Create taxonomy category failed', [
                'error' => $e->getMessage(),
                'payload' => $request->validated(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Tao danh muc that bai',
            ], 500);
        }
    }

    public function update(UpdateTaxonomyRequest $request, int $id): JsonResponse
    {
        try {
            $item = Category::where('type', 'taxonomy')->findOrFail($id);
            $data = $request->validated();

            $item->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'image' => $data['image'] ?? null,
                'content' => $data['content'] ?? null,
                'status' => (int) ($data['status'] ?? 0),
                'feature' => (int) ($data['feature'] ?? 0),
                'is_home' => (int) ($data['is_home'] ?? 0),
                'tracking' => $data['tracking'] ?? null,
                'type' => 'taxonomy',
                'cat_id' => $data['cat_id'] ?? 0,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cap nhat danh muc thanh cong',
                'data' => new CategoryResource($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('Update taxonomy category failed', [
                'error' => $e->getMessage(),
                'id' => $id,
                'payload' => $request->validated(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Cap nhat danh muc that bai',
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = Category::where('type', 'taxonomy')->findOrFail($id);

            $children = Category::where('type', 'taxonomy')->where('cat_id', $id)->count();
            if ($children > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh muc chua danh muc con, khong the xoa',
                ], 400);
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xoa danh muc thanh cong',
            ]);
        } catch (\Throwable $e) {
            Log::error('Delete taxonomy category failed', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Xoa danh muc that bai',
            ], 500);
        }
    }

    public function updateStatus(TaxonomyStatusRequest $request, int $id): JsonResponse
    {
        try {
            $item = Category::where('type', 'taxonomy')->findOrFail($id);
            $status = (int) $request->status;

            $item->update(['status' => $status]);

            return response()->json([
                'success' => true,
                'message' => 'Cap nhat trang thai thanh cong',
                'data' => new CategoryResource($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('Update taxonomy status failed', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Cap nhat trang thai that bai',
            ], 500);
        }
    }

    public function bulkAction(TaxonomyBulkActionRequest $request): JsonResponse
    {
        try {
            $ids = $request->checklist;
            $action = (int) $request->action;

            if ($action === 0 || $action === 1) {
                Category::where('type', 'taxonomy')
                    ->whereIn('id', $ids)
                    ->update(['status' => $action]);
            } elseif ($action === 2) {
                foreach ($ids as $id) {
                    $hasChildren = Category::where('type', 'taxonomy')
                        ->where('cat_id', (int) $id)
                        ->exists();
                    if ($hasChildren) {
                        continue;
                    }
                    Category::where('type', 'taxonomy')->where('id', (int) $id)->delete();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk action danh muc thanh cong',
            ]);
        } catch (\Throwable $e) {
            Log::error('Bulk taxonomy action failed', [
                'error' => $e->getMessage(),
                'ids' => $request->checklist,
                'action' => $request->action,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Bulk action danh muc that bai',
            ], 500);
        }
    }

    public function updateSort(Request $request): JsonResponse
    {
        try {
            $items = $request->get('items', []);
            if (!is_array($items)) {
                $items = [];
            }

            foreach ($items as $row) {
                $id = isset($row['id']) ? (int) $row['id'] : 0;
                if ($id <= 0) {
                    continue;
                }
                $parentId = isset($row['parent_id']) ? (int) $row['parent_id'] : 0;
                $sort = isset($row['sort']) ? (int) $row['sort'] : 0;

                Category::where('type', 'taxonomy')
                    ->where('id', $id)
                    ->update([
                        'cat_id' => $parentId,
                        'sort' => $sort,
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cap nhat sap xep danh muc thanh cong',
            ]);
        } catch (\Throwable $e) {
            Log::error('Update taxonomy sort failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Cap nhat sap xep that bai',
            ], 500);
        }
    }
}

