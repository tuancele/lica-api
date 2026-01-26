<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Redirection\RedirectionResource;
use App\Modules\Redirection\Models\Redirection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RedirectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Redirection::query();
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) {
                $keyword = $filters['keyword'];
                $query->where(function ($q) use ($keyword) {
                    $q->where('link_from', 'like', '%' . $keyword . '%')
                      ->orWhere('link_to', 'like', '%' . $keyword . '%');
                });
            }
            $query->orderBy('created_at', 'desc');
            
            $redirections = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => RedirectionResource::collection($redirections->items()),
                'pagination' => [
                    'current_page' => $redirections->currentPage(),
                    'per_page' => $redirections->perPage(),
                    'total' => $redirections->total(),
                    'last_page' => $redirections->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get redirections list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get redirections list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $redirection = Redirection::find($id);
            if (!$redirection) return response()->json(['success' => false, 'message' => 'Redirection not found'], 404);
            return response()->json(['success' => true, 'data' => new RedirectionResource($redirection)], 200);
        } catch (\Exception $e) {
            Log::error('Get redirection details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get redirection details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'link_from' => 'required|string',
                'link_to' => 'required|string',
                'type' => 'required|in:301,302',
                'status' => 'required|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $redirection = Redirection::create([
                'link_from' => $request->link_from,
                'link_to' => $request->link_to,
                'type' => $request->type,
                'status' => $request->status,
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Redirection created successfully',
                'data' => new RedirectionResource($redirection),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create redirection failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create redirection'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $redirection = Redirection::find($id);
            if (!$redirection) return response()->json(['success' => false, 'message' => 'Redirection not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'link_from' => 'sometimes|required|string',
                'link_to' => 'sometimes|required|string',
                'type' => 'sometimes|required|in:301,302',
                'status' => 'sometimes|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('link_from')) $updateData['link_from'] = $request->link_from;
            if ($request->has('link_to')) $updateData['link_to'] = $request->link_to;
            if ($request->has('type')) $updateData['type'] = $request->type;
            if ($request->has('status')) $updateData['status'] = $request->status;
            
            $redirection->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Redirection updated successfully',
                'data' => new RedirectionResource($redirection->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update redirection failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update redirection'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $redirection = Redirection::find($id);
            if (!$redirection) return response()->json(['success' => false, 'message' => 'Redirection not found'], 404);
            $redirection->delete();
            return response()->json(['success' => true, 'message' => 'Redirection deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete redirection failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete redirection'], 500);
        }
    }
}

