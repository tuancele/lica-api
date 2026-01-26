<?php

declare(strict_types=1);

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Origin\OriginResource;
use App\Modules\Origin\Models\Origin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Origin API Controller for Admin
 * Base URL: /admin/api/origins.
 */
class OriginController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') {
                $filters['status'] = $request->status;
            }
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }

            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

            $query = Origin::query();
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['keyword'])) {
                $query->where('name', 'like', '%'.$filters['keyword'].'%');
            }
            $query->orderBy('sort', 'asc');

            $origins = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => OriginResource::collection($origins->items()),
                'pagination' => [
                    'current_page' => $origins->currentPage(),
                    'per_page' => $origins->perPage(),
                    'total' => $origins->total(),
                    'last_page' => $origins->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get origins list failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get origins list',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $origin = Origin::with('user')->find($id);
            if (! $origin) {
                return response()->json(['success' => false, 'message' => 'Origin not found'], 404);
            }

            return response()->json(['success' => true, 'data' => new OriginResource($origin)], 200);
        } catch (\Exception $e) {
            Log::error('Get origin details failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get origin details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'status' => 'required|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $origin = Origin::create([
                'name' => $request->name,
                'status' => $request->status,
                'sort' => $request->sort ?? 0,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Origin created successfully',
                'data' => new OriginResource($origin->load('user')),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create origin failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to create origin'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $origin = Origin::find($id);
            if (! $origin) {
                return response()->json(['success' => false, 'message' => 'Origin not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
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
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('sort')) {
                $updateData['sort'] = $request->sort;
            }
            $updateData['user_id'] = Auth::id();

            $origin->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Origin updated successfully',
                'data' => new OriginResource($origin->fresh()->load('user')),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update origin failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update origin'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $origin = Origin::find($id);
            if (! $origin) {
                return response()->json(['success' => false, 'message' => 'Origin not found'], 404);
            }
            $origin->delete();

            return response()->json(['success' => true, 'message' => 'Origin deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete origin failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete origin'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $origin = Origin::find($id);
            if (! $origin) {
                return response()->json(['success' => false, 'message' => 'Origin not found'], 404);
            }
            $origin->update(['status' => $request->status, 'user_id' => Auth::id()]);

            return response()->json(['success' => true, 'message' => 'Origin status updated successfully', 'data' => new OriginResource($origin->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update origin status failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update origin status'], 500);
        }
    }

    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:origins,id',
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
                    $affected = Origin::whereIn('id', $ids)->update(['status' => '0', 'user_id' => Auth::id()]);
                } elseif ($action == 1) {
                    $affected = Origin::whereIn('id', $ids)->update(['status' => '1', 'user_id' => Auth::id()]);
                } else {
                    $affected = Origin::whereIn('id', $ids)->delete();
                }
                DB::commit();
                $actionNames = ['hidden', 'shown', 'deleted'];

                return response()->json(['success' => true, 'message' => "Successfully {$actionNames[$action]} {$affected} origin(s)", 'affected_count' => $affected], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Bulk action failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Bulk action failed'], 500);
        }
    }

    public function updateSort(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sort' => 'required|array',
                'sort.*.id' => 'required|integer|exists:origins,id',
                'sort.*.sort' => 'required|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            try {
                foreach ($request->sort as $item) {
                    Origin::where('id', $item['id'])->update(['sort' => $item['sort'], 'user_id' => Auth::id()]);
                }
                DB::commit();

                return response()->json(['success' => true, 'message' => 'Origin sort order updated successfully'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Update origin sort failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update origin sort order'], 500);
        }
    }
}
