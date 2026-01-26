<?php

declare(strict_types=1);

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Menu\MenuResource;
use App\Modules\Menu\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $groupId = $request->get('group_id');
            $query = Menu::query();
            if ($groupId) {
                $query->where('group_id', $groupId);
            }
            $query->orderBy('sort', 'asc');

            $menus = $query->get();
            $treeData = $this->buildTree($menus);

            return response()->json([
                'success' => true,
                'data' => MenuResource::collection($treeData),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get menus list failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get menus list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $menu = Menu::with('children')->find($id);
            if (! $menu) {
                return response()->json(['success' => false, 'message' => 'Menu not found'], 404);
            }

            return response()->json(['success' => true, 'data' => new MenuResource($menu)], 200);
        } catch (\Exception $e) {
            Log::error('Get menu details failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get menu details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'url' => 'required|string|max:500',
                'group_id' => 'required|integer',
                'parent' => 'nullable|integer|min:0',
                'status' => 'required|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $menu = Menu::create([
                'name' => $request->name,
                'url' => $request->url,
                'group_id' => $request->group_id,
                'parent' => $request->parent ?? 0,
                'status' => $request->status,
                'sort' => $request->sort ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Menu created successfully',
                'data' => new MenuResource($menu),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create menu failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to create menu'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $menu = Menu::find($id);
            if (! $menu) {
                return response()->json(['success' => false, 'message' => 'Menu not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'url' => 'sometimes|required|string|max:500',
                'group_id' => 'sometimes|required|integer',
                'parent' => 'nullable|integer|min:0',
                'status' => 'sometimes|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $updateData = [];
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('url')) {
                $updateData['url'] = $request->url;
            }
            if ($request->has('group_id')) {
                $updateData['group_id'] = $request->group_id;
            }
            if ($request->has('parent')) {
                $updateData['parent'] = $request->parent;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('sort')) {
                $updateData['sort'] = $request->sort;
            }

            $menu->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Menu updated successfully',
                'data' => new MenuResource($menu->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update menu failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update menu'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $menu = Menu::find($id);
            if (! $menu) {
                return response()->json(['success' => false, 'message' => 'Menu not found'], 404);
            }
            $menu->delete();

            return response()->json(['success' => true, 'message' => 'Menu deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete menu failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete menu'], 500);
        }
    }

    public function updateSort(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sortable' => 'required|array',
                'sortable.*.item_id' => 'required|integer|exists:menus,id',
                'sortable.*.parent_id' => 'required|integer',
                'sortable.*.sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            try {
                foreach ($request->sortable as $order => $value) {
                    $id = (int) $value['item_id'];
                    $parentId = (int) $value['parent_id'];
                    $sort = isset($value['sort']) ? (int) $value['sort'] : $order;

                    Menu::where('id', $id)->update([
                        'parent' => $parentId,
                        'sort' => $sort,
                    ]);
                }
                DB::commit();

                return response()->json(['success' => true, 'message' => 'Menu sort order updated successfully'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Update menu sort failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update menu sort order'], 500);
        }
    }

    private function buildTree($menus, $parentId = 0)
    {
        return $menus->filter(function ($menu) use ($parentId) {
            return ($menu->parent ?? 0) == $parentId;
        })->map(function ($menu) use ($menus) {
            $menu->setRelation('children', $this->buildTree($menus, $menu->id));

            return $menu;
        })->values();
    }
}
