<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\InventoryController;

/*
|--------------------------------------------------------------------------
| Inventory API Routes
|--------------------------------------------------------------------------
|
| Add to routes/api.php: require __DIR__.'/api_inventory.php';
|
*/

Route::prefix('v2/inventory')->middleware(['auth:sanctum'])->group(function () {
    // Stock queries
    Route::get('stocks', [InventoryController::class, 'stocks']);
    Route::get('stocks/{variantId}', [InventoryController::class, 'stockShow']);
    Route::post('stocks/check-availability', [InventoryController::class, 'checkAvailability']);
    Route::get('stocks/low-stock', [InventoryController::class, 'lowStock']);
    
    // Stock mutations
    Route::post('receipts/import', [InventoryController::class, 'import']);
    Route::post('receipts/export', [InventoryController::class, 'export']);
    Route::post('receipts/transfer', [InventoryController::class, 'transfer']);
    Route::post('receipts/adjust', [InventoryController::class, 'adjust']);
    
    // Receipts
    Route::get('receipts', [InventoryController::class, 'receipts']);
    Route::get('receipts/{id}', [InventoryController::class, 'receiptShow']);
    Route::delete('receipts/{id}', [InventoryController::class, 'receiptDestroy']);
    
    // Warehouses
    Route::get('warehouses', [InventoryController::class, 'warehouses']);
    
    // Movements & Reports
    Route::get('movements', [InventoryController::class, 'movements']);
    Route::get('reports/valuation', [InventoryController::class, 'valuation']);
});
