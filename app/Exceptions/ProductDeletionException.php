<?php

declare(strict_types=1);
namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when product deletion fails
 */
class ProductDeletionException extends Exception
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
                'message' => $this->getMessage() ?: 'Không thể xóa sản phẩm',
                'error_code' => 'PRODUCT_DELETION_FAILED'
            ], 422);
        }
        
        return redirect()->back()
            ->with('error', $this->getMessage() ?: 'Không thể xóa sản phẩm');
    }
}
