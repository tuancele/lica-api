<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Setting\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $settings = Setting::orderBy('key', 'asc')->get()->keyBy('key');
            return response()->json([
                'success' => true,
                'data' => $settings->map(function ($setting) {
                    return [
                        'key' => $setting->key,
                        'value' => $setting->value,
                        'group' => $setting->group ?? 'general',
                    ];
                })->values(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get settings failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get settings'], 500);
        }
    }

    public function show(string $key): JsonResponse
    {
        try {
            $setting = Setting::where('key', $key)->first();
            if (!$setting) return response()->json(['success' => false, 'message' => 'Setting not found'], 404);
            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'group' => $setting->group ?? 'general',
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get setting failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get setting'], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'nullable',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            foreach ($request->settings as $settingData) {
                Setting::updateOrCreate(
                    ['key' => $settingData['key']],
                    ['value' => $settingData['value'] ?? '']
                );
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update settings failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update settings'], 500);
        }
    }

    public function updateSetting(Request $request, string $key): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'value' => 'nullable',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->value ?? '']
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update setting failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update setting'], 500);
        }
    }
}

