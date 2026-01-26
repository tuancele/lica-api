<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Config\Models\Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 20);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;
            
            $query = Config::query();
            if (isset($filters['keyword'])) $query->where('key', 'like', '%' . $filters['keyword'] . '%');
            $query->orderBy('key', 'asc');
            
            $configs = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $configs->items()->map(function ($config) {
                    return [
                        'id' => $config->id,
                        'key' => $config->key,
                        'value' => $config->value,
                        'group' => $config->group ?? 'general',
                    ];
                }),
                'pagination' => [
                    'current_page' => $configs->currentPage(),
                    'per_page' => $configs->perPage(),
                    'total' => $configs->total(),
                    'last_page' => $configs->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get configs list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get configs list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $config = Config::find($id);
            if (!$config) return response()->json(['success' => false, 'message' => 'Config not found'], 404);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $config->id,
                    'key' => $config->key,
                    'value' => $config->value,
                    'group' => $config->group ?? 'general',
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get config details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get config details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'key' => 'required|string|unique:configs,key',
                'value' => 'nullable',
                'group' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $config = Config::create([
                'key' => $request->key,
                'value' => $request->value ?? '',
                'group' => $request->group ?? 'general',
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Config created successfully',
                'data' => [
                    'id' => $config->id,
                    'key' => $config->key,
                    'value' => $config->value,
                    'group' => $config->group,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create config failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create config'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $config = Config::find($id);
            if (!$config) return response()->json(['success' => false, 'message' => 'Config not found'], 404);
            
            $validator = Validator::make($request->all(), [
                'key' => 'sometimes|required|string|unique:configs,key,' . $id,
                'value' => 'nullable',
                'group' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $updateData = [];
            if ($request->has('key')) $updateData['key'] = $request->key;
            if ($request->has('value')) $updateData['value'] = $request->value;
            if ($request->has('group')) $updateData['group'] = $request->group;
            
            $config->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Config updated successfully',
                'data' => [
                    'id' => $config->id,
                    'key' => $config->key,
                    'value' => $config->value,
                    'group' => $config->group,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update config failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update config'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $config = Config::find($id);
            if (!$config) return response()->json(['success' => false, 'message' => 'Config not found'], 404);
            $config->delete();
            return response()->json(['success' => true, 'message' => 'Config deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete config failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete config'], 500);
        }
    }
}

