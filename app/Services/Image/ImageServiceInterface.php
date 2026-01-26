<?php

declare(strict_types=1);

namespace App\Services\Image;

/**
 * Interface for Image Service.
 *
 * Handles image gallery processing and management
 */
interface ImageServiceInterface
{
    /**
     * Process gallery images from form and session.
     *
     * @param  array  $formImages  Images from form (imageOther)
     * @param  string|null  $sessionKey  R2 session key for uploaded images
     * @param  array  $existingGallery  Existing gallery (e.g. from DB) to merge with, can be empty on create
     * @return array Processed gallery array
     */
    public function processGallery(array $formImages = [], ?string $sessionKey = null, array $existingGallery = []): array;

    /**
     * Get main image from gallery (first image).
     */
    public function getMainImage(array $gallery): ?string;

    /**
     * Clear session URLs after processing.
     */
    public function clearSessionUrls(?string $sessionKey = null): void;
}
