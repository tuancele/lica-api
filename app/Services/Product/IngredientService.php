<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Modules\Dictionary\Models\IngredientPaulas;
use Illuminate\Support\Facades\Cache;

/**
 * Service for processing product ingredients.
 *
 * Handles ingredient text processing, linking to IngredientPaulas dictionary,
 * and extracting structured ingredient data for API responses.
 */
class IngredientService
{
    /**
     * Process ingredient text and return structured data.
     *
     * @param  string|null  $ingredientText  Raw ingredient text from product
     * @return array Contains: raw, html, ingredients_list
     */
    public function processIngredient(?string $ingredientText): array
    {
        if (empty($ingredientText)) {
            return [
                'raw' => '',
                'html' => '',
                'ingredients_list' => [],
            ];
        }

        // Check if already processed (contains item_ingredient links)
        $isProcessed = preg_match('/<a[^>]*class=["\']item_ingredient["\'][^>]*>/i', $ingredientText);

        if ($isProcessed) {
            // Already processed - extract ingredients from HTML
            return $this->extractFromProcessedHtml($ingredientText);
        }

        // Not processed - process it now using IngredientPaulas dictionary
        return $this->processRawText($ingredientText);
    }

    /**
     * Extract ingredients from already processed HTML.
     *
     * @param  string  $html  Processed HTML with item_ingredient links
     */
    private function extractFromProcessedHtml(string $html): array
    {
        $raw = strip_tags($html);

        // Extract ingredient links
        preg_match_all('/<a[^>]*class=["\']item_ingredient["\'][^>]*data-id=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i', $html, $matches);

        $ingredientsList = [];
        if (! empty($matches[1])) {
            foreach ($matches[1] as $index => $slug) {
                $name = $matches[2][$index] ?? '';
                if (! empty($slug) && ! empty($name)) {
                    $ingredientsList[] = [
                        'name' => trim($name),
                        'slug' => trim($slug),
                        'link' => '/ingredient-dictionary/'.trim($slug),
                    ];
                }
            }
        }

        return [
            'raw' => $raw,
            'html' => $html,
            'ingredients_list' => $ingredientsList,
        ];
    }

    /**
     * Process raw ingredient text and link to IngredientPaulas.
     *
     * @param  string  $text  Raw ingredient text
     */
    private function processRawText(string $text): array
    {
        $raw = strip_tags($text);

        // Get all active ingredients from IngredientPaulas dictionary (cached)
        $ingredients = Cache::remember('ingredient_paulas_active_list', 3600, function () {
            return IngredientPaulas::where('status', '1')
                ->select('id', 'name', 'slug')
                ->get();
        });

        // Build lookup map: lowercase name => ingredient object (Paulas only)
        $ingMap = [];
        foreach ($ingredients as $ing) {
            $lowerName = mb_strtolower(trim($ing->name), 'UTF-8');
            $ingMap[$lowerName] = [
                'name' => $ing->name,
                'slug' => $ing->slug,
            ];
        }

        // Process text and build HTML with links
        $processedHtml = $text;
        $ingredientsList = [];

        // Try to match ingredients in text using dictionary map
        foreach ($ingMap as $lowerName => $ingData) {
            $name = $ingData['name'];
            $slug = $ingData['slug'];

            // Use case-insensitive replacement
            $pattern = '/\b'.preg_quote($name, '/').'\b/i';
            if (preg_match($pattern, $processedHtml)) {
                $link = '/ingredient-dictionary/'.$slug;

                $linkHtml = '<a href="javascript:;" class="item_ingredient" data-id="'.htmlspecialchars($slug, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</a>';

                $processedHtml = preg_replace($pattern, $linkHtml, $processedHtml);

                // Add to ingredients list (avoid duplicates)
                $exists = false;
                foreach ($ingredientsList as $existing) {
                    if ($existing['slug'] === $slug) {
                        $exists = true;
                        break;
                    }
                }

                if (! $exists) {
                    $ingredientsList[] = [
                        'name' => $name,
                        'slug' => $slug,
                        'link' => $link,
                    ];
                }
            }
        }

        return [
            'raw' => $raw,
            'html' => $processedHtml,
            'ingredients_list' => $ingredientsList,
        ];
    }
}
