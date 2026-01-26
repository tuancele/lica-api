<?php

declare(strict_types=1);

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\FooterBlock\FooterBlockResource;
use App\Modules\FooterBlock\Models\FooterBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FooterBlockController extends Controller
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

            $query = FooterBlock::query();
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['keyword'])) {
                $query->where('title', 'like', '%'.$filters['keyword'].'%');
            }
            $query->orderBy('sort', 'asc');

            $blocks = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => FooterBlockResource::collection($blocks->items()),
                'pagination' => [
                    'current_page' => $blocks->currentPage(),
                    'per_page' => $blocks->perPage(),
                    'total' => $blocks->total(),
                    'last_page' => $blocks->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get footer blocks list failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get footer blocks list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $block = FooterBlock::find($id);
            if (! $block) {
                return response()->json(['success' => false, 'message' => 'Footer block not found'], 404);
            }

            return response()->json(['success' => true, 'data' => new FooterBlockResource($block)], 200);
        } catch (\Exception $e) {
            Log::error('Get footer block details failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get footer block details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'tags' => 'nullable|array',
                'links' => 'nullable|array',
                'status' => 'required|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $block = FooterBlock::create([
                'title' => $request->title,
                'tags' => $request->tags ?? [],
                'links' => $request->links ?? [],
                'status' => $request->status,
                'sort' => $request->sort ?? 0,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Footer block created successfully',
                'data' => new FooterBlockResource($block),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create footer block failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to create footer block'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $block = FooterBlock::find($id);
            if (! $block) {
                return response()->json(['success' => false, 'message' => 'Footer block not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'tags' => 'nullable|array',
                'links' => 'nullable|array',
                'status' => 'sometimes|in:0,1',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $updateData = [];
            if ($request->has('title')) {
                $updateData['title'] = $request->title;
            }
            if ($request->has('tags')) {
                $updateData['tags'] = $request->tags;
            }
            if ($request->has('links')) {
                $updateData['links'] = $request->links;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('sort')) {
                $updateData['sort'] = $request->sort;
            }

            $block->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Footer block updated successfully',
                'data' => new FooterBlockResource($block->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update footer block failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update footer block'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $block = FooterBlock::find($id);
            if (! $block) {
                return response()->json(['success' => false, 'message' => 'Footer block not found'], 404);
            }
            $block->delete();

            return response()->json(['success' => true, 'message' => 'Footer block deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete footer block failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete footer block'], 500);
        }
    }
}
