<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Video\VideoResource;
use App\Modules\Video\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Video::where('type', 'video');
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            $query->orderBy('id', 'desc');
            
            $videos = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => VideoResource::collection($videos->items()),
                'pagination' => [
                    'current_page' => $videos->currentPage(),
                    'per_page' => $videos->perPage(),
                    'total' => $videos->total(),
                    'last_page' => $videos->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get videos list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get videos list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $video = Video::where('type', 'video')->with('user')->find($id);
            if (!$video) return response()->json(['success' => false, 'message' => 'Video not found'], 404);
            return response()->json(['success' => true, 'data' => new VideoResource($video)], 200);
        } catch (\Exception $e) {
            Log::error('Get video details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get video details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:posts,slug',
                'image' => 'nullable|string',
                'video_url' => 'nullable|string',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
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
                while (Video::where('slug', $slug)->where('type', 'video')->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
            
            $video = Video::create([
                'name' => $request->name,
                'slug' => $slug,
                'image' => $request->image,
                'video_url' => $request->video_url,
                'description' => $request->description,
                'content' => $request->content,
                'status' => $request->status,
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'type' => 'video',
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Video created successfully',
                'data' => new VideoResource($video->load('user')),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create video failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create video'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $video = Video::where('type', 'video')->find($id);
            if (!$video) return response()->json(['success' => false, 'message' => 'Video not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:posts,slug,' . $id,
                'image' => 'nullable|string',
                'video_url' => 'nullable|string',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'status' => 'sometimes|in:0,1',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string|max:500',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('slug')) $updateData['slug'] = $request->slug;
            if ($request->has('image')) $updateData['image'] = $request->image;
            if ($request->has('video_url')) $updateData['video_url'] = $request->video_url;
            if ($request->has('description')) $updateData['description'] = $request->description;
            if ($request->has('content')) $updateData['content'] = $request->content;
            if ($request->has('status')) $updateData['status'] = $request->status;
            if ($request->has('seo_title')) $updateData['seo_title'] = $request->seo_title;
            if ($request->has('seo_description')) $updateData['seo_description'] = $request->seo_description;
            $updateData['user_id'] = Auth::id();
            
            $video->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Video updated successfully',
                'data' => new VideoResource($video->fresh()->load('user')),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update video failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update video'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $video = Video::where('type', 'video')->find($id);
            if (!$video) return response()->json(['success' => false, 'message' => 'Video not found'], 404);
            $video->delete();
            return response()->json(['success' => true, 'message' => 'Video deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete video failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete video'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $video = Video::where('type', 'video')->find($id);
            if (!$video) return response()->json(['success' => false, 'message' => 'Video not found'], 404);
            $video->update(['status' => $request->status, 'user_id' => Auth::id()]);
            return response()->json(['success' => true, 'message' => 'Video status updated successfully', 'data' => new VideoResource($video->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update video status failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update video status'], 500);
        }
    }
}

