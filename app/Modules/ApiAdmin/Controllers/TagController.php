<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tag\TagResource;
use App\Modules\Tag\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Tag::query();
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) $query->where('name', 'like', $filters['keyword'] . '%');
            $query->orderBy('id', 'desc');
            
            $tags = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => TagResource::collection($tags->items()),
                'pagination' => [
                    'current_page' => $tags->currentPage(),
                    'per_page' => $tags->perPage(),
                    'total' => $tags->total(),
                    'last_page' => $tags->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get tags list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get tags list'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:tags,slug',
                'content' => 'nullable|string',
                'status' => 'required|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $slug = $request->slug;
            if (empty($slug)) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;
                while (Tag::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
            
            $tag = Tag::create([
                'name' => $request->name,
                'slug' => $slug,
                'content' => $request->content,
                'status' => $request->status,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Tag created successfully',
                'data' => new TagResource($tag),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create tag failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create tag'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $tag = Tag::find($id);
            if (!$tag) return response()->json(['success' => false, 'message' => 'Tag not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:tags,slug,' . $id,
                'content' => 'nullable|string',
                'status' => 'sometimes|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('slug')) $updateData['slug'] = $request->slug;
            if ($request->has('content')) $updateData['content'] = $request->content;
            if ($request->has('status')) $updateData['status'] = $request->status;
            
            $tag->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Tag updated successfully',
                'data' => new TagResource($tag->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update tag failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update tag'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $tag = Tag::find($id);
            if (!$tag) return response()->json(['success' => false, 'message' => 'Tag not found'], 404);
            $tag->delete();
            return response()->json(['success' => true, 'message' => 'Tag deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete tag failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete tag'], 500);
        }
    }
}

