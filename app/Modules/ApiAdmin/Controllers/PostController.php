<?php

declare(strict_types=1);

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Post\PostResource;
use App\Modules\Post\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') {
                $filters['status'] = $request->status;
            }
            if ($request->has('cat_id') && $request->cat_id !== '') {
                $filters['cat_id'] = $request->cat_id;
            }
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }

            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

            $query = Post::where('type', 'post');
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['cat_id'])) {
                $query->where('cat_id', $filters['cat_id']);
            }
            if (isset($filters['keyword'])) {
                $query->where('name', 'like', '%'.$filters['keyword'].'%');
            }
            $query->orderBy('created_at', 'desc');

            $posts = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => PostResource::collection($posts->items()),
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'last_page' => $posts->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get posts list failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get posts list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $post = Post::where('type', 'post')->with('user')->find($id);
            if (! $post) {
                return response()->json(['success' => false, 'message' => 'Post not found'], 404);
            }

            return response()->json(['success' => true, 'data' => new PostResource($post)], 200);
        } catch (\Exception $e) {
            Log::error('Get post details failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get post details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:posts,slug',
                'image' => 'nullable|string',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'cat_id' => 'nullable|integer',
                'status' => 'required|in:0,1',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string|max:500',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $slug = $request->slug;
            if (empty($slug)) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;
                while (Post::where('slug', $slug)->exists()) {
                    $slug = $originalSlug.'-'.$counter;
                    $counter++;
                }
            }

            $post = Post::create([
                'name' => $request->name,
                'slug' => $slug,
                'image' => $request->image,
                'description' => $request->description,
                'content' => $request->content,
                'cat_id' => $request->cat_id ?? 0,
                'status' => $request->status,
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'type' => 'post',
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => new PostResource($post->load('user')),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create post failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to create post'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $post = Post::where('type', 'post')->find($id);
            if (! $post) {
                return response()->json(['success' => false, 'message' => 'Post not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:posts,slug,'.$id,
                'image' => 'nullable|string',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'cat_id' => 'nullable|integer',
                'status' => 'sometimes|in:0,1',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string|max:500',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $updateData = [];
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('slug')) {
                $updateData['slug'] = $request->slug;
            }
            if ($request->has('image')) {
                $updateData['image'] = $request->image;
            }
            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }
            if ($request->has('content')) {
                $updateData['content'] = $request->content;
            }
            if ($request->has('cat_id')) {
                $updateData['cat_id'] = $request->cat_id;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('seo_title')) {
                $updateData['seo_title'] = $request->seo_title;
            }
            if ($request->has('seo_description')) {
                $updateData['seo_description'] = $request->seo_description;
            }
            $updateData['user_id'] = Auth::id();

            $post->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully',
                'data' => new PostResource($post->fresh()->load('user')),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update post failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update post'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $post = Post::where('type', 'post')->find($id);
            if (! $post) {
                return response()->json(['success' => false, 'message' => 'Post not found'], 404);
            }
            $post->delete();

            return response()->json(['success' => true, 'message' => 'Post deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete post failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete post'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $post = Post::where('type', 'post')->find($id);
            if (! $post) {
                return response()->json(['success' => false, 'message' => 'Post not found'], 404);
            }
            $post->update(['status' => $request->status, 'user_id' => Auth::id()]);

            return response()->json(['success' => true, 'message' => 'Post status updated successfully', 'data' => new PostResource($post->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update post status failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update post status'], 500);
        }
    }
}
