<?php
/**
 * Test Deal Admin API Endpoints
 * 
 * Usage: php test_deal_admin_api.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Modules\Deal\Models\Deal;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\User;

// Colors for output
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$BLUE = "\033[34m";
$NC = "\033[0m"; // No Color

echo "\n{$BLUE}=== Testing Deal Admin API Endpoints ==={$NC}\n\n";

// Get test user (first user)
$user = User::first();
if (!$user) {
    echo "{$RED}Error: No user found{$NC}\n";
    exit(1);
}

// Set authenticated user
Auth::setUser($user);

// Create test products with variants (exclude products already in active deals)
echo "{$YELLOW}Setting up test data...{$NC}\n";

// Get products that are NOT in active deals
$now = strtotime(date('Y-m-d H:i:s'));
$activeDealProductIds = DB::table('deal_products')
    ->join('deals', 'deal_products.deal_id', '=', 'deals.id')
    ->where('deals.status', '1')
    ->where('deals.start', '<=', $now)
    ->where('deals.end', '>=', $now)
    ->pluck('deal_products.product_id')
    ->unique()
    ->toArray();

$product1 = Product::where('type', 'product')
    ->where('status', '1')
    ->whereNotIn('id', $activeDealProductIds)
    ->first();

$product2 = Product::where('type', 'product')
    ->where('status', '1')
    ->where('id', '!=', $product1->id ?? 0)
    ->whereNotIn('id', $activeDealProductIds)
    ->first();

// If still no products, try any products
if (!$product1) {
    $product1 = Product::where('type', 'product')->where('status', '1')->first();
}
if (!$product2) {
    $product2 = Product::where('type', 'product')->where('status', '1')->where('id', '!=', $product1->id ?? 0)->first();
}

if (!$product1 || !$product2) {
    echo "{$RED}Error: Need at least 2 products for testing{$NC}\n";
    exit(1);
}

// Get variants
$variant1 = Variant::where('product_id', $product1->id)->first();
$variant2 = Variant::where('product_id', $product2->id)->first();

echo "{$GREEN}Using products: {$product1->id} and {$product2->id}{$NC}\n";
if ($variant1) echo "{$GREEN}Variant 1: {$variant1->id}{$NC}\n";
if ($variant2) echo "{$GREEN}Variant 2: {$variant2->id}{$NC}\n\n";

// Test 1: GET /admin/api/deals (List)
echo "{$BLUE}[Test 1] GET /admin/api/deals (List){$NC}\n";
try {
    $response = $app->make('Illuminate\Http\Request')->create('/admin/api/deals', 'GET', [
        'page' => 1,
        'limit' => 10
    ]);
    
    $controller = new \App\Modules\ApiAdmin\Controllers\DealController();
    $result = $controller->index($response);
    $data = json_decode($result->getContent(), true);
    
    if ($data['success']) {
        echo "{$GREEN}✓ Success: Found " . count($data['data']) . " deals{$NC}\n";
    } else {
        echo "{$RED}✗ Failed: {$data['message']}{$NC}\n";
    }
} catch (\Exception $e) {
    echo "{$RED}✗ Error: {$e->getMessage()}{$NC}\n";
}
echo "\n";

// Test 2: POST /admin/api/deals (Create)
echo "{$BLUE}[Test 2] POST /admin/api/deals (Create){$NC}\n";
try {
    $startDate = date('Y-m-d H:i:s', strtotime('+1 day'));
    $endDate = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $requestData = [
        'name' => 'Test Deal API ' . time(),
        'start' => $startDate,
        'end' => $endDate,
        'status' => '1',
        'limited' => 3,
        'products' => [
            [
                'product_id' => $product1->id,
                'variant_id' => $product1->has_variants ? ($variant1->id ?? null) : null,
                'status' => '1'
            ]
        ],
        'sale_products' => [
            [
                'product_id' => $product2->id,
                'variant_id' => $product2->has_variants ? ($variant2->id ?? null) : null,
                'price' => 100000,
                'qty' => 2,
                'status' => '1'
            ]
        ]
    ];
    
    $request = $app->make('Illuminate\Http\Request')->create('/admin/api/deals', 'POST', $requestData);
    
    $controller = new \App\Modules\ApiAdmin\Controllers\DealController();
    $result = $controller->store($request);
    $data = json_decode($result->getContent(), true);
    
    if (!$data['success']) {
        echo "{$RED}✗ Failed: {$data['message']}{$NC}\n";
        if (isset($data['error'])) {
            echo "  Error: {$data['error']}{$NC}\n";
        }
        if (isset($data['errors'])) {
            echo "  Validation Errors:\n";
            print_r($data['errors']);
        }
        if (isset($data['conflicts'])) {
            echo "  Conflicts:\n";
            print_r($data['conflicts']);
        }
    } else {
        $dealId = $data['data']['id'];
        echo "{$GREEN}✓ Success: Created Deal ID {$dealId}{$NC}\n";
        
        // Test 3: GET /admin/api/deals/{id} (Show)
        echo "\n{$BLUE}[Test 3] GET /admin/api/deals/{$dealId} (Show){$NC}\n";
        try {
            $showRequest = $app->make('Illuminate\Http\Request')->create("/admin/api/deals/{$dealId}", 'GET');
            $showResult = $controller->show($dealId);
            $showData = json_decode($showResult->getContent(), true);
            
            if ($showData['success']) {
                echo "{$GREEN}✓ Success: Retrieved Deal details{$NC}\n";
                echo "  - Products: " . count($showData['data']['products'] ?? []) . "\n";
                echo "  - Sale Products: " . count($showData['data']['sale_products'] ?? []) . "\n";
            } else {
                echo "{$RED}✗ Failed: {$showData['message']}{$NC}\n";
            }
        } catch (\Exception $e) {
            echo "{$RED}✗ Error: {$e->getMessage()}{$NC}\n";
        }
        
        // Test 4: PUT /admin/api/deals/{id} (Update)
        echo "\n{$BLUE}[Test 4] PUT /admin/api/deals/{$dealId} (Update){$NC}\n";
        try {
            $updateData = [
                'name' => 'Updated Test Deal ' . time(),
                'limited' => 5
            ];
            
            $updateRequest = $app->make('Illuminate\Http\Request')->create("/admin/api/deals/{$dealId}", 'PUT', $updateData);
            
            $updateResult = $controller->update($updateRequest, $dealId);
            $updateResponse = json_decode($updateResult->getContent(), true);
            
            if ($updateResponse['success']) {
                echo "{$GREEN}✓ Success: Updated Deal{$NC}\n";
            } else {
                echo "{$RED}✗ Failed: {$updateResponse['message']}{$NC}\n";
            }
        } catch (\Exception $e) {
            echo "{$RED}✗ Error: {$e->getMessage()}{$NC}\n";
        }
        
        // Test 5: PATCH /admin/api/deals/{id}/status (Update Status)
        echo "\n{$BLUE}[Test 5] PATCH /admin/api/deals/{$dealId}/status (Update Status){$NC}\n";
        try {
            $statusRequest = $app->make('Illuminate\Http\Request')->create("/admin/api/deals/{$dealId}/status", 'PATCH', [
                'status' => '0'
            ]);
            
            $statusResult = $controller->updateStatus($statusRequest, $dealId);
            $statusResponse = json_decode($statusResult->getContent(), true);
            
            if ($statusResponse['success']) {
                echo "{$GREEN}✓ Success: Updated Deal status{$NC}\n";
            } else {
                echo "{$RED}✗ Failed: {$statusResponse['message']}{$NC}\n";
            }
        } catch (\Exception $e) {
            echo "{$RED}✗ Error: {$e->getMessage()}{$NC}\n";
        }
        
        // Test 6: DELETE /admin/api/deals/{id} (Delete)
        echo "\n{$BLUE}[Test 6] DELETE /admin/api/deals/{$dealId} (Delete){$NC}\n";
        try {
            $deleteResult = $controller->destroy($dealId);
            $deleteResponse = json_decode($deleteResult->getContent(), true);
            
            if ($deleteResponse['success']) {
                echo "{$GREEN}✓ Success: Deleted Deal{$NC}\n";
            } else {
                echo "{$RED}✗ Failed: {$deleteResponse['message']}{$NC}\n";
            }
        } catch (\Exception $e) {
            echo "{$RED}✗ Error: {$e->getMessage()}{$NC}\n";
        }
    }
} catch (\Exception $e) {
    echo "{$RED}✗ Error: {$e->getMessage()}{$NC}\n";
    echo "Stack trace: {$e->getTraceAsString()}\n";
}
echo "\n";

// Test 7: Validation Tests
echo "{$BLUE}[Test 7] Validation Tests{$NC}\n";
try {
    // Test invalid data
    $invalidRequest = $app->make('Illuminate\Http\Request')->create('/admin/api/deals', 'POST', [
        'name' => '', // Empty name
        'start' => 'invalid-date',
        'end' => date('Y-m-d H:i:s', strtotime('-1 day')), // End before start
    ]);
    
    $controller = new \App\Modules\ApiAdmin\Controllers\DealController();
    $invalidResult = $controller->store($invalidRequest);
    $invalidData = json_decode($invalidResult->getContent(), true);
    
    if (!$invalidData['success'] && isset($invalidData['errors'])) {
        echo "{$GREEN}✓ Validation working correctly{$NC}\n";
    } else {
        echo "{$RED}✗ Validation not working{$NC}\n";
    }
} catch (\Exception $e) {
    echo "{$RED}✗ Error: {$e->getMessage()}{$NC}\n";
}
echo "\n";

echo "{$BLUE}=== Testing Complete ==={$NC}\n\n";
