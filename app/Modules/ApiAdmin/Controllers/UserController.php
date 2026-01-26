<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private function checkRole(): bool
    {
        $user = Auth::user();
        return $user && $user->role_id == 1;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            if (!$this->checkRole()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = User::query();
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) {
                $keyword = $filters['keyword'];
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                      ->orWhere('email', 'like', '%' . $keyword . '%')
                      ->orWhere('phone', 'like', '%' . $keyword . '%');
                });
            }
            $query->orderBy('id', 'desc');
            
            $users = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get users list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get users list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            if (!$this->checkRole()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $user = User::find($id);
            if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);
            return response()->json(['success' => true, 'data' => new UserResource($user)], 200);
        } catch (\Exception $e) {
            Log::error('Get user details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get user details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if (!$this->checkRole()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'phone' => 'nullable|string',
                'role_id' => 'nullable|integer',
                'status' => 'required|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role_id' => $request->role_id ?? 2,
                'status' => $request->status,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => new UserResource($user),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create user failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create user'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$this->checkRole() && $user->id != $id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $targetUser = User::find($id);
            if (!$targetUser) return response()->json(['success' => false, 'message' => 'User not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'phone' => 'nullable|string',
                'role_id' => 'nullable|integer',
                'status' => 'sometimes|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('email')) $updateData['email'] = $request->email;
            if ($request->has('phone')) $updateData['phone'] = $request->phone;
            if ($request->has('role_id') && $this->checkRole()) $updateData['role_id'] = $request->role_id;
            if ($request->has('status') && $this->checkRole()) $updateData['status'] = $request->status;
            
            $targetUser->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => new UserResource($targetUser->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update user failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update user'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            if (!$this->checkRole()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $user = User::find($id);
            if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);
            if ($user->id == Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Cannot delete your own account'], 422);
            }
            $user->delete();
            return response()->json(['success' => true, 'message' => 'User deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete user failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete user'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            if (!$this->checkRole()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $user = User::find($id);
            if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);
            $user->update(['status' => $request->status]);
            return response()->json(['success' => true, 'message' => 'User status updated successfully', 'data' => new UserResource($user->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update user status failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update user status'], 500);
        }
    }

    public function changePassword(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$this->checkRole() && $user->id != $id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:6|confirmed',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $targetUser = User::find($id);
            if (!$targetUser) return response()->json(['success' => false, 'message' => 'User not found'], 404);
            
            $targetUser->update(['password' => Hash::make($request->password)]);
            
            return response()->json(['success' => true, 'message' => 'Password changed successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Change password failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to change password'], 500);
        }
    }

    public function checkEmail(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'exclude_id' => 'nullable|integer',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $query = User::where('email', $request->email);
            if ($request->has('exclude_id')) {
                $query->where('id', '!=', $request->exclude_id);
            }
            
            $exists = $query->exists();
            
            return response()->json([
                'success' => true,
                'available' => !$exists,
                'message' => $exists ? 'Email already exists' : 'Email is available',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Check email failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to check email'], 500);
        }
    }
}

