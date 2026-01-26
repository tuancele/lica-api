<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Feedback\FeedbackResource;
use App\Modules\Feedback\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Feedback::query();
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            $query->orderBy('name', 'asc');
            
            $feedbacks = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => FeedbackResource::collection($feedbacks->items()),
                'pagination' => [
                    'current_page' => $feedbacks->currentPage(),
                    'per_page' => $feedbacks->perPage(),
                    'total' => $feedbacks->total(),
                    'last_page' => $feedbacks->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get feedbacks list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get feedbacks list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $feedback = Feedback::find($id);
            if (!$feedback) return response()->json(['success' => false, 'message' => 'Feedback not found'], 404);
            return response()->json(['success' => true, 'data' => new FeedbackResource($feedback)], 200);
        } catch (\Exception $e) {
            Log::error('Get feedback details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get feedback details'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $feedback = Feedback::find($id);
            if (!$feedback) return response()->json(['success' => false, 'message' => 'Feedback not found'], 404);
            $feedback->delete();
            return response()->json(['success' => true, 'message' => 'Feedback deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete feedback failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete feedback'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $feedback = Feedback::find($id);
            if (!$feedback) return response()->json(['success' => false, 'message' => 'Feedback not found'], 404);
            $feedback->update(['status' => $request->status]);
            return response()->json(['success' => true, 'message' => 'Feedback status updated successfully', 'data' => new FeedbackResource($feedback->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update feedback status failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update feedback status'], 500);
        }
    }
}

