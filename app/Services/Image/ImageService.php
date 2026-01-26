<?php

declare(strict_types=1);

namespace App\Services\Image;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Service for Image processing.
 *
 * Handles gallery image processing, validation, and session management
 */
class ImageService implements ImageServiceInterface
{
    /**
     * Process gallery images from form and session.
     *
     * @param  array  $formImages  Images from form (imageOther)
     * @param  string|null  $sessionKey  R2 session key for uploaded images
     * @param  array  $existingGallery  Existing gallery (e.g. from DB) to merge with, can be empty on create
     * @return array Processed gallery array
     */
    public function processGallery(array $formImages = [], ?string $sessionKey = null, array $existingGallery = []): array
    {
        // Filter form images
        $formImages = $this->filterInvalidUrls($formImages);

        // Get session URLs (scoped strictly by provided session key)
        $sessionUrls = $this->getSessionUrls($sessionKey);

        // Merge all URLs: existing gallery (from DB) + form + new session URLs
        // Điều này giúp khi edit, nếu vì lý do nào đó JS không gửi lại đủ imageOther[],
        // chúng ta vẫn không mất ảnh cũ. Nếu user muốn xóa ảnh cũ thì sẽ xử lý sau.
        $existingGallery = $this->filterInvalidUrls($existingGallery);
        $allUrls = array_merge($existingGallery, $formImages, $sessionUrls);

        // Remove duplicates while preserving order
        $gallery = $this->removeDuplicates($allUrls);

        // Final validation
        $gallery = $this->filterInvalidUrls($gallery);

        Log::info('ImageService: Gallery processed', [
            'existing_gallery_count' => count($existingGallery),
            'form_images_count' => count($formImages),
            'session_urls_count' => count($sessionUrls),
            'final_gallery_count' => count($gallery),
        ]);

        return array_values($gallery); // Re-index array
    }

    /**
     * Get main image from gallery (first image).
     */
    public function getMainImage(array $gallery): ?string
    {
        return ! empty($gallery) ? $gallery[0] : null;
    }

    /**
     * Clear session URLs after processing.
     */
    public function clearSessionUrls(?string $sessionKey = null): void
    {
        if ($sessionKey) {
            $sessionKeys = is_array($sessionKey) ? $sessionKey : explode(',', $sessionKey);
            $sessionKeys = array_filter(array_map('trim', $sessionKeys));

            foreach ($sessionKeys as $key) {
                Session::forget($key);
            }
        }
    }

    /**
     * Filter invalid URLs.
     */
    private function filterInvalidUrls(array $urls): array
    {
        return array_filter($urls, function ($url) {
            return ! empty($url) &&
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false &&
                   $url !== asset('public/admin/no-image.png') &&
                   $url !== url('public/admin/no-image.png');
        });
    }

    /**
     * Get URLs from session.
     */
    private function getSessionUrls(?string $sessionKey = null): array
    {
        if (! $sessionKey) {
            return [];
        }

        $sessionUrls = [];
        $sessionKeys = is_array($sessionKey) ? $sessionKey : explode(',', $sessionKey);
        $sessionKeys = array_filter(array_map('trim', $sessionKeys));

        foreach ($sessionKeys as $key) {
            $urlsFromKey = Session::get($key, []);

            if (! empty($urlsFromKey)) {
                if (is_array($urlsFromKey)) {
                    $sessionUrls = array_merge($sessionUrls, $urlsFromKey);
                } else {
                    $sessionUrls[] = $urlsFromKey;
                }
            }
        }

        return $this->filterInvalidUrls($sessionUrls);
    }

    /**
     * Remove duplicates while preserving order.
     */
    private function removeDuplicates(array $urls): array
    {
        $gallery = [];
        $seen = [];

        foreach ($urls as $url) {
            if (! in_array($url, $seen)) {
                $gallery[] = $url;
                $seen[] = $url;
            }
        }

        return $gallery;
    }
}
