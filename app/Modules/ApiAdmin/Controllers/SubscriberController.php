<?php

declare(strict_types=1);

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subscriber\SubscriberResource;
use App\Modules\Subcriber\Models\Subcriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubscriberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }

            $perPage = (int) $request->get('limit', 20);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

            $query = Subcriber::query();
            if (isset($filters['keyword'])) {
                $query->where('email', 'like', '%'.$filters['keyword'].'%');
            }
            $query->latest();

            $subscribers = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => SubscriberResource::collection($subscribers->items()),
                'pagination' => [
                    'current_page' => $subscribers->currentPage(),
                    'per_page' => $subscribers->perPage(),
                    'total' => $subscribers->total(),
                    'last_page' => $subscribers->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get subscribers list failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get subscribers list'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:subcribers,email',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $subscriber = Subcriber::create(['email' => $request->email]);

            return response()->json([
                'success' => true,
                'message' => 'Subscriber added successfully',
                'data' => new SubscriberResource($subscriber),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create subscriber failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to add subscriber'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $subscriber = Subcriber::find($id);
            if (! $subscriber) {
                return response()->json(['success' => false, 'message' => 'Subscriber not found'], 404);
            }
            $subscriber->delete();

            return response()->json(['success' => true, 'message' => 'Subscriber deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete subscriber failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete subscriber'], 500);
        }
    }

    public function export(Request $request): JsonResponse
    {
        try {
            $subscribers = Subcriber::select('email', 'created_at')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => SubscriberResource::collection($subscribers),
                'count' => $subscribers->count(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Export subscribers failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to export subscribers'], 500);
        }
    }
}
