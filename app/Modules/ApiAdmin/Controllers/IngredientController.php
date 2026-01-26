<?php

declare(strict_types=1);

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ingredient\DictionaryBulkActionRequest;
use App\Http\Requests\Ingredient\DictionaryItemRequest;
use App\Http\Requests\Ingredient\DictionaryStatusRequest;
use App\Http\Requests\Ingredient\IngredientBulkActionRequest;
use App\Http\Requests\Ingredient\IngredientCrawlRunRequest;
use App\Http\Requests\Ingredient\IngredientStatusRequest;
use App\Http\Requests\Ingredient\StoreIngredientRequest;
use App\Http\Requests\Ingredient\UpdateIngredientRequest;
use App\Http\Resources\Ingredient\DictionaryItemResource;
use App\Http\Resources\Ingredient\IngredientResource;
use App\Services\Ingredient\IngredientAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IngredientController extends Controller
{
    public function __construct(
        private IngredientAdminService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('limit', 20);
            $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

            $filters = [
                'status' => $request->get('status'),
                'keyword' => $request->get('keyword'),
                'rate_id' => $request->get('rate_id'),
                'cat_id' => $request->get('cat_id'),
                'benefit_id' => $request->get('benefit_id'),
            ];

            $items = $this->service->list($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => IngredientResource::collection($items),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('List ingredients failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => '获取成分列表失败',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = $this->service->find($id);

            return response()->json([
                'success' => true,
                'data' => new IngredientResource($item),
            ]);
        } catch (\Exception $e) {
            Log::error('Show ingredient failed', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json([
                'success' => false,
                'message' => '成分不存在',
            ], 404);
        }
    }

    public function store(StoreIngredientRequest $request): JsonResponse
    {
        try {
            $item = $this->service->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => '创建成分成功',
                'data' => new IngredientResource($item),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create ingredient failed', [
                'error' => $e->getMessage(),
                'payload' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '创建成分失败',
            ], 500);
        }
    }

    public function update(UpdateIngredientRequest $request, int $id): JsonResponse
    {
        try {
            $item = $this->service->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => '更新成分成功',
                'data' => new IngredientResource($item),
            ]);
        } catch (\Exception $e) {
            Log::error('Update ingredient failed', [
                'error' => $e->getMessage(),
                'id' => $id,
                'payload' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '更新成分失败',
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json([
                'success' => true,
                'message' => '删除成分成功',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete ingredient failed', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json([
                'success' => false,
                'message' => '删除成分失败',
            ], 500);
        }
    }

    public function updateStatus(IngredientStatusRequest $request, int $id): JsonResponse
    {
        try {
            $item = $this->service->updateStatus($id, (int) $request->status);

            return response()->json([
                'success' => true,
                'message' => '状态更新成功',
                'data' => new IngredientResource($item),
            ]);
        } catch (\Exception $e) {
            Log::error('Update ingredient status failed', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json([
                'success' => false,
                'message' => '状态更新失败',
            ], 500);
        }
    }

    public function bulkAction(IngredientBulkActionRequest $request): JsonResponse
    {
        try {
            $this->service->bulkAction($request->checklist, (int) $request->action);

            return response()->json([
                'success' => true,
                'message' => '批量操作成功',
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk ingredient failed', [
                'error' => $e->getMessage(),
                'ids' => $request->checklist,
                'action' => $request->action,
            ]);

            return response()->json([
                'success' => false,
                'message' => '批量操作失败',
            ], 500);
        }
    }

    public function crawlSummary(): JsonResponse
    {
        try {
            $data = $this->service->crawlSummary();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取 crawl summary 失败: '.$e->getMessage(),
            ], 500);
        }
    }

    public function crawlRun(IngredientCrawlRunRequest $request): JsonResponse
    {
        $result = $this->service->crawlRun((int) $request->offset);
        $statusCode = $result['status'] === 'success' ? 200 : 500;

        return response()->json([
            'success' => $result['status'] === 'success',
            'message' => $result['message'],
        ], $statusCode);
    }

    public function listCategories(Request $request): JsonResponse
    {
        return $this->dictionaryList('categories', $request);
    }

    public function storeCategory(DictionaryItemRequest $request): JsonResponse
    {
        return $this->dictionaryStore('categories', $request);
    }

    public function updateCategory(DictionaryItemRequest $request, int $id): JsonResponse
    {
        return $this->dictionaryUpdate('categories', $id, $request);
    }

    public function deleteCategory(int $id): JsonResponse
    {
        return $this->dictionaryDelete('categories', $id);
    }

    public function statusCategory(DictionaryStatusRequest $request, int $id): JsonResponse
    {
        return $this->dictionaryStatus('categories', $id, $request);
    }

    public function bulkCategory(DictionaryBulkActionRequest $request): JsonResponse
    {
        return $this->dictionaryBulk('categories', $request);
    }

    public function listBenefits(Request $request): JsonResponse
    {
        return $this->dictionaryList('benefits', $request);
    }

    public function storeBenefit(DictionaryItemRequest $request): JsonResponse
    {
        return $this->dictionaryStore('benefits', $request);
    }

    public function updateBenefit(DictionaryItemRequest $request, int $id): JsonResponse
    {
        return $this->dictionaryUpdate('benefits', $id, $request);
    }

    public function deleteBenefit(int $id): JsonResponse
    {
        return $this->dictionaryDelete('benefits', $id);
    }

    public function statusBenefit(DictionaryStatusRequest $request, int $id): JsonResponse
    {
        return $this->dictionaryStatus('benefits', $id, $request);
    }

    public function bulkBenefit(DictionaryBulkActionRequest $request): JsonResponse
    {
        return $this->dictionaryBulk('benefits', $request);
    }

    public function listRates(Request $request): JsonResponse
    {
        return $this->dictionaryList('rates', $request);
    }

    public function storeRate(DictionaryItemRequest $request): JsonResponse
    {
        return $this->dictionaryStore('rates', $request);
    }

    public function updateRate(DictionaryItemRequest $request, int $id): JsonResponse
    {
        return $this->dictionaryUpdate('rates', $id, $request);
    }

    public function deleteRate(int $id): JsonResponse
    {
        return $this->dictionaryDelete('rates', $id);
    }

    public function statusRate(DictionaryStatusRequest $request, int $id): JsonResponse
    {
        return $this->dictionaryStatus('rates', $id, $request);
    }

    public function bulkRate(DictionaryBulkActionRequest $request): JsonResponse
    {
        return $this->dictionaryBulk('rates', $request);
    }

    private function dictionaryList(string $type, Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('limit', 40);
            $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 40;
            $items = $this->service->listDictionary($type, $perPage);

            return response()->json([
                'success' => true,
                'data' => DictionaryItemResource::collection($items),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("List {$type} failed", ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => '获取列表失败',
            ], 500);
        }
    }

    private function dictionaryStore(string $type, DictionaryItemRequest $request): JsonResponse
    {
        try {
            $item = $this->service->createDictionary($type, $request->validated());

            return response()->json([
                'success' => true,
                'message' => '创建成功',
                'data' => new DictionaryItemResource($item),
            ], 201);
        } catch (\Exception $e) {
            Log::error("Create {$type} failed", ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => '创建失败',
            ], 500);
        }
    }

    private function dictionaryUpdate(string $type, int $id, DictionaryItemRequest $request): JsonResponse
    {
        try {
            $item = $this->service->updateDictionary($type, $id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => '更新成功',
                'data' => new DictionaryItemResource($item),
            ]);
        } catch (\Exception $e) {
            Log::error("Update {$type} failed", ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => '更新失败',
            ], 500);
        }
    }

    private function dictionaryDelete(string $type, int $id): JsonResponse
    {
        try {
            $this->service->deleteDictionary($type, $id);

            return response()->json([
                'success' => true,
                'message' => '删除成功',
            ]);
        } catch (\Exception $e) {
            Log::error("Delete {$type} failed", ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json([
                'success' => false,
                'message' => '删除失败',
            ], 500);
        }
    }

    private function dictionaryStatus(string $type, int $id, DictionaryStatusRequest $request): JsonResponse
    {
        try {
            $item = $this->service->updateDictionaryStatus($type, $id, (int) $request->status);

            return response()->json([
                'success' => true,
                'message' => '状态更新成功',
                'data' => new DictionaryItemResource($item),
            ]);
        } catch (\Exception $e) {
            Log::error("Update {$type} status failed", ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json([
                'success' => false,
                'message' => '状态更新失败',
            ], 500);
        }
    }

    private function dictionaryBulk(string $type, DictionaryBulkActionRequest $request): JsonResponse
    {
        try {
            $this->service->bulkDictionary($type, $request->checklist, (int) $request->action);

            return response()->json([
                'success' => true,
                'message' => '批量操作成功',
            ]);
        } catch (\Exception $e) {
            Log::error("Bulk {$type} failed", [
                'error' => $e->getMessage(),
                'ids' => $request->checklist,
                'action' => $request->action,
            ]);

            return response()->json([
                'success' => false,
                'message' => '批量操作失败',
            ], 500);
        }
    }
}
