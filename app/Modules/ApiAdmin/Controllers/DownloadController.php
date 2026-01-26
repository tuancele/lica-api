<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Download\DownloadResource;
use App\Modules\Download\Models\Download;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Download::where('type', 'download');
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            $query->orderBy('id', 'desc');
            
            $downloads = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => DownloadResource::collection($downloads->items()),
                'pagination' => [
                    'current_page' => $downloads->currentPage(),
                    'per_page' => $downloads->perPage(),
                    'total' => $downloads->total(),
                    'last_page' => $downloads->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get downloads list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get downloads list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $download = Download::where('type', 'download')->find($id);
            if (!$download) return response()->json(['success' => false, 'message' => 'Download not found'], 404);
            return response()->json(['success' => true, 'data' => new DownloadResource($download)], 200);
        } catch (\Exception $e) {
            Log::error('Get download details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get download details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:posts,slug',
                'file' => 'nullable|string',
                'description' => 'nullable|string',
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
                while (Download::where('slug', $slug)->where('type', 'download')->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
            
            $download = Download::create([
                'name' => $request->name,
                'slug' => $slug,
                'file' => $request->file,
                'description' => $request->description,
                'content' => $request->content,
                'status' => $request->status,
                'type' => 'download',
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Download created successfully',
                'data' => new DownloadResource($download),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create download failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create download'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $download = Download::where('type', 'download')->find($id);
            if (!$download) return response()->json(['success' => false, 'message' => 'Download not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'slug' => 'nullable|string|max:250|unique:posts,slug,' . $id,
                'file' => 'nullable|string',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'status' => 'sometimes|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('slug')) $updateData['slug'] = $request->slug;
            if ($request->has('file')) $updateData['file'] = $request->file;
            if ($request->has('description')) $updateData['description'] = $request->description;
            if ($request->has('content')) $updateData['content'] = $request->content;
            if ($request->has('status')) $updateData['status'] = $request->status;
            $updateData['user_id'] = Auth::id();
            
            $download->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Download updated successfully',
                'data' => new DownloadResource($download->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update download failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update download'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $download = Download::where('type', 'download')->find($id);
            if (!$download) return response()->json(['success' => false, 'message' => 'Download not found'], 404);
            $download->delete();
            return response()->json(['success' => true, 'message' => 'Download deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete download failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete download'], 500);
        }
    }
}

