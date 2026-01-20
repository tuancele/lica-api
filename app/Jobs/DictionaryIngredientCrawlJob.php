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

    public function __construct(
        private string $crawlId,
        private int $userId,
        private int $offset,
        private int $batchSize = 100
    ) {
    }

    public function handle(): void
    {
        $key = $this->stateKey($this->crawlId);

        $state = Cache::get($key);
        if (!is_array($state)) {
            $state = [];
        }

        $state['status'] = 'running';
        $state['started_at'] = $state['started_at'] ?? time();
        $state['updated_at'] = time();
        $this->putState($key, $state);

        $listUrl = 'https://www.paulaschoice.com/ingredient-dictionary?start=' . $this->offset . '&sz=2000&ajax=true';
        $payload = $this->curlJson($listUrl);
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
        ]);

        $batchSize = $this->batchSize;
        if ($batchSize <= 0) {
            $batchSize = 100;
        }
        if ($batchSize > 200) {
            $batchSize = 200;
        }

        for ($i = 0; $i < $total; $i++) {
            $it = $ingredients[$i] ?? [];

            try {
                $name = (string) ($it['name'] ?? '');
                $slug = (string) ($it['id'] ?? '');
                $desc = $it['description'] ?? '';
                $url = (string) ($it['url'] ?? '');

                if ($slug === '' || $name === '') {
                    $this->appendLog($key, ($i + 1) . '/' . $total . ' - (skip empty)');
                    continue;
                }

                $existing = IngredientPaulas::where('slug', $slug)->first();
                if ($existing) {
                    $id = (int) $existing->id;
                    $status = 'updated';
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
                }

                if ($id > 0 && $url !== '') {
                    $detailUrl = 'https://www.paulaschoice.com' . $url . '&ajax=true';
                    $this->updateFromRemote($detailUrl, $id);
                }

                $this->appendLog($key, ($i + 1) . '/' . $total . ' - ' . $name . ' - ' . $status);
            } catch (Exception $e) {
                $this->appendLog($key, ($i + 1) . '/' . $total . ' - error: ' . $e->getMessage());
                $this->setError($key, $e->getMessage());
            }

            if ((($i + 1) % $batchSize) === 0 || ($i + 1) === $total) {
                $state = Cache::get($key);
                if (!is_array($state)) {
                    $state = [];
                }
                $state['processed'] = $i + 1;
                $state['updated_at'] = time();
                $this->putState($key, $state);
            }
        }

        $state = Cache::get($key);
        if (!is_array($state)) {
            $state = [];
        }
        $state['processed'] = $total;
        $state['done'] = true;
        $state['status'] = 'done';
        $state['updated_at'] = time();
        $this->putState($key, $state);
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
        Log::error('DictionaryIngredientCrawlJob error', ['key' => $key, 'error' => $message]);
    }

    private function curlJson(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        $content = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode((string) $content, true);
        return is_array($decoded) ? $decoded : [];
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
        $detail = IngredientRate::where('name', $rateName)->first();
        return $detail ? (string) $detail->id : '0';
    }

    private function mapCategories(array $categories): array
    {
        $ids = [];
        foreach ($categories as $value) {
            $name = $value['name'] ?? '';
            if (!is_string($name) || $name === '') {
                continue;
            }
            $detail = IngredientCategory::where('name', $name)->first();
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
            $name = $value['name'] ?? '';
            if (!is_string($name) || $name === '') {
                continue;
            }
            $detail = IngredientBenefit::where('name', $name)->first();
            if ($detail) {
                $ids[] = (string) $detail->id;
            }
        }
        return $ids;
    }

    private function updateFromRemote(string $link, int $id): void
    {
        $rooms = $this->curlJson($link);

        $description = $this->buildDescription($rooms['description'] ?? []);
        $reference = $this->buildReferences($rooms['references'] ?? []);
        $disclaimer = $this->normalizeString($rooms['strings']['disclaimer'] ?? '');
        $glance = $this->buildGlance($rooms['keyPoints'] ?? []);

        $catIds = $this->mapCategories($rooms['relatedCategories'] ?? []);
        $benefitIds = $this->mapBenefits($rooms['benefits'] ?? []);

        IngredientPaulas::where('id', $id)->update([
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
}

