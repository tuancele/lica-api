<?php

namespace App\Jobs;

use App\Modules\Dictionary\Models\IngredientBenefit;
use App\Modules\Dictionary\Models\IngredientCategory;
use App\Modules\Dictionary\Models\IngredientPaulas;
use App\Modules\Dictionary\Models\IngredientRate;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DictionaryIngredientCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private static ?array $rateMap = null;
    private static ?array $categoryMap = null;
    private static ?array $benefitMap = null;

    public function __construct(
        private string $crawlId,
        private int $userId,
        private int $offset,
        private int $batchSize = 100
    ) {
    }

    private function loadMappingMaps(): void
    {
        if (self::$rateMap === null) {
            self::$rateMap = [];
            $rates = IngredientRate::select('id', 'name')->get();
            foreach ($rates as $rate) {
                $normalized = $this->normalizeForMapping($rate->name);
                self::$rateMap[$normalized] = (string) $rate->id;
                // Also add original name for exact match
                self::$rateMap[strtolower(trim($rate->name))] = (string) $rate->id;
            }
            Log::debug('DictionaryIngredientCrawlJob rate map loaded', [
                'crawl_id' => $this->crawlId,
                'count' => count(self::$rateMap),
            ]);
        }

        if (self::$categoryMap === null) {
            self::$categoryMap = [];
            $categories = IngredientCategory::select('id', 'name')->get();
            foreach ($categories as $cat) {
                $normalized = $this->normalizeForMapping($cat->name);
                self::$categoryMap[$normalized] = (string) $cat->id;
                // Also add original name for exact match
                self::$categoryMap[strtolower(trim($cat->name))] = (string) $cat->id;
            }
            Log::debug('DictionaryIngredientCrawlJob category map loaded', [
                'crawl_id' => $this->crawlId,
                'count' => count(self::$categoryMap),
            ]);
        }

        if (self::$benefitMap === null) {
            self::$benefitMap = [];
            $benefits = IngredientBenefit::select('id', 'name')->get();
            foreach ($benefits as $benefit) {
                $normalized = $this->normalizeForMapping($benefit->name);
                self::$benefitMap[$normalized] = (string) $benefit->id;
                // Also add original name for exact match
                self::$benefitMap[strtolower(trim($benefit->name))] = (string) $benefit->id;
            }
            Log::debug('DictionaryIngredientCrawlJob benefit map loaded', [
                'crawl_id' => $this->crawlId,
                'count' => count(self::$benefitMap),
            ]);
        }
    }

    private function normalizeForMapping(string $value): string
    {
        // Normalize for better matching: lowercase, trim, remove extra spaces
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return $normalized;
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $key = $this->stateKey($this->crawlId);

        Log::info('DictionaryIngredientCrawlJob started', [
            'crawl_id' => $this->crawlId,
            'user_id' => $this->userId,
            'offset' => $this->offset,
            'batch_size' => $this->batchSize,
        ]);

        // Load mapping maps into memory for performance
        $this->loadMappingMaps();

        $state = Cache::get($key);
        if (!is_array($state)) {
            $state = [];
        }

        $state['status'] = 'running';
        $state['started_at'] = $state['started_at'] ?? time();
        $state['updated_at'] = time();
        $this->putState($key, $state);

        $listUrl = 'https://www.paulaschoice.com/ingredient-dictionary?start=' . $this->offset . '&sz=2000&ajax=true';
        
        Log::info('DictionaryIngredientCrawlJob fetching ingredient list', [
            'crawl_id' => $this->crawlId,
            'url' => $listUrl,
        ]);

        $listStartTime = microtime(true);
        $payload = $this->curlJson($listUrl);
        $listFetchTime = round((microtime(true) - $listStartTime) * 1000, 2);
        
        $ingredients = $payload['ingredients'] ?? [];
        if (!is_array($ingredients)) {
            $ingredients = [];
        }

        $total = count($ingredients);
        $state['total'] = $total;
        $state['processed'] = 0;
        $state['done'] = false;
        $state['error'] = null;
        $state['updated_at'] = time();
        $this->putState($key, $state);

        Log::info('DictionaryIngredientCrawlJob list fetched', [
            'crawl_id' => $this->crawlId,
            'offset' => $this->offset,
            'total' => $total,
            'fetch_time_ms' => $listFetchTime,
            'has_paging' => isset($payload['paging']),
        ]);

        $batchSize = $this->batchSize;
        if ($batchSize <= 0) {
            $batchSize = 100;
        }
        if ($batchSize > 200) {
            $batchSize = 200;
        }

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'detail_fetched' => 0,
            'detail_failed' => 0,
        ];

        for ($i = 0; $i < $total; $i++) {
            $it = $ingredients[$i] ?? [];
            $itemStartTime = microtime(true);

            try {
                $name = (string) ($it['name'] ?? '');
                $slug = (string) ($it['id'] ?? '');
                $desc = $it['description'] ?? '';
                $url = (string) ($it['url'] ?? '');

                if ($slug === '' || $name === '') {
                    $stats['skipped']++;
                    $this->appendLog($key, ($i + 1) . '/' . $total . ' - (skip empty)');
                    Log::warning('DictionaryIngredientCrawlJob skipped empty ingredient', [
                        'crawl_id' => $this->crawlId,
                        'index' => $i + 1,
                        'name' => $name,
                        'slug' => $slug,
                    ]);
                    continue;
                }

                $existing = IngredientPaulas::where('slug', $slug)->first();
                if ($existing) {
                    $id = (int) $existing->id;
                    $status = 'updated';
                    $stats['updated']++;
                    Log::debug('DictionaryIngredientCrawlJob ingredient found (update)', [
                        'crawl_id' => $this->crawlId,
                        'ingredient_id' => $id,
                        'slug' => $slug,
                        'name' => $name,
                    ]);
                } else {
                    $id = (int) IngredientPaulas::insertGetId([
                        'name' => $name,
                        'slug' => $slug,
                        'description' => is_string($desc) ? $desc : $this->normalizeString($desc),
                        'status' => '1',
                        'seo_description' => is_string($desc) ? $desc : $this->normalizeString($desc),
                        'seo_title' => $name,
                        'user_id' => $this->userId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $status = 'created';
                    $stats['created']++;
                    Log::info('DictionaryIngredientCrawlJob ingredient created', [
                        'crawl_id' => $this->crawlId,
                        'ingredient_id' => $id,
                        'slug' => $slug,
                        'name' => $name,
                    ]);
                }

                if ($id > 0 && $url !== '') {
                    $detailUrl = 'https://www.paulaschoice.com' . $url . '&ajax=true';
                    try {
                        $detailStartTime = microtime(true);
                        $this->updateFromRemote($detailUrl, $id);
                        $detailTime = round((microtime(true) - $detailStartTime) * 1000, 2);
                        $stats['detail_fetched']++;
                        Log::debug('DictionaryIngredientCrawlJob detail fetched', [
                            'crawl_id' => $this->crawlId,
                            'ingredient_id' => $id,
                            'url' => $detailUrl,
                            'fetch_time_ms' => $detailTime,
                        ]);
                    } catch (Exception $e) {
                        $stats['detail_failed']++;
                        Log::error('DictionaryIngredientCrawlJob detail fetch failed', [
                            'crawl_id' => $this->crawlId,
                            'ingredient_id' => $id,
                            'url' => $detailUrl,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }

                $itemTime = round((microtime(true) - $itemStartTime) * 1000, 2);
                $this->appendLog($key, ($i + 1) . '/' . $total . ' - ' . $name . ' - ' . $status);
                
                Log::debug('DictionaryIngredientCrawlJob ingredient processed', [
                    'crawl_id' => $this->crawlId,
                    'index' => $i + 1,
                    'total' => $total,
                    'ingredient_id' => $id,
                    'status' => $status,
                    'process_time_ms' => $itemTime,
                ]);
            } catch (Exception $e) {
                $stats['failed']++;
                $this->appendLog($key, ($i + 1) . '/' . $total . ' - error: ' . $e->getMessage());
                $this->setError($key, $e->getMessage());
                Log::error('DictionaryIngredientCrawlJob ingredient processing failed', [
                    'crawl_id' => $this->crawlId,
                    'index' => $i + 1,
                    'total' => $total,
                    'name' => $it['name'] ?? 'unknown',
                    'slug' => $it['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            if ((($i + 1) % $batchSize) === 0 || ($i + 1) === $total) {
                $state = Cache::get($key);
                if (!is_array($state)) {
                    $state = [];
                }
                $state['processed'] = $i + 1;
                $state['updated_at'] = time();
                $this->putState($key, $state);
                
                Log::info('DictionaryIngredientCrawlJob batch progress', [
                    'crawl_id' => $this->crawlId,
                    'processed' => $i + 1,
                    'total' => $total,
                    'progress_percent' => round(($i + 1) / $total * 100, 2),
                ]);
            }
        }

        $totalTime = round((microtime(true) - $startTime), 2);
        $state = Cache::get($key);
        if (!is_array($state)) {
            $state = [];
        }
        $state['processed'] = $total;
        $state['done'] = true;
        $state['status'] = 'done';
        $state['updated_at'] = time();
        $state['stats'] = $stats;
        $state['total_time_seconds'] = $totalTime;
        $this->putState($key, $state);

        Log::info('DictionaryIngredientCrawlJob completed', [
            'crawl_id' => $this->crawlId,
            'user_id' => $this->userId,
            'offset' => $this->offset,
            'total' => $total,
            'stats' => $stats,
            'total_time_seconds' => $totalTime,
            'avg_time_per_item_ms' => $total > 0 ? round(($totalTime * 1000) / $total, 2) : 0,
        ]);
    }

    private function stateKey(string $crawlId): string
    {
        return 'dictionary_ingredient_crawl_job:' . $crawlId;
    }

    private function putState(string $key, array $state): void
    {
        $state['logs'] = $state['logs'] ?? [];
        if (!is_array($state['logs'])) {
            $state['logs'] = [];
        }
        Cache::put($key, $state, now()->addHours(6));
    }

    private function appendLog(string $key, string $line): void
    {
        $state = Cache::get($key);
        if (!is_array($state)) {
            $state = [];
        }
        $logs = $state['logs'] ?? [];
        if (!is_array($logs)) {
            $logs = [];
        }
        $logs[] = $line;
        if (count($logs) > 2000) {
            $logs = array_slice($logs, -2000);
        }
        $state['logs'] = $logs;
        $state['updated_at'] = time();
        $this->putState($key, $state);
    }

    private function setError(string $key, string $message): void
    {
        $state = Cache::get($key);
        if (!is_array($state)) {
            $state = [];
        }
        $state['error'] = $message;
        $state['status'] = 'error';
        $state['updated_at'] = time();
        $this->putState($key, $state);
        Log::error('DictionaryIngredientCrawlJob error', [
            'crawl_id' => $this->crawlId,
            'user_id' => $this->userId,
            'offset' => $this->offset,
            'key' => $key,
            'error' => $message,
        ]);
    }

    private function curlJson(string $url, int $retryCount = 0, int $maxRetries = 3): array
    {
        $startTime = microtime(true);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $fetchTime = round((microtime(true) - $startTime) * 1000, 2);
        $contentLength = strlen($content ?? '');

        if ($error || ($httpCode !== 200 && $httpCode !== 0)) {
            if ($retryCount < $maxRetries) {
                $waitTime = pow(2, $retryCount); // Exponential backoff: 1s, 2s, 4s
                Log::warning('DictionaryIngredientCrawlJob curl retry', [
                    'crawl_id' => $this->crawlId,
                    'url' => $url,
                    'error' => $error,
                    'http_code' => $httpCode,
                    'retry_count' => $retryCount + 1,
                    'max_retries' => $maxRetries,
                    'wait_time' => $waitTime,
                ]);
                sleep($waitTime);
                return $this->curlJson($url, $retryCount + 1, $maxRetries);
            }

            Log::error('DictionaryIngredientCrawlJob curl error after retries', [
                'crawl_id' => $this->crawlId,
                'url' => $url,
                'error' => $error,
                'http_code' => $httpCode,
                'fetch_time_ms' => $fetchTime,
                'retry_count' => $retryCount,
            ]);
            return [];
        }

        $decoded = json_decode((string) $content, true);
        if (!is_array($decoded)) {
            Log::warning('DictionaryIngredientCrawlJob invalid JSON response', [
                'crawl_id' => $this->crawlId,
                'url' => $url,
                'http_code' => $httpCode,
                'content_length' => $contentLength,
                'content_preview' => substr($content ?? '', 0, 200),
            ]);
            return [];
        }

        Log::debug('DictionaryIngredientCrawlJob curl success', [
            'crawl_id' => $this->crawlId,
            'url' => $url,
            'http_code' => $httpCode,
            'content_length' => $contentLength,
            'fetch_time_ms' => $fetchTime,
        ]);

        return $decoded;
    }

    private function normalizeString(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $v) {
                if (is_string($v)) {
                    $parts[] = $v;
                } elseif (is_scalar($v)) {
                    $parts[] = (string) $v;
                }
            }
            return trim(implode(' ', $parts));
        }
        if (is_scalar($value)) {
            return trim((string) $value);
        }
        return '';
    }

    private function buildDescription(mixed $sections): string
    {
        if (!is_array($sections)) {
            $t = $this->normalizeString($sections);
            return $t !== '' ? '<p>' . $t . '</p>' : '';
        }
        $content = '';
        foreach ($sections as $value) {
            $texts = $value['text'] ?? [];
            if (is_string($texts)) {
                $t = $this->normalizeString($texts);
                if ($t !== '') {
                    $content .= '<p>' . $t . '</p>';
                }
                continue;
            }
            if (is_array($texts) && !empty($texts)) {
                $last = end($texts);
                $t = $this->normalizeString($last);
                if ($t !== '') {
                    $content .= '<p>' . $t . '</p>';
                }
            }
        }
        return $content;
    }

    private function buildReferences(mixed $references): string
    {
        if (!is_array($references)) {
            $t = $this->normalizeString($references);
            return $t !== '' ? '<p>' . $t . '</p>' : '';
        }
        $content = '';
        foreach ($references as $ref) {
            $t = $this->normalizeString($ref);
            if ($t !== '') {
                $content .= '<p>' . $t . '</p>';
            }
        }
        return $content;
    }

    private function buildGlance(mixed $points): string
    {
        if (!is_array($points)) {
            $t = $this->normalizeString($points);
            return $t !== '' ? '<ul><li>' . $t . '</li></ul>' : '';
        }
        $items = [];
        foreach ($points as $p) {
            $t = $this->normalizeString($p);
            if ($t !== '') {
                $items[] = $t;
            }
        }
        if (empty($items)) {
            return '';
        }
        return '<ul><li>' . implode('</li><li>', $items) . '</li></ul>';
    }

    private function mapRate(mixed $rate): string
    {
        $rateName = $this->normalizeString($rate);
        if ($rateName === '') {
            return '0';
        }

        // Try exact match first
        $normalized = $this->normalizeForMapping($rateName);
        if (isset(self::$rateMap[$normalized])) {
            return self::$rateMap[$normalized];
        }

        // Try case-insensitive match
        $lower = strtolower(trim($rateName));
        if (isset(self::$rateMap[$lower])) {
            return self::$rateMap[$lower];
        }

        // Try partial match (for cases like "Best Rated" vs "Best")
        foreach (self::$rateMap as $key => $id) {
            if (stripos($key, $normalized) !== false || stripos($normalized, $key) !== false) {
                return $id;
            }
        }

        return '0';
    }

    private function mapCategories(array $categories): array
    {
        $ids = [];
        $notFound = [];
        foreach ($categories as $value) {
            $name = $value['name'] ?? '';
            if (!is_string($name) || $name === '') {
                continue;
            }

            // Try exact match first
            $normalized = $this->normalizeForMapping($name);
            if (isset(self::$categoryMap[$normalized])) {
                $ids[] = self::$categoryMap[$normalized];
                continue;
            }

            // Try case-insensitive match
            $lower = strtolower(trim($name));
            if (isset(self::$categoryMap[$lower])) {
                $ids[] = self::$categoryMap[$lower];
                continue;
            }

            // Try partial match
            $found = false;
            foreach (self::$categoryMap as $key => $id) {
                if (stripos($key, $normalized) !== false || stripos($normalized, $key) !== false) {
                    $ids[] = $id;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $notFound[] = $name;
            }
        }

        if (!empty($notFound) && count($ids) === 0 && count($categories) > 0) {
            Log::debug('DictionaryIngredientCrawlJob mapCategories not found', [
                'crawl_id' => $this->crawlId,
                'category_names' => $notFound,
                'total_categories' => count($categories),
            ]);
        }

        return array_unique($ids);
    }

    private function mapBenefits(array $benefits): array
    {
        $ids = [];
        $notFound = [];
        foreach ($benefits as $value) {
            $name = $value['name'] ?? '';
            if (!is_string($name) || $name === '') {
                continue;
            }

            // Try exact match first
            $normalized = $this->normalizeForMapping($name);
            if (isset(self::$benefitMap[$normalized])) {
                $ids[] = self::$benefitMap[$normalized];
                continue;
            }

            // Try case-insensitive match
            $lower = strtolower(trim($name));
            if (isset(self::$benefitMap[$lower])) {
                $ids[] = self::$benefitMap[$lower];
                continue;
            }

            // Try partial match
            $found = false;
            foreach (self::$benefitMap as $key => $id) {
                if (stripos($key, $normalized) !== false || stripos($normalized, $key) !== false) {
                    $ids[] = $id;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $notFound[] = $name;
            }
        }

        if (!empty($notFound) && count($ids) === 0 && count($benefits) > 0) {
            Log::debug('DictionaryIngredientCrawlJob mapBenefits not found', [
                'crawl_id' => $this->crawlId,
                'benefit_names' => $notFound,
                'total_benefits' => count($benefits),
            ]);
        }

        return array_unique($ids);
    }

    private function updateFromRemote(string $link, int $id): void
    {
        $startTime = microtime(true);
        
        $rooms = $this->curlJson($link);
        
        if (empty($rooms)) {
            Log::warning('DictionaryIngredientCrawlJob updateFromRemote empty response', [
                'crawl_id' => $this->crawlId,
                'ingredient_id' => $id,
                'url' => $link,
            ]);
            return;
        }

        // Log raw data for debugging mapping issues
        $rawRating = $rooms['rating'] ?? null;
        $rawCategories = $rooms['relatedCategories'] ?? [];
        $rawBenefits = $rooms['benefits'] ?? [];

        $description = $this->buildDescription($rooms['description'] ?? []);
        $reference = $this->buildReferences($rooms['references'] ?? []);
        $disclaimer = $this->normalizeString($rooms['strings']['disclaimer'] ?? '');
        $glance = $this->buildGlance($rooms['keyPoints'] ?? []);

        $catIds = $this->mapCategories($rawCategories);
        $benefitIds = $this->mapBenefits($rawBenefits);
        $rateId = $this->mapRate($rawRating);

        // Log mapping details for first few items or when mapping fails
        $shouldLogMapping = ($id % 50 === 0) || (count($catIds) === 0 && !empty($rawCategories)) || (count($benefitIds) === 0 && !empty($rawBenefits)) || ($rateId === '0' && !empty($rawRating));
        
        if ($shouldLogMapping) {
            Log::info('DictionaryIngredientCrawlJob updateFromRemote mapping details', [
                'crawl_id' => $this->crawlId,
                'ingredient_id' => $id,
                'name' => $rooms['name'] ?? '',
                'raw_rating' => is_scalar($rawRating) ? (string) $rawRating : (is_array($rawRating) ? json_encode($rawRating) : 'null'),
                'mapped_rate_id' => $rateId,
                'raw_categories' => array_column($rawCategories, 'name'),
                'mapped_category_ids' => $catIds,
                'raw_benefits' => array_column($rawBenefits, 'name'),
                'mapped_benefit_ids' => $benefitIds,
            ]);
        }

        $updateData = [
            'name' => $rooms['name'] ?? '',
            'rate_id' => $rateId,
            'content' => $description,
            'reference' => $reference,
            'disclaimer' => $disclaimer,
            'glance' => $glance,
            'status' => '1',
            'cat_id' => json_encode($catIds),
            'benefit_id' => json_encode($benefitIds),
            'updated_at' => now(),
        ];

        IngredientPaulas::where('id', $id)->update($updateData);

        $processTime = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::debug('DictionaryIngredientCrawlJob updateFromRemote completed', [
            'crawl_id' => $this->crawlId,
            'ingredient_id' => $id,
            'name' => $updateData['name'],
            'rate_id' => $rateId,
            'categories_count' => count($catIds),
            'benefits_count' => count($benefitIds),
            'has_content' => !empty($description),
            'has_reference' => !empty($reference),
            'has_glance' => !empty($glance),
            'process_time_ms' => $processTime,
        ]);
    }
}

