<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when product update fails.
 */
class ProductUpdateException extends Exception
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
                'message' => $this->getMessage() ?: 'Không thể cập nhật sản phẩm',
                'error_code' => 'PRODUCT_UPDATE_FAILED',
            ], 422);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $this->getMessage() ?: 'Không thể cập nhật sản phẩm');
    }
}
