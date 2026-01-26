<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiDocsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/docs-api', [ApiDocsController::class, 'index'])->name('api.docs');

