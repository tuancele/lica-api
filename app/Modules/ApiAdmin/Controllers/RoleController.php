<?php

declare(strict_types=1);

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Role\RoleResource;
use App\Modules\Permission\Models\Permission;
use App\Modules\Role\Models\Role;
use App\Modules\Role\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
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

            $perPage = (int) $request->get('limit', 20);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

            $query = Role::withCount('permissions');
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['keyword'])) {
                $query->where('name', 'like', '%'.$filters['keyword'].'%');
            }
            $query->orderBy('id', 'asc');

            $roles = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => RoleResource::collection($roles->items()),
                'pagination' => [
                    'current_page' => $roles->currentPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total(),
                    'last_page' => $roles->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get roles list failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get roles list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $role = Role::with(['user', 'permissions'])->find($id);
            if (! $role) {
                return response()->json(['success' => false, 'message' => 'Role not found'], 404);
            }

            return response()->json(['success' => true, 'data' => new RoleResource($role)], 200);
        } catch (\Exception $e) {
            Log::error('Get role details failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get role details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'status' => 'required|in:0,1',
                'permissions' => 'nullable|array',
                'permissions.*' => 'integer|exists:permissions,id',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            try {
                $role = Role::create([
                    'name' => $request->name,
                    'status' => $request->status,
                    'user_id' => Auth::id(),
                ]);

                if ($request->has('permissions') && is_array($request->permissions)) {
                    foreach ($request->permissions as $permissionId) {
                        RolePermission::create([
                            'role_id' => $role->id,
                            'permission_id' => $permissionId,
                            'user_id' => Auth::id(),
                        ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Role created successfully',
                    'data' => new RoleResource($role->load(['user', 'permissions'])),
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Create role failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to create role'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $role = Role::find($id);
            if (! $role) {
                return response()->json(['success' => false, 'message' => 'Role not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string',
                'status' => 'sometimes|in:0,1',
                'permissions' => 'nullable|array',
                'permissions.*' => 'integer|exists:permissions,id',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            try {
                $updateData = [];
                if ($request->has('name')) {
                    $updateData['name'] = $request->name;
                }
                if ($request->has('status')) {
                    $updateData['status'] = $request->status;
                }
                $updateData['user_id'] = Auth::id();

                $role->update($updateData);

                if ($request->has('permissions')) {
                    RolePermission::where('role_id', $id)->delete();
                    if (is_array($request->permissions)) {
                        foreach ($request->permissions as $permissionId) {
                            RolePermission::create([
                                'role_id' => $id,
                                'permission_id' => $permissionId,
                                'user_id' => Auth::id(),
                            ]);
                        }
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Role updated successfully',
                    'data' => new RoleResource($role->fresh()->load(['user', 'permissions'])),
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Update role failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update role'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $role = Role::find($id);
            if (! $role) {
                return response()->json(['success' => false, 'message' => 'Role not found'], 404);
            }

            DB::beginTransaction();
            try {
                RolePermission::where('role_id', $id)->delete();
                $role->delete();
                DB::commit();

                return response()->json(['success' => true, 'message' => 'Role deleted successfully'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Delete role failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete role'], 500);
        }
    }

    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        try {
            $role = Role::find($id);
            if (! $role) {
                return response()->json(['success' => false, 'message' => 'Role not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'integer|exists:permissions,id',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            try {
                RolePermission::where('role_id', $id)->delete();
                foreach ($request->permissions as $permissionId) {
                    RolePermission::create([
                        'role_id' => $id,
                        'permission_id' => $permissionId,
                        'user_id' => Auth::id(),
                    ]);
                }
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Permissions assigned successfully',
                    'data' => new RoleResource($role->fresh()->load(['user', 'permissions'])),
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Assign permissions failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to assign permissions'], 500);
        }
    }

    public function getPermissions(): JsonResponse
    {
        try {
            $permissions = Permission::where('parent_id', '0')
                ->orderBy('sort', 'asc')
                ->with('children')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $permissions,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get permissions failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get permissions'], 500);
        }
    }
}
