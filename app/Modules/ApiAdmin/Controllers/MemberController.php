<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Member\MemberResource;
use App\Http\Resources\Member\AddressResource;
use App\Modules\Member\Models\Member;
use App\Modules\Address\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 20);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;
            
            $query = Member::query();
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) {
                $keyword = $filters['keyword'];
                $query->where(function ($q) use ($keyword) {
                    $q->where('first_name', 'like', '%' . $keyword . '%')
                      ->orWhere('last_name', 'like', '%' . $keyword . '%')
                      ->orWhere('email', 'like', '%' . $keyword . '%')
                      ->orWhere('phone', 'like', '%' . $keyword . '%');
                });
            }
            $query->orderBy('id', 'desc');
            
            $members = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => MemberResource::collection($members->items()),
                'pagination' => [
                    'current_page' => $members->currentPage(),
                    'per_page' => $members->perPage(),
                    'total' => $members->total(),
                    'last_page' => $members->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get members list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get members list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $member = Member::with('address')->find($id);
            if (!$member) return response()->json(['success' => false, 'message' => 'Member not found'], 404);
            return response()->json(['success' => true, 'data' => new MemberResource($member)], 200);
        } catch (\Exception $e) {
            Log::error('Get member details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get member details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email|unique:members,email',
                'phone' => 'required|string',
                'password' => 'nullable|string|min:6',
                'status' => 'required|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $member = Member::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password ? Hash::make($request->password) : Hash::make('password123'),
                'status' => $request->status,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Member created successfully',
                'data' => new MemberResource($member->load('address')),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create member failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create member'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $member = Member::find($id);
            if (!$member) return response()->json(['success' => false, 'message' => 'Member not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'first_name' => 'sometimes|required|string',
                'last_name' => 'sometimes|required|string',
                'email' => 'sometimes|required|email|unique:members,email,' . $id,
                'phone' => 'sometimes|required|string',
                'status' => 'sometimes|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('first_name')) $updateData['first_name'] = $request->first_name;
            if ($request->has('last_name')) $updateData['last_name'] = $request->last_name;
            if ($request->has('email')) $updateData['email'] = $request->email;
            if ($request->has('phone')) $updateData['phone'] = $request->phone;
            if ($request->has('status')) $updateData['status'] = $request->status;
            
            $member->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Member updated successfully',
                'data' => new MemberResource($member->fresh()->load('address')),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update member failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update member'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $member = Member::find($id);
            if (!$member) return response()->json(['success' => false, 'message' => 'Member not found'], 404);
            $member->delete();
            return response()->json(['success' => true, 'message' => 'Member deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete member failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete member'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $member = Member::find($id);
            if (!$member) return response()->json(['success' => false, 'message' => 'Member not found'], 404);
            $member->update(['status' => $request->status]);
            return response()->json(['success' => true, 'message' => 'Member status updated successfully', 'data' => new MemberResource($member->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update member status failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update member status'], 500);
        }
    }

    public function addAddress(Request $request, int $id): JsonResponse
    {
        try {
            $member = Member::find($id);
            if (!$member) return response()->json(['success' => false, 'message' => 'Member not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string',
                'phone' => 'required|string',
                'address' => 'required|string',
                'province_id' => 'required|integer',
                'district_id' => 'required|integer',
                'ward_id' => 'required|integer',
                'is_default' => 'nullable|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            if ($request->is_default == 1) {
                Address::where('member_id', $id)->update(['is_default' => 0]);
            }
            
            $address = Address::create([
                'member_id' => $id,
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'address' => $request->address,
                'province_id' => $request->province_id,
                'district_id' => $request->district_id,
                'ward_id' => $request->ward_id,
                'is_default' => $request->is_default ?? 0,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Address added successfully',
                'data' => new AddressResource($address),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Add address failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add address'], 500);
        }
    }

    public function updateAddress(Request $request, int $id, int $addressId): JsonResponse
    {
        try {
            $member = Member::find($id);
            if (!$member) return response()->json(['success' => false, 'message' => 'Member not found'], 404);
            
            $address = Address::where('member_id', $id)->find($addressId);
            if (!$address) return response()->json(['success' => false, 'message' => 'Address not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|required|string',
                'phone' => 'sometimes|required|string',
                'address' => 'sometimes|required|string',
                'province_id' => 'sometimes|required|integer',
                'district_id' => 'sometimes|required|integer',
                'ward_id' => 'sometimes|required|integer',
                'is_default' => 'nullable|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            if ($request->is_default == 1) {
                Address::where('member_id', $id)->where('id', '!=', $addressId)->update(['is_default' => 0]);
            }
            
            $updateData = [];
            if ($request->has('full_name')) $updateData['full_name'] = $request->full_name;
            if ($request->has('phone')) $updateData['phone'] = $request->phone;
            if ($request->has('address')) $updateData['address'] = $request->address;
            if ($request->has('province_id')) $updateData['province_id'] = $request->province_id;
            if ($request->has('district_id')) $updateData['district_id'] = $request->district_id;
            if ($request->has('ward_id')) $updateData['ward_id'] = $request->ward_id;
            if ($request->has('is_default')) $updateData['is_default'] = $request->is_default;
            
            $address->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully',
                'data' => new AddressResource($address->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update address failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update address'], 500);
        }
    }

    public function deleteAddress(int $id, int $addressId): JsonResponse
    {
        try {
            $member = Member::find($id);
            if (!$member) return response()->json(['success' => false, 'message' => 'Member not found'], 404);
            
            $address = Address::where('member_id', $id)->find($addressId);
            if (!$address) return response()->json(['success' => false, 'message' => 'Address not found'], 404);
            
            $address->delete();
            
            return response()->json(['success' => true, 'message' => 'Address deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete address failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete address'], 500);
        }
    }

    public function changePassword(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:6|confirmed',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $member = Member::find($id);
            if (!$member) return response()->json(['success' => false, 'message' => 'Member not found'], 404);
            
            $member->update(['password' => Hash::make($request->password)]);
            
            return response()->json(['success' => true, 'message' => 'Password changed successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Change password failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to change password'], 500);
        }
    }
}

