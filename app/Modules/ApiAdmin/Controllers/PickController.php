<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pick\PickResource;
use App\Modules\Pick\Models\Pick;
use App\Traits\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PickController extends Controller
{
    use Location;

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Pick::query();
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            $query->orderBy('sort', 'asc');
            
            $picks = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => PickResource::collection($picks->items()),
                'pagination' => [
                    'current_page' => $picks->currentPage(),
                    'per_page' => $picks->perPage(),
                    'total' => $picks->total(),
                    'last_page' => $picks->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get picks list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get picks list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $pick = Pick::with(['user', 'province', 'district', 'ward'])->find($id);
            if (!$pick) return response()->json(['success' => false, 'message' => 'Pick location not found'], 404);
            return response()->json(['success' => true, 'data' => new PickResource($pick)], 200);
        } catch (\Exception $e) {
            Log::error('Get pick details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get pick details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'tel' => 'required|string',
                'province_id' => 'required|integer',
                'district_id' => 'required|integer',
                'ward_id' => 'required|integer',
                'address' => 'required|string',
                'status' => 'required|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $pick = Pick::create([
                'name' => $request->name,
                'tel' => $request->tel,
                'province_id' => $request->province_id,
                'district_id' => $request->district_id,
                'ward_id' => $request->ward_id,
                'address' => $request->address,
                'status' => $request->status,
                'sort' => $request->sort ?? 0,
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pick location created successfully',
                'data' => new PickResource($pick->load(['user', 'province', 'district', 'ward'])),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create pick failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create pick location'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $pick = Pick::find($id);
            if (!$pick) return response()->json(['success' => false, 'message' => 'Pick location not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'tel' => 'sometimes|required|string',
                'province_id' => 'sometimes|required|integer',
                'district_id' => 'sometimes|required|integer',
                'ward_id' => 'sometimes|required|integer',
                'address' => 'sometimes|required|string',
                'status' => 'sometimes|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('tel')) $updateData['tel'] = $request->tel;
            if ($request->has('province_id')) $updateData['province_id'] = $request->province_id;
            if ($request->has('district_id')) $updateData['district_id'] = $request->district_id;
            if ($request->has('ward_id')) $updateData['ward_id'] = $request->ward_id;
            if ($request->has('address')) $updateData['address'] = $request->address;
            if ($request->has('status')) $updateData['status'] = $request->status;
            if ($request->has('sort')) $updateData['sort'] = $request->sort;
            $updateData['user_id'] = Auth::id();
            
            $pick->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Pick location updated successfully',
                'data' => new PickResource($pick->fresh()->load(['user', 'province', 'district', 'ward'])),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update pick failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update pick location'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $pick = Pick::find($id);
            if (!$pick) return response()->json(['success' => false, 'message' => 'Pick location not found'], 404);
            $pick->delete();
            return response()->json(['success' => true, 'message' => 'Pick location deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete pick failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete pick location'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $pick = Pick::find($id);
            if (!$pick) return response()->json(['success' => false, 'message' => 'Pick location not found'], 404);
            $pick->update(['status' => $request->status, 'user_id' => Auth::id()]);
            return response()->json(['success' => true, 'message' => 'Pick location status updated successfully', 'data' => new PickResource($pick->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update pick status failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update pick location status'], 500);
        }
    }

    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:picks,id',
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
                    $affected = Pick::whereIn('id', $ids)->update(['status' => '0', 'user_id' => Auth::id()]);
                } elseif ($action == 1) {
                    $affected = Pick::whereIn('id', $ids)->update(['status' => '1', 'user_id' => Auth::id()]);
                } else {
                    $affected = Pick::whereIn('id', $ids)->delete();
                }
                DB::commit();
                $actionNames = ['hidden', 'shown', 'deleted'];
                return response()->json(['success' => true, 'message' => "Successfully {$actionNames[$action]} {$affected} pick location(s)", 'affected_count' => $affected], 200);
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
                'sort.*.id' => 'required|integer|exists:picks,id',
                'sort.*.sort' => 'required|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            DB::beginTransaction();
            try {
                foreach ($request->sort as $item) {
                    Pick::where('id', $item['id'])->update(['sort' => $item['sort'], 'user_id' => Auth::id()]);
                }
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Pick location sort order updated successfully'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Update pick sort failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update pick location sort order'], 500);
        }
    }

    public function getDistrict(int $id, int $districtId): JsonResponse
    {
        try {
            $pick = Pick::find($id);
            if (!$pick) return response()->json(['success' => false, 'message' => 'Pick location not found'], 404);
            $district = $this->getDistrict($pick->province_id, $districtId);
            return response()->json(['success' => true, 'data' => $district], 200);
        } catch (\Exception $e) {
            Log::error('Get district failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get district'], 500);
        }
    }

    public function getWard(int $id, int $wardId): JsonResponse
    {
        try {
            $pick = Pick::find($id);
            if (!$pick) return response()->json(['success' => false, 'message' => 'Pick location not found'], 404);
            $ward = $this->getWard($pick->district_id, $wardId);
            return response()->json(['success' => true, 'data' => $ward], 200);
        } catch (\Exception $e) {
            Log::error('Get ward failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get ward'], 500);
        }
    }
}

