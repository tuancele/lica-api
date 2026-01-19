<?php

/**
 * Warehouse API Test Script
 * 
 * Usage: php test_warehouse_api.php
 * 
 * Note: Cần có authentication token để test. 
 * Có thể lấy token từ session hoặc tạo Personal Access Token.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$baseUrl = '/admin/api/v1/warehouse';

// Test với user ID = 1 (admin mặc định)
// Trong thực tế, bạn cần authentication token
$userId = 1;

echo "=== Warehouse API V1 Test Script ===\n\n";

// Test 1: Get Inventory List
echo "Test 1: GET /inventory\n";
try {
    $service = app(\App\Services\Warehouse\WarehouseServiceInterface::class);
    $inventory = $service->getInventory([], 5);
    echo "✓ Success: Found " . $inventory->total() . " variants\n";
    echo "  First item: " . ($inventory->first() ? "Variant ID " . ($inventory->first()->variant_id ?? 'N/A') : "No data") . "\n\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Search Products
echo "Test 2: GET /products/search\n";
try {
    $service = app(\App\Services\Warehouse\WarehouseServiceInterface::class);
    $products = $service->searchProducts('test', 5);
    echo "✓ Success: Found " . count($products) . " products\n";
    if (!empty($products)) {
        echo "  First product: " . $products[0]['name'] . "\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Get Summary Statistics
echo "Test 3: GET /statistics/summary\n";
try {
    $service = app(\App\Services\Warehouse\WarehouseServiceInterface::class);
    $stats = $service->getSummaryStatistics([]);
    echo "✓ Success: Statistics retrieved\n";
    echo "  Total Products: " . ($stats['total_products'] ?? 0) . "\n";
    echo "  Total Variants: " . ($stats['total_variants'] ?? 0) . "\n";
    echo "  Total Import Receipts: " . ($stats['total_import_receipts'] ?? 0) . "\n";
    echo "  Total Export Receipts: " . ($stats['total_export_receipts'] ?? 0) . "\n";
    echo "\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Get Import Receipts List
echo "Test 4: GET /import-receipts\n";
try {
    $service = app(\App\Services\Warehouse\WarehouseServiceInterface::class);
    $receipts = $service->getImportReceipts([], 5);
    echo "✓ Success: Found " . $receipts->total() . " import receipts\n";
    if ($receipts->count() > 0) {
        $first = $receipts->first();
        echo "  First receipt: " . ($first->code ?? 'N/A') . " - " . ($first->subject ?? 'N/A') . "\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 5: Get Export Receipts List
echo "Test 5: GET /export-receipts\n";
try {
    $service = app(\App\Services\Warehouse\WarehouseServiceInterface::class);
    $receipts = $service->getExportReceipts([], 5);
    echo "✓ Success: Found " . $receipts->total() . " export receipts\n";
    if ($receipts->count() > 0) {
        $first = $receipts->first();
        echo "  First receipt: " . ($first->code ?? 'N/A') . " - " . ($first->subject ?? 'N/A') . "\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "=== Test Complete ===\n";
echo "\nNote: Để test đầy đủ các endpoints, cần:\n";
echo "1. Authentication token (Bearer token)\n";
echo "2. Test với Postman hoặc curl\n";
echo "3. Xem file WAREHOUSE_API_TEST_GUIDE.md để biết chi tiết\n";
