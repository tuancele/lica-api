<?php

declare(strict_types=1);
namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a product is not found
 */
class ProductNotFoundException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sản phẩm không tồn tại',
                'error_code' => 'PRODUCT_NOT_FOUND'
            ], 404);
        }
        
        return redirect()->route('product.index')
            ->with('error', 'Sản phẩm không tồn tại');
    }
}
