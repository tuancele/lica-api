<?php

return [
    // Google Merchant Center account ID (numeric).
    'merchant_id' => env('GMC_MERCHANT_ID', ''),

    // Service account JSON path (must not be committed).
    'service_account_json' => env('GMC_SERVICE_ACCOUNT_JSON', storage_path('app/google/service-account.json')),

    // Feed defaults.
    'channel' => env('GMC_CHANNEL', 'online'),
    'content_language' => env('GMC_CONTENT_LANGUAGE', 'vi'),
    'target_country' => env('GMC_TARGET_COUNTRY', 'VN'),
    'currency' => env('GMC_CURRENCY', 'VND'),

    // Warehouse id used for Inventory V2 stock queries (null = default).
    'warehouse_id' => env('GMC_WAREHOUSE_ID', null),

    // Offer id strategy: "sku" or "variant_id".
    'offer_id_strategy' => env('GMC_OFFER_ID_STRATEGY', 'sku'),

    // Optional: Google product category (string). Empty = not set.
    'google_product_category' => env('GMC_GOOGLE_PRODUCT_CATEGORY', ''),

    // Store base URL used for product links in GMC to avoid domain mismatch.
    // Example: https://lica.vn (no trailing slash required).
    'store_base_url' => env('GMC_STORE_BASE_URL', env('APP_URL', '')),

    // Debug flag: when true, log detailed GMC payload info (without secrets).
    'debug' => (bool) env('GMC_DEBUG', false),
];
