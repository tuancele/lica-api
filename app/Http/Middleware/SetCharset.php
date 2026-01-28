<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCharset
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Set UTF-8 charset header for all text responses
        if ($response instanceof \Illuminate\Http\Response || $response instanceof \Symfony\Component\HttpFoundation\Response) {
            $contentType = $response->headers->get('Content-Type', '');
            
            // For HTML responses
            if (str_contains($contentType, 'text/html') || str_contains($contentType, 'application/xhtml')) {
                if (! str_contains($contentType, 'charset')) {
                    $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
                } else {
                    // Ensure charset is UTF-8 even if already set
                    $response->headers->set('Content-Type', preg_replace('/charset=[^;]+/i', 'charset=UTF-8', $contentType));
                }
            }
            // For JSON responses
            elseif (str_contains($contentType, 'application/json')) {
                if (! str_contains($contentType, 'charset')) {
                    $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
                } else {
                    $response->headers->set('Content-Type', preg_replace('/charset=[^;]+/i', 'charset=UTF-8', $contentType));
                }
            }
            // For plain text responses
            elseif (str_contains($contentType, 'text/plain') || str_contains($contentType, 'text/css') || str_contains($contentType, 'text/javascript')) {
                if (! str_contains($contentType, 'charset')) {
                    $response->headers->set('Content-Type', $contentType . '; charset=UTF-8');
                } else {
                    $response->headers->set('Content-Type', preg_replace('/charset=[^;]+/i', 'charset=UTF-8', $contentType));
                }
            }
            // For XML responses
            elseif (str_contains($contentType, 'application/xml') || str_contains($contentType, 'text/xml')) {
                if (! str_contains($contentType, 'charset')) {
                    $response->headers->set('Content-Type', $contentType . '; charset=UTF-8');
                } else {
                    $response->headers->set('Content-Type', preg_replace('/charset=[^;]+/i', 'charset=UTF-8', $contentType));
                }
            }
        }

        return $response;
    }
}

