<?php
namespace App\Modules\Dictionary\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Dictionary\Models\IngredientPaulas;
use App\Modules\Dictionary\Models\IngredientCategory;
use App\Modules\Dictionary\Models\IngredientBenefit;
use App\Modules\Dictionary\Models\IngredientRate;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\DictionaryIngredientCrawlJob;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;
use Validator;
use Exception;
use Illuminate\Http\JsonResponse;

class IngredientController extends Controller
{
    private $model;
    private $controller = 'ingredient';
    private $view = 'Dictionary';
    public function __construct(IngredientPaulas $model){
        $this->model = $model;
    }
    public function index(Request $request)
    {
        active('dictionary','ingredient');
        $data['posts'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
	            $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
	            $query->where('name','like','%'.$request->get('keyword').'%');
	        }
        })->orderBy('created_at','desc')->paginate(20)->appends(['keyword' => $request->get('keyword'),'status' => $request->get('status')]);
        return view($this->view.'::ingredient.index',$data);
    }
    public function create(){
        //$this->authorize('post-create');
        active('dictionary','ingredient');
        $data['categories'] = IngredientCategory::where('status','1')->orderBy('sort','asc')->get();
        $data['rates'] = IngredientRate::where('status','1')->orderBy('sort','asc')->get();
        $data['benefits'] = IngredientBenefit::where('status','1')->orderBy('sort','asc')->get();
        return view($this->view.'::ingredient.create',$data);
    }
    public function edit($id){
        active('dictionary','ingredient');
        $post = $this->model::find($id);
        //$this->authorize($post,'post-edit');
        if(!isset($post) && empty($post)){
            return redirect()->route('post');
        }
        $data['detail'] = $post;
        $data['categories'] = IngredientCategory::where('status','1')->orderBy('sort','asc')->get();
        $data['rates'] = IngredientRate::where('status','1')->orderBy('sort','asc')->get();
        $data['benefits'] = IngredientBenefit::where('status','1')->orderBy('sort','asc')->get();
        $data['dcat'] = is_array($post->cat_id) ? $post->cat_id : json_decode($post->cat_id ?? '[]', true);
        $data['dben'] = is_array($post->benefit_id) ? $post->benefit_id : json_decode($post->benefit_id ?? '[]', true);
        $data['products'] = Product::select('id','name','image')->where([['status','1'],['type','product'],['ingredient','like','%'.$post->name.'%']])->get(); 
        return view($this->view.'::ingredient.edit',$data);
    }

    public function crawl(){
        active('dictionary','ingredient');
        
        $link = "https://www.paulaschoice.com/ingredient-dictionary?csortb1=ingredientNotRated&csortd1=1&csortb2=ingredientRating&csortd2=2&csortb3=name&csortd3=1&start=0&sz=1&ajax=true";
        
        Log::info('DictionaryIngredientCrawlJob crawl page accessed', [
            'user_id' => Auth::id(),
            'url' => $link,
        ]);

        $startTime = microtime(true);
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $fetchTime = round((microtime(true) - $startTime) * 1000, 2);

        if ($error) {
            Log::error('DictionaryIngredientCrawlJob crawl page fetch error', [
                'url' => $link,
                'error' => $error,
                'http_code' => $httpCode,
                'fetch_time_ms' => $fetchTime,
            ]);
            $data['page'] = 0;
            $data['total'] = 0;
            return view($this->view.'::ingredient.crawl',$data);
        }

        $api = json_decode($content, true);
        
        if (!is_array($api) || !isset($api['paging']['total'])) {
            Log::warning('DictionaryIngredientCrawlJob crawl page invalid response', [
                'url' => $link,
                'http_code' => $httpCode,
                'content_preview' => substr($content ?? '', 0, 200),
            ]);
            $data['page'] = 0;
            $data['total'] = 0;
            return view($this->view.'::ingredient.crawl',$data);
        }

        $total = $api['paging']['total'];
        $page = ceil($total/2000);
        $data['page'] = $page;
        $data['total'] = $total;

        Log::info('DictionaryIngredientCrawlJob crawl page loaded', [
            'user_id' => Auth::id(),
            'total' => $total,
            'page' => $page,
            'fetch_time_ms' => $fetchTime,
        ]);

        return view($this->view.'::ingredient.crawl',$data); 
    }

    public function getData(Request $request){
        try{
            $i = $request->offset;
            $rerult = '';
            $link = "https://www.paulaschoice.com/ingredient-dictionary?start=".$i."&sz=2000&ajax=true";
            $ch = curl_init($link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
            curl_close($ch);
            $api = json_decode($content, true);
            $pages = $api['paging'];
            $ingredients = $api['ingredients'];
            $status = "";
            $j = 0;
            if(isset($ingredients) && !empty($ingredients)){
                foreach ($ingredients as $key => $value) {
                    $check = $this->model::where('slug',$value['id'])->first();
                    if(isset($check) && !empty($check)){
                        $url = 'https://www.paulaschoice.com'.$value['url'].'&ajax=true';
                        $this->detail($url,$check->id);
                        $status = "<span>Cập nhật thành công</span>";
                    }else{
                        $id = $this->model::insertGetId(
                            [
                                'name' => $value['name'],
                                'slug' => $value['id'],
                                'description' => $value['description'],
                                'status' => '1',
                                'seo_description' => $value['description'],
                                'seo_title' => $value['name'],
                                'user_id'=> Auth::id(),
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                        if($id > 0){
                            $url = 'https://www.paulaschoice.com'.$value['url'].'&ajax=true';
                            $this->detail($url,$id);
                        }
                        $status = "<span>Thêm thành công</span>";
                    } 
                    $j++;
                    $rerult .= '<p>'.$j.' - Thành phần: '.$value['name']. ' - '.$status.'</p>';
                }  
            }
            return response()->json([
                'status' => 'success',
                'message' => $rerult
            ]);
        }catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function crawlStateKey(int $userId, int $offset): string
    {
        return 'dictionary_ingredient_crawl:' . $userId . ':' . $offset;
    }

    public function crawlStart(Request $request): JsonResponse
    {
        try {
            $offset = (int) ($request->offset ?? 0);
            if ($offset < 0) {
                Log::warning('DictionaryIngredientCrawlJob invalid offset', [
                    'offset' => $offset,
                    'user_id' => Auth::id(),
                ]);
                return response()->json(['success' => false, 'message' => 'Invalid offset'], 422);
            }

            $userId = (int) (Auth::id() ?? 0);
            if ($userId <= 0) {
                Log::warning('DictionaryIngredientCrawlJob unauthenticated request', [
                    'offset' => $offset,
                ]);
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $crawlId = (string) Str::uuid();
            $key = 'dictionary_ingredient_crawl_job:' . $crawlId;

            Cache::put($key, [
                'crawl_id' => $crawlId,
                'user_id' => $userId,
                'offset' => $offset,
                'status' => 'queued',
                'total' => null,
                'processed' => 0,
                'done' => false,
                'error' => null,
                'logs' => ['[INFO] Crawl job queued. Waiting for worker to start...'],
                'started_at' => time(),
                'updated_at' => time(),
            ], now()->addHours(6));

            Log::info('DictionaryIngredientCrawlJob crawl started', [
                'crawl_id' => $crawlId,
                'user_id' => $userId,
                'offset' => $offset,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Run via queue worker. Using afterResponse prevents long-running work from blocking the HTTP request,
            // but only if queue driver is not "sync" (sync driver runs jobs immediately)
            $queueDriver = config('queue.default');
            $job = DictionaryIngredientCrawlJob::dispatch($crawlId, $userId, $offset, 100)
                ->onQueue('dictionary-crawl');
            
            // Only use afterResponse if queue driver is not sync
            if ($queueDriver !== 'sync') {
                $job->afterResponse();
            }
            
            Log::debug('DictionaryIngredientCrawlJob job dispatched', [
                'crawl_id' => $crawlId,
                'queue_driver' => $queueDriver,
                'after_response' => $queueDriver !== 'sync',
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'crawl_id' => $crawlId,
                    'offset' => $offset,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('DictionaryIngredientCrawlJob crawlStart exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'offset' => $request->offset ?? null,
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function crawlStep(Request $request): JsonResponse
    {
        try {
            $offset = (int) ($request->offset ?? 0);
            $batchSize = (int) ($request->batch_size ?? 100);
            if ($batchSize <= 0) {
                $batchSize = 100;
            }
            if ($batchSize > 200) {
                $batchSize = 200;
            }

            $userId = (int) (Auth::id() ?? 0);
            if ($userId <= 0) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $key = $this->crawlStateKey($userId, $offset);
            $cached = Cache::get($key);
            if (!$cached || !isset($cached['state'], $cached['items'])) {
                return response()->json(['success' => false, 'message' => 'Crawl not started'], 409);
            }

            $state = $cached['state'];
            $items = $cached['items'];

            if (!empty($state['done'])) {
                return response()->json(['success' => true, 'data' => $this->crawlPublicState($state, 0, [])]);
            }

            $cursor = (int) ($state['cursor'] ?? 0);
            $total = (int) ($state['total'] ?? 0);

            $end = min($cursor + $batchSize, $total);
            $newLogs = [];

            for ($i = $cursor; $i < $end; $i++) {
                $it = $items[$i] ?? [];
                $name = (string) ($it['name'] ?? '');
                $slug = (string) ($it['id'] ?? '');
                $url = (string) ($it['url'] ?? '');

                try {
                    $status = 'created';
                    $existing = $this->model::where('slug', $slug)->first();
                    if ($existing) {
                        $status = 'updated';
                        $id = $existing->id;
                    } else {
                        $id = $this->model::insertGetId([
                            'name' => $name,
                            'slug' => $slug,
                            'description' => $it['description'] ?? '',
                            'status' => '1',
                            'seo_description' => $it['description'] ?? '',
                            'seo_title' => $name,
                            'user_id' => Auth::id(),
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }

                    if ($id && $url) {
                        $detailUrl = 'https://www.paulaschoice.com' . $url . '&ajax=true';
                        $this->detail($detailUrl, $id);
                    }

                    $line = ($i + 1) . '/' . $total . ' - ' . $name . ' - ' . $status;
                    $newLogs[] = $line;
                } catch (Exception $e) {
                    $state['error'] = $e->getMessage();
                    $line = ($i + 1) . '/' . $total . ' - ' . $name . ' - error: ' . $e->getMessage();
                    $newLogs[] = $line;
                }
            }

            $state['cursor'] = $end;
            $state['batch_size'] = $batchSize;
            if ($end >= $total) {
                $state['done'] = true;
            }

            $state['logs'] = array_merge($state['logs'] ?? [], $newLogs);
            // Keep last 800 lines to avoid unbounded cache growth
            if (count($state['logs']) > 800) {
                $state['logs'] = array_slice($state['logs'], -800);
            }

            Cache::put($key, [
                'state' => $state,
                'items' => $items,
            ], now()->addHours(2));

            return response()->json([
                'success' => true,
                'data' => $this->crawlPublicState($state, count($newLogs), $newLogs),
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function crawlCancel(Request $request): JsonResponse
    {
        try {
            $crawlId = (string) ($request->crawl_id ?? '');
            
            $userId = (int) (Auth::id() ?? 0);
            if ($userId <= 0) {
                Log::warning('DictionaryIngredientCrawlJob crawlCancel unauthenticated', [
                    'crawl_id' => $crawlId,
                ]);
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }
            
            if ($crawlId === '') {
                Log::warning('DictionaryIngredientCrawlJob crawlCancel missing crawl_id', [
                    'user_id' => $userId,
                ]);
                return response()->json(['success' => false, 'message' => 'Missing crawl_id'], 422);
            }
            
            $key = 'dictionary_ingredient_crawl_job:' . $crawlId;
            $state = Cache::get($key);
            if (!is_array($state)) {
                Log::warning('DictionaryIngredientCrawlJob crawlCancel not found', [
                    'crawl_id' => $crawlId,
                    'user_id' => $userId,
                ]);
                return response()->json(['success' => false, 'message' => 'Crawl not found'], 404);
            }
            
            if ((int) ($state['user_id'] ?? 0) !== $userId) {
                Log::warning('DictionaryIngredientCrawlJob crawlCancel forbidden', [
                    'crawl_id' => $crawlId,
                    'user_id' => $userId,
                    'state_user_id' => $state['user_id'] ?? null,
                ]);
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }
            
            // Check if already done or cancelled
            if (!empty($state['done']) || !empty($state['cancelled'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Crawl already stopped',
                    'data' => ['crawl_id' => $crawlId, 'status' => $state['status'] ?? 'unknown'],
                ]);
            }
            
            // Mark as cancelled
            $state['cancelled'] = true;
            $state['status'] = 'cancelling';
            $state['updated_at'] = time();
            Cache::put($key, $state, now()->addHours(6));
            
            Log::info('DictionaryIngredientCrawlJob crawl cancelled', [
                'crawl_id' => $crawlId,
                'user_id' => $userId,
                'offset' => $state['offset'] ?? 0,
                'processed' => $state['processed'] ?? 0,
                'total' => $state['total'] ?? 0,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Crawl cancellation requested',
                'data' => [
                    'crawl_id' => $crawlId,
                    'status' => 'cancelling',
                ],
            ]);
        } catch (Exception $e) {
            Log::error('DictionaryIngredientCrawlJob crawlCancel exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'crawl_id' => $request->crawl_id ?? null,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function crawlStatus(Request $request): JsonResponse
    {
        try {
            $since = (int) ($request->since ?? 0);
            $crawlId = (string) ($request->crawl_id ?? '');

            $userId = (int) (Auth::id() ?? 0);
            if ($userId <= 0) {
                Log::warning('DictionaryIngredientCrawlJob crawlStatus unauthenticated', [
                    'crawl_id' => $crawlId,
                ]);
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            if ($crawlId === '') {
                Log::warning('DictionaryIngredientCrawlJob crawlStatus missing crawl_id', [
                    'user_id' => $userId,
                ]);
                return response()->json(['success' => false, 'message' => 'Missing crawl_id'], 422);
            }

            $key = 'dictionary_ingredient_crawl_job:' . $crawlId;
            $state = Cache::get($key);
            if (!is_array($state)) {
                Log::warning('DictionaryIngredientCrawlJob crawlStatus not found', [
                    'crawl_id' => $crawlId,
                    'user_id' => $userId,
                ]);
                return response()->json(['success' => false, 'message' => 'Crawl not found'], 404);
            }
            if ((int) ($state['user_id'] ?? 0) !== $userId) {
                Log::warning('DictionaryIngredientCrawlJob crawlStatus forbidden', [
                    'crawl_id' => $crawlId,
                    'user_id' => $userId,
                    'state_user_id' => $state['user_id'] ?? null,
                ]);
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $logs = $state['logs'] ?? [];
            if (!is_array($logs)) {
                $logs = [];
            }

            $since = max(0, $since);
            $newLogs = array_slice($logs, $since);

            $responseData = $this->crawlPublicState($state, count($newLogs), $newLogs, $since + count($newLogs));
            
            // Log status check periodically (every 10 requests or when done/error)
            $logStatus = false;
            if ($since === 0 || !empty($state['done']) || !empty($state['error'])) {
                $logStatus = true;
            }

            if ($logStatus) {
                Log::debug('DictionaryIngredientCrawlJob crawlStatus checked', [
                    'crawl_id' => $crawlId,
                    'user_id' => $userId,
                    'status' => $state['status'] ?? 'unknown',
                    'processed' => $responseData['processed'] ?? 0,
                    'total' => $responseData['total'] ?? 0,
                    'done' => $responseData['done'] ?? false,
                    'has_error' => !empty($responseData['error']),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ]);
        } catch (Exception $e) {
            Log::error('DictionaryIngredientCrawlJob crawlStatus exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'crawl_id' => $request->crawl_id ?? null,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function crawlPublicState(array $state, int $stepCount, array $newLogs, ?int $nextSince = null): array
    {
        $total = (int) ($state['total'] ?? 0);
        $cursor = (int) ($state['processed'] ?? 0);
        $startedAt = (int) ($state['started_at'] ?? time());
        $elapsed = max(0, time() - $startedAt);
        $done = !empty($state['done']);
        $error = $state['error'] ?? null;

        return [
            'crawl_id' => (string) ($state['crawl_id'] ?? ''),
            'offset' => (int) ($state['offset'] ?? 0),
            'total' => $total,
            'processed' => $cursor,
            'done' => $done,
            'error' => $error,
            'cancelled' => !empty($state['cancelled']),
            'elapsed' => $elapsed,
            'step_processed' => $stepCount,
            'logs' => $newLogs,
            'next_since' => $nextSince,
            'status' => (string) ($state['status'] ?? ''),
        ];
    }

    private function curlJson(string $url): array
    {
        $startTime = microtime(true);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $fetchTime = round((microtime(true) - $startTime) * 1000, 2);
        $contentLength = strlen($content ?? '');

        if ($error) {
            Log::error('DictionaryIngredientCrawlJob curl error (Controller)', [
                'url' => $url,
                'error' => $error,
                'http_code' => $httpCode,
                'fetch_time_ms' => $fetchTime,
            ]);
            return [];
        }

        if ($httpCode !== 200) {
            Log::warning('DictionaryIngredientCrawlJob non-200 response (Controller)', [
                'url' => $url,
                'http_code' => $httpCode,
                'content_length' => $contentLength,
                'fetch_time_ms' => $fetchTime,
            ]);
        }

        $decoded = json_decode((string) $content, true);
        if (!is_array($decoded)) {
            Log::warning('DictionaryIngredientCrawlJob invalid JSON (Controller)', [
                'url' => $url,
                'http_code' => $httpCode,
                'content_length' => $contentLength,
                'content_preview' => substr($content ?? '', 0, 200),
            ]);
            return [];
        }

        return $decoded;
    }

    private function normalizeString($value): string
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
            $text = trim(implode(' ', $parts));
            return $text;
        }
        if (is_scalar($value)) {
            return trim((string) $value);
        }
        return '';
    }

    private function buildDescription($sections): string
    {
        if (!is_array($sections)) {
            $text = $this->normalizeString($sections);
            return $text !== '' ? '<p>' . $text . '</p>' : '';
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

    private function buildReferences($references): string
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

    private function buildGlance($points): string
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

    public function getDom($link){
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        $dom = HtmlDomParser::str_get_html($content);
        return $dom;
    }

    public function detail($link,$id){
        try{
            Log::debug('DictionaryIngredientCrawlJob detail fetch started', [
                'ingredient_id' => $id,
                'url' => $link,
            ]);

            $startTime = microtime(true);
            $rooms = $this->curlJson($link);
            $fetchTime = round((microtime(true) - $startTime) * 1000, 2);

            if (empty($rooms)) {
                Log::warning('DictionaryIngredientCrawlJob detail empty response', [
                    'ingredient_id' => $id,
                    'url' => $link,
                    'fetch_time_ms' => $fetchTime,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Empty response from remote'
                ]);
            }

            $content2 = $this->buildDescription($rooms['description'] ?? []);
            $reference = $this->buildReferences($rooms['references'] ?? []);
            $disclaimer = $this->normalizeString($rooms['strings']['disclaimer'] ?? '');
            $glance = $this->buildGlance($rooms['keyPoints'] ?? []);

            $catIds = $this->getCategory($rooms['relatedCategories'] ?? []);
            $benefitIds = $this->getBenefit($rooms['benefits'] ?? []);
            $rateId = $this->getRate($rooms['rating'] ?? '');

            $this->model::where('id',$id)->update(
                [
                    'name' => $rooms['name'] ?? '',
                    'rate_id' => $rateId,
                    'content' => $content2,
                    'reference' => $reference,
                    'disclaimer' => $disclaimer,
                    'glance' => $glance,
                    'status' => '1',
                    'cat_id' => json_encode($catIds),
                    'benefit_id' => json_encode($benefitIds),
                ]
            );

            $processTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('DictionaryIngredientCrawlJob detail updated', [
                'ingredient_id' => $id,
                'name' => $rooms['name'] ?? '',
                'rate_id' => $rateId,
                'categories_count' => count($catIds),
                'benefits_count' => count($benefitIds),
                'has_content' => !empty($content2),
                'has_reference' => !empty($reference),
                'has_glance' => !empty($glance),
                'fetch_time_ms' => $fetchTime,
                'total_time_ms' => $processTime,
            ]);
        }catch (Exception $e) {
            Log::error('DictionaryIngredientCrawlJob detail exception', [
                'ingredient_id' => $id,
                'url' => $link,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getBenefit($benefits){
        $array = array();
        if(isset($benefits) && !empty($benefits)){
            foreach ($benefits as $key => $value) {
                $detail = IngredientBenefit::where('name',$value['name'])->first();
                if(isset($detail) && !empty($detail)){
                    array_push($array, strval($detail->id));
                }
            }
        }
        return $array;
    }

    public function getCategory($categories){
        $array = array();
        if(isset($categories) && !empty($categories)){
            foreach ($categories as $key => $value) {
                $detail = IngredientCategory::where('name',$value['name'])->first();
                if(isset($detail) && !empty($detail)){
                    array_push($array, strval($detail->id));
                }
            }
        }
        return $array;
    }

    public function getRate($rate){
        $rateName = $this->normalizeString($rate);
        if($rateName != ""){
            $detail = IngredientRate::where('name',$rateName)->first();
            if(isset($detail) && !empty($detail)){
                return $detail->id;
            }else{
                return '0';
            }
        }else{
            return '0';
        }
    }

    public function updateIngredient(){
        try{
            $link = 'https://www.paulaschoice.com/ingredient-dictionary/ingredient-aha.html?sz=2000&fdid=ingredients&start=0&ajax=true';
            $id = '23';
            $ch = curl_init($link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
            curl_close($ch);
            $rooms = json_decode($content, true);
            $description = $rooms['description'];
            $content2 = '';
            if(isset($description) && !empty($description)){
                foreach ($description as $key => $value) {
                    $stt = count($value['text'])-1;
                    $content2 .='<p>'.$value['text'][$stt].'</p>';
                }
            }
           
            $reference = '';
            $references = $rooms['references'];
            if(isset($references) && !empty($references)){
                foreach ($references as $key1 => $val1) {
                    $reference .='<p>'.$val1.'</p>';
                }
            }
            $disclaimer = '';
            $strings = $rooms['strings'];
            if(isset($strings) && !empty($strings)){
                if(isset($strings['disclaimer'])){
                    $disclaimer = $strings['disclaimer'];
                }
            }
            $glance = '';
            if(isset($rooms['keyPoints']) && !empty($rooms['keyPoints'])){
                $keyPoints = $rooms['keyPoints'];
                $glance .= '<ul>';
                foreach ($keyPoints as $key2 => $val2) {
                    $glance .='<li>'.$val2.'</li>';
                }
                $glance .= '</ul>';
            }
            $this->model::where('id',$id)->update(
                [
                    'name' => $rooms['name'],
                    'rate_id' => $this->getRate($rooms['rating']),
                    'content' => $content2,
                    'reference' => $reference,
                    'disclaimer' => $disclaimer,
                    'glance' => $glance,
                    'status' => '1',
                    'cat_id' => json_encode($this->getCategory($rooms['relatedCategories'])),
                    'benefit_id' => json_encode($this->getBenefit($rooms['benefits'])),
                ]
            );
        }catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function publicList(): JsonResponse
    {
        $items = $this->model->newQuery()
            ->select(['name as title', 'slug'])
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderByRaw('CHAR_LENGTH(name) DESC')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function adminAllIngredients(): JsonResponse
    {
        $items = $this->model->newQuery()
            ->select(['name as title', 'slug', 'benefit_id', 'rate_id'])
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderByRaw('CHAR_LENGTH(name) DESC')
            ->orderBy('name', 'asc')
            ->get();

        $benefitIds = [];
        $rateIds = [];

        foreach ($items as $item) {
            $rateId = (int) ($item->rate_id ?? 0);
            if ($rateId > 0) {
                $rateIds[$rateId] = true;
            }

            $ids = $item->benefit_id ?? [];
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                $ids = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($ids)) {
                $ids = [];
            }

            foreach ($ids as $id) {
                $bid = (int) $id;
                if ($bid > 0) {
                    $benefitIds[$bid] = true;
                }
            }
        }

        $benefitMap = IngredientBenefit::query()
            ->select(['id', 'name'])
            ->whereIn('id', array_keys($benefitIds))
            ->get()
            ->keyBy('id');

        $rateMap = IngredientRate::query()
            ->select(['id', 'name'])
            ->whereIn('id', array_keys($rateIds))
            ->get()
            ->keyBy('id');

        $data = [];
        foreach ($items as $item) {
            $ids = $item->benefit_id ?? [];
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                $ids = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($ids)) {
                $ids = [];
            }

            $benefits = [];
            foreach ($ids as $id) {
                $bid = (int) $id;
                if ($bid <= 0) {
                    continue;
                }
                $b = $benefitMap->get($bid);
                if ($b) {
                    $benefits[] = ['id' => (int) $b->id, 'name' => (string) $b->name];
                }
            }

            $rates = [];
            $rateId = (int) ($item->rate_id ?? 0);
            if ($rateId > 0) {
                $r = $rateMap->get($rateId);
                if ($r) {
                    $rates[] = ['id' => (int) $r->id, 'name' => (string) $r->name];
                }
            }

            $data[] = [
                'title' => (string) $item->title,
                'slug' => (string) $item->slug,
                'benefits' => $benefits,
                'rates' => $rates,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
