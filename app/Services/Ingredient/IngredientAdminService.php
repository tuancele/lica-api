<?php

declare(strict_types=1);
namespace App\Services\Ingredient;

use App\Modules\Dictionary\Models\IngredientBenefit;
use App\Modules\Dictionary\Models\IngredientCategory;
use App\Modules\Dictionary\Models\IngredientPaulas;
use App\Modules\Dictionary\Models\IngredientRate;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IngredientAdminService
{
    private const CACHE_KEY = 'ingredient_paulas_active_list';

    public function __construct(
        private IngredientPaulas $ingredient,
        private IngredientCategory $category,
        private IngredientBenefit $benefit,
        private IngredientRate $rate,
        private Client $client
    ) {
    }

    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->ingredient->newQuery()->orderByDesc('created_at');

        if ($filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['keyword'])) {
            $query->where('name', 'like', '%' . $filters['keyword'] . '%');
        }

        if (!empty($filters['rate_id'])) {
            $query->where('rate_id', $filters['rate_id']);
        }

        if (!empty($filters['cat_id'])) {
            $query->where(function ($q) use ($filters) {
                foreach ((array) $filters['cat_id'] as $catId) {
                    $q->orWhere('cat_id', 'like', '%"' . $catId . '"%');
                }
            });
        }

        if (!empty($filters['benefit_id'])) {
            $query->where(function ($q) use ($filters) {
                foreach ((array) $filters['benefit_id'] as $benefitId) {
                    $q->orWhere('benefit_id', 'like', '%"' . $benefitId . '"%');
                }
            });
        }

        $items = $query->paginate($perPage);

        $items->getCollection()->transform(function (IngredientPaulas $item) {
            return $this->attachRelations($item);
        });

        return $items;
    }

    public function find(int $id): IngredientPaulas
    {
        $item = $this->ingredient->newQuery()->with('rate')->findOrFail($id);
        return $this->attachRelations($item);
    }

    public function create(array $data): IngredientPaulas
    {
        $payload = $this->buildPayload($data);
        $payload['created_at'] = now();
        $payload['user_id'] = Auth::id();

        $id = $this->ingredient->newQuery()->insertGetId($payload);
        $item = $this->ingredient->newQuery()->findOrFail($id);

        $this->clearCache();

        return $this->attachRelations($item);
    }

    public function update(int $id, array $data): IngredientPaulas
    {
        $payload = $this->buildPayload($data);
        $payload['updated_at'] = now();
        $payload['user_id'] = Auth::id();

        $this->ingredient->newQuery()->where('id', $id)->update($payload);
        $item = $this->ingredient->newQuery()->findOrFail($id);

        $this->clearCache();

        return $this->attachRelations($item);
    }

    public function delete(int $id): void
    {
        $this->ingredient->newQuery()->findOrFail($id)->delete();
        $this->clearCache();
    }

    public function updateStatus(int $id, int $status): IngredientPaulas
    {
        $this->ingredient->newQuery()->where('id', $id)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);

        $item = $this->ingredient->newQuery()->findOrFail($id);
        $this->clearCache();

        return $this->attachRelations($item);
    }

    public function bulkAction(array $ids, int $action): void
    {
        if ($action === 0 || $action === 1) {
            $this->ingredient->newQuery()
                ->whereIn('id', $ids)
                ->update([
                    'status' => $action,
                    'updated_at' => now(),
                ]);
        } else {
            $this->ingredient->newQuery()->whereIn('id', $ids)->delete();
        }

        $this->clearCache();
    }

    public function listDictionary(string $type, int $perPage = 40): LengthAwarePaginator
    {
        $model = $this->dictionaryModel($type);
        return $model->newQuery()->orderBy('sort', 'asc')->paginate($perPage);
    }

    public function createDictionary(string $type, array $data)
    {
        $model = $this->dictionaryModel($type);
        $id = $model->newQuery()->insertGetId([
            'name' => $data['name'],
            'status' => $data['status'],
            'sort' => $data['sort'] ?? 0,
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);

        $this->clearCacheIfRate($type);

        return $model->newQuery()->findOrFail($id);
    }

    public function updateDictionary(string $type, int $id, array $data)
    {
        $model = $this->dictionaryModel($type);
        $model->newQuery()->where('id', $id)->update([
            'name' => $data['name'],
            'status' => $data['status'],
            'sort' => $data['sort'] ?? 0,
            'user_id' => Auth::id(),
            'updated_at' => now(),
        ]);

        $this->clearCacheIfRate($type);

        return $model->newQuery()->findOrFail($id);
    }

    public function deleteDictionary(string $type, int $id): void
    {
        $model = $this->dictionaryModel($type);
        $model->newQuery()->findOrFail($id)->delete();
        $this->clearCacheIfRate($type);
    }

    public function updateDictionaryStatus(string $type, int $id, int $status)
    {
        $model = $this->dictionaryModel($type);
        $model->newQuery()->where('id', $id)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);

        $this->clearCacheIfRate($type);

        return $model->newQuery()->findOrFail($id);
    }

    public function bulkDictionary(string $type, array $ids, int $action): void
    {
        $model = $this->dictionaryModel($type);
        if ($action === 0 || $action === 1) {
            $model->newQuery()->whereIn('id', $ids)->update([
                'status' => $action,
                'updated_at' => now(),
            ]);
        } else {
            $model->newQuery()->whereIn('id', $ids)->delete();
        }

        $this->clearCacheIfRate($type);
    }

    public function crawlSummary(): array
    {
        $link = 'https://www.paulaschoice.com/ingredient-dictionary?csortb1=ingredientNotRated&csortd1=1&csortb2=ingredientRating&csortd2=2&csortb3=name&csortd3=1&start=0&sz=1&ajax=true';
        try {
            $response = $this->client->get($link, ['timeout' => 20]);
            $payload = json_decode($response->getBody()->getContents(), true);
            $total = $payload['paging']['total'] ?? 0;
            $page = $total > 0 ? (int) ceil($total / 2000) : 0;

            return [
                'total' => $total,
                'pages' => $page,
            ];
        } catch (ConnectException|RequestException $e) {
            Log::error('Ingredient crawl summary failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function crawlRun(int $offset): array
    {
        @set_time_limit(0);

        $listUrl = "https://www.paulaschoice.com/ingredient-dictionary?start={$offset}&sz=2000&ajax=true";
        $results = [];
        $hasError = false;
        Log::info('Ingredient crawl started', [
            'offset' => $offset,
            'url' => $listUrl,
        ]);
        try {
            $response = $this->client->get($listUrl, ['timeout' => 40]);
            $payload = json_decode($response->getBody()->getContents(), true);
            $ingredients = $payload['ingredients'] ?? [];
            $count = is_array($ingredients) ? count($ingredients) : 0;

            Log::info('Ingredient crawl list fetched', [
                'offset' => $offset,
                'url' => $listUrl,
                'count' => $count,
            ]);

            foreach ($ingredients as $item) {
                try {
                    $status = 'created';
                    $existing = $this->ingredient->newQuery()->where('slug', $item['id'])->first();
                    $detailUrl = 'https://www.paulaschoice.com' . $item['url'] . '&ajax=true';

                    Log::info('Ingredient crawl item processing', [
                        'offset' => $offset,
                        'slug' => $item['id'] ?? null,
                        'name' => $item['name'] ?? null,
                        'existing' => (bool) $existing,
                        'detail_url' => $detailUrl,
                    ]);

                    if ($existing) {
                        // Overwrite existing data with latest from remote
                        $this->updateFromRemote($detailUrl, $existing->id);
                        $status = 'updated';
                    } else {
                        $newId = $this->ingredient->newQuery()->insertGetId([
                            'name' => $item['name'],
                            'slug' => $item['id'],
                            'description' => $item['description'] ?? '',
                            'seo_description' => $item['description'] ?? '',
                            'seo_title' => $item['name'] ?? '',
                            'status' => '1',
                            'user_id' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        $this->updateFromRemote($detailUrl, $newId);
                    }

                    $results[] = "{$item['name']} - {$status}";
                } catch (Exception $e) {
                    $hasError = true;
                    Log::error('Ingredient crawl item failed', [
                        'offset' => $offset,
                        'slug' => $item['id'] ?? null,
                        'name' => $item['name'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    $results[] = "{$item['name']} - error: {$e->getMessage()}";
                }
            }

            $this->clearCache();

            return [
                'status' => $hasError ? 'error' : 'success',
                'message' => implode("\n", $results),
            ];
        } catch (ConnectException|RequestException $e) {
            Log::error('Ingredient crawl timeout or request error', [
                'url' => $listUrl,
                'error' => $e->getMessage(),
            ]);
            return [
                'status' => 'error',
                'message' => 'Crawl timeout or request error: ' . $e->getMessage(),
            ];
        } catch (Exception $e) {
            Log::error('Ingredient crawl failed', [
                'url' => $listUrl,
                'error' => $e->getMessage(),
            ]);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function updateFromRemote(string $link, int $id): void
    {
        $response = $this->client->get($link, ['timeout' => 40]);
        $rooms = json_decode($response->getBody()->getContents(), true);

        $description = $this->buildDescription($rooms['description'] ?? []);
        $reference = $this->buildReferences($rooms['references'] ?? []);
        $disclaimer = $this->normalizeString($rooms['strings']['disclaimer'] ?? '');
        $glance = $this->buildGlance($rooms['keyPoints'] ?? []);

        $catIds = $this->mapCategories($rooms['relatedCategories'] ?? []);
        $benefitIds = $this->mapBenefits($rooms['benefits'] ?? []);

        $this->ingredient->newQuery()->where('id', $id)->update([
            'name' => $rooms['name'] ?? '',
            'rate_id' => $this->mapRate($rooms['rating'] ?? ''),
            'content' => $description,
            'reference' => $reference,
            'disclaimer' => $disclaimer,
            'glance' => $glance,
            'status' => '1',
            'cat_id' => json_encode($catIds),
            'benefit_id' => json_encode($benefitIds),
            'updated_at' => now(),
        ]);
    }

    private function mapCategories(array $categories): array
    {
        $ids = [];
        foreach ($categories as $value) {
            $detail = $this->category->newQuery()->where('name', $value['name'] ?? '')->first();
            if ($detail) {
                $ids[] = (string) $detail->id;
            }
        }
        return $ids;
    }

    private function mapBenefits(array $benefits): array
    {
        $ids = [];
        foreach ($benefits as $value) {
            $detail = $this->benefit->newQuery()->where('name', $value['name'] ?? '')->first();
            if ($detail) {
                $ids[] = (string) $detail->id;
            }
        }
        return $ids;
    }

    private function mapRate(mixed $rate): string
    {
        $rateName = $this->normalizeString($rate);
        if ($rateName === '') {
            return '0';
        }

        $detail = $this->rate->newQuery()->where('name', $rateName)->first();
        return $detail ? (string) $detail->id : '0';
    }

    private function buildDescription(mixed $sections): string
    {
        if (!is_array($sections)) {
            // Normalize string or scalar to expected section structure
            $normalized = $this->normalizeString($sections);
            if ($normalized === '') {
                return '';
            }
            $sections = [
                [
                    'text' => [$normalized],
                ],
            ];
        }

        $content = '';
        foreach ($sections as $value) {
            $texts = $value['text'] ?? [];
            if (is_string($texts)) {
                $content .= '<p>' . $texts . '</p>';
                continue;
            }

            if (is_array($texts) && !empty($texts)) {
                $last = end($texts);
                $normalized = $this->normalizeString($last);
                if ($normalized !== '') {
                    $content .= '<p>' . $normalized . '</p>';
                }
            }
        }

        return $content;
    }

    private function buildReferences(array $references): string
    {
        $content = '';
        foreach ($references as $ref) {
            $text = $this->normalizeString($ref);
            if ($text !== '') {
                $content .= '<p>' . $text . '</p>';
            }
        }

        return $content;
    }

    private function buildGlance(array $points): string
    {
        $items = [];
        foreach ($points as $point) {
            $text = $this->normalizeString($point);
            if ($text !== '') {
                $items[] = $text;
            }
        }

        if (empty($items)) {
            return '';
        }

        return '<ul><li>' . implode('</li><li>', $items) . '</li></ul>';
    }

    private function normalizeString(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_array($value)) {
            $flatten = array_filter(array_map(function ($v) {
                if (is_string($v)) {
                    return $v;
                }
                if (is_scalar($v)) {
                    return (string) $v;
                }
                return '';
            }, $value), fn ($v) => $v !== '');

            return !empty($flatten) ? trim(implode(' ', $flatten)) : '';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private function buildPayload(array $data): array
    {
        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'rate_id' => $data['rate_id'] ?? null,
            'description' => $data['description'] ?? '',
            'content' => $data['content'] ?? '',
            'disclaimer' => $data['disclaimer'] ?? '',
            'reference' => $data['reference'] ?? '',
            'shortcode' => $data['shortcode'] ?? '',
            'glance' => $data['glance'] ?? '',
            'status' => $data['status'],
            'cat_id' => isset($data['cat_id']) ? json_encode($data['cat_id']) : null,
            'benefit_id' => isset($data['benefit_id']) ? json_encode($data['benefit_id']) : null,
            'seo_title' => $data['seo_title'] ?? '',
            'seo_description' => $data['seo_description'] ?? '',
        ];
    }

    private function attachRelations(IngredientPaulas $item): IngredientPaulas
    {
        $catIds = $item->cat_id ?? [];
        $benefitIds = $item->benefit_id ?? [];

        $item->setRelation('rate', $item->rate()->first());

        $item->setRelation('categories', !empty($catIds)
            ? $this->category->newQuery()->whereIn('id', $catIds)->get()
            : collect());

        $item->setRelation('benefits', !empty($benefitIds)
            ? $this->benefit->newQuery()->whereIn('id', $benefitIds)->get()
            : collect());

        return $item;
    }

    private function dictionaryModel(string $type)
    {
        return match ($type) {
            'categories' => $this->category,
            'benefits' => $this->benefit,
            'rates' => $this->rate,
            default => throw new Exception('Invalid dictionary type'),
        };
    }

    private function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function clearCacheIfRate(string $type): void
    {
        if ($type === 'rates') {
            $this->clearCache();
        }
    }
}
