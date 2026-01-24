<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugLogController extends Controller
{
    /**
     * Write debug log from frontend JavaScript
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function writeLog(Request $request)
    {
        $level = $request->input('level', 'info'); // info, warning, error
        $message = $request->input('message', '');
        $context = $request->input('context', []);
        
        // Validate level
        $allowedLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        if (!in_array($level, $allowedLevels)) {
            $level = 'info';
        }
        
        // Format message with context
        $logMessage = '[CHECKOUT_CALCULATION] ' . $message;
        if (!empty($context)) {
            $logMessage .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        // Write to Laravel log
        Log::channel('single')->{$level}($logMessage);
        
        return response()->json([
            'success' => true,
            'message' => 'Log written successfully'
        ]);
    }
}

