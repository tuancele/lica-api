<?php

declare(strict_types=1);

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Promotion\PromotionResource;
use App\Modules\Promotion\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
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

            $query = Promotion::query();
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['keyword'])) {
                $query->where('name', 'like', '%'.$filters['keyword'].'%');
            }
            $query->latest();

            $promotions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => PromotionResource::collection($promotions->items()),
                'pagination' => [
                    'current_page' => $promotions->currentPage(),
                    'per_page' => $promotions->perPage(),
                    'total' => $promotions->total(),
                    'last_page' => $promotions->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get promotions list failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get promotions list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $promotion = Promotion::with('user')->find($id);
            if (! $promotion) {
                return response()->json(['success' => false, 'message' => 'Promotion not found'], 404);
            }

            return response()->json(['success' => true, 'data' => new PromotionResource($promotion)], 200);
        } catch (\Exception $e) {
            Log::error('Get promotion details failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to get promotion details'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'code' => 'required|string|unique:promotions,code',
                'value' => 'required|numeric|min:0',
                'unit' => 'required|in:percent,amount',
                'number' => 'required|integer|min:0',
                'start' => 'required|date',
                'end' => 'required|date|after:start',
                'status' => 'required|in:0,1',
                'endow' => 'nullable|string',
                'order_sale' => 'nullable|numeric|min:0',
                'payment' => 'nullable|string',
                'content' => 'nullable|string',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $promotion = Promotion::create([
                'code' => $request->code,
                'name' => $request->name,
                'value' => $request->value,
                'unit' => $request->unit,
                'number' => $request->number,
                'start' => $request->start,
                'end' => $request->end,
                'status' => $request->status,
                'endow' => $request->endow,
                'order_sale' => $request->order_sale,
                'payment' => $request->payment,
                'content' => $request->content,
                'sort' => $request->sort ?? 0,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promotion created successfully',
                'data' => new PromotionResource($promotion->load('user')),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create promotion failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to create promotion'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $promotion = Promotion::find($id);
            if (! $promotion) {
                return response()->json(['success' => false, 'message' => 'Promotion not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|min:1|max:250',
                'code' => 'sometimes|required|string|unique:promotions,code,'.$id,
                'value' => 'sometimes|required|numeric|min:0',
                'unit' => 'sometimes|required|in:percent,amount',
                'number' => 'sometimes|required|integer|min:0',
                'start' => 'sometimes|required|date',
                'end' => 'sometimes|required|date|after:start',
                'status' => 'sometimes|in:0,1',
                'endow' => 'nullable|string',
                'order_sale' => 'nullable|numeric|min:0',
                'payment' => 'nullable|string',
                'content' => 'nullable|string',
                'sort' => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $updateData = [];
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('code')) {
                $updateData['code'] = $request->code;
            }
            if ($request->has('value')) {
                $updateData['value'] = $request->value;
            }
            if ($request->has('unit')) {
                $updateData['unit'] = $request->unit;
            }
            if ($request->has('number')) {
                $updateData['number'] = $request->number;
            }
            if ($request->has('start')) {
                $updateData['start'] = $request->start;
            }
            if ($request->has('end')) {
                $updateData['end'] = $request->end;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('endow')) {
                $updateData['endow'] = $request->endow;
            }
            if ($request->has('order_sale')) {
                $updateData['order_sale'] = $request->order_sale;
            }
            if ($request->has('payment')) {
                $updateData['payment'] = $request->payment;
            }
            if ($request->has('content')) {
                $updateData['content'] = $request->content;
            }
            if ($request->has('sort')) {
                $updateData['sort'] = $request->sort;
            }
            $updateData['user_id'] = Auth::id();

            $promotion->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Promotion updated successfully',
                'data' => new PromotionResource($promotion->fresh()->load('user')),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update promotion failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update promotion'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $promotion = Promotion::find($id);
            if (! $promotion) {
                return response()->json(['success' => false, 'message' => 'Promotion not found'], 404);
            }
            $promotion->delete();

            return response()->json(['success' => true, 'message' => 'Promotion deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete promotion failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete promotion'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $promotion = Promotion::find($id);
            if (! $promotion) {
                return response()->json(['success' => false, 'message' => 'Promotion not found'], 404);
            }
            $promotion->update(['status' => $request->status, 'user_id' => Auth::id()]);

            return response()->json(['success' => true, 'message' => 'Promotion status updated successfully', 'data' => new PromotionResource($promotion->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update promotion status failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update promotion status'], 500);
        }
    }

    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:promotions,id',
                'action' => 'required|in:0,1,2',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            try {
                $ids = $request->ids;
                $action = $request->action;
                if ($action == 0) {
                    $affected = Promotion::whereIn('id', $ids)->update(['status' => '0', 'user_id' => Auth::id()]);
                } elseif ($action == 1) {
                    $affected = Promotion::whereIn('id', $ids)->update(['status' => '1', 'user_id' => Auth::id()]);
                } else {
                    $affected = Promotion::whereIn('id', $ids)->delete();
                }
                DB::commit();
                $actionNames = ['hidden', 'shown', 'deleted'];

                return response()->json(['success' => true, 'message' => "Successfully {$actionNames[$action]} {$affected} promotion(s)", 'affected_count' => $affected], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Bulk action failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Bulk action failed'], 500);
        }
    }

    public function updateSort(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sort' => 'required|array',
                'sort.*.id' => 'required|integer|exists:promotions,id',
                'sort.*.sort' => 'required|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            try {
                foreach ($request->sort as $item) {
                    Promotion::where('id', $item['id'])->update(['sort' => $item['sort'], 'user_id' => Auth::id()]);
                }
                DB::commit();

                return response()->json(['success' => true, 'message' => 'Promotion sort order updated successfully'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Update promotion sort failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update promotion sort order'], 500);
        }
    }
}
