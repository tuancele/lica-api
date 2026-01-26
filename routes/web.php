<?php

declare(strict_types=1);
use App\Http\Controllers\ApiDocsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/docs-api', [ApiDocsController::class, 'index'])->name('api.docs');
