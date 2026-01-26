<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Warehouse
    |--------------------------------------------------------------------------
    |
    | The default warehouse ID to use when no warehouse is specified.
    |
    */
    'default_warehouse_id' => env('INVENTORY_DEFAULT_WAREHOUSE', 1),

    /*
    |--------------------------------------------------------------------------
    | Stock Reservation Settings
    |--------------------------------------------------------------------------
    |
    | Configure how long stock reservations should last before auto-release.
    |
    */
    'reservations' => [
        // Cart reservation duration (minutes)
        'cart_minutes' => env('INVENTORY_CART_RESERVATION_MINUTES', 30),

        // Order reservation duration (hours) - for pending orders
        'order_hours' => env('INVENTORY_ORDER_RESERVATION_HOURS', 24),

        // Flash sale reservation duration (minutes)
        'flash_sale_minutes' => env('INVENTORY_FLASH_SALE_RESERVATION_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stock Thresholds
    |--------------------------------------------------------------------------
    |
    | Default thresholds for stock alerts.
    |
    */
    'thresholds' => [
        // Default low stock threshold
        'low_stock' => env('INVENTORY_LOW_STOCK_THRESHOLD', 10),

        // Reorder point
        'reorder_point' => env('INVENTORY_REORDER_POINT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for migrating from the old system.
    |
    */
    'migration' => [
        // Enable dual-write mode (write to both old and new tables)
        'dual_write_enabled' => env('INVENTORY_DUAL_WRITE', false),

        // Legacy table names
        'legacy_warehouse_table' => 'warehouse',
        'legacy_product_warehouse_table' => 'product_warehouse',
    ],

    /*
    |--------------------------------------------------------------------------
    | Receipt Code Prefixes
    |--------------------------------------------------------------------------
    |
    | Prefixes for generating receipt codes.
    |
    */
    'receipt_prefixes' => [
        'import' => 'IMP',
        'export' => 'EXP',
        'transfer' => 'TRF',
        'adjustment' => 'ADJ',
        'return' => 'RTN',
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Configure caching for stock queries.
    |
    */
    'cache' => [
        'enabled' => env('INVENTORY_CACHE_ENABLED', true),
        'ttl_seconds' => env('INVENTORY_CACHE_TTL', 60),
        'prefix' => 'inventory_stock',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure notifications for inventory events.
    |
    */
    'notifications' => [
        // Send email on low stock
        'low_stock_email' => env('INVENTORY_LOW_STOCK_EMAIL', true),

        // Send email on out of stock
        'out_of_stock_email' => env('INVENTORY_OUT_OF_STOCK_EMAIL', true),

        // Admin emails to notify
        'admin_emails' => array_filter(explode(',', env('INVENTORY_ADMIN_EMAILS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | External Sync
    |--------------------------------------------------------------------------
    |
    | Configure external marketplace sync.
    |
    */
    'sync' => [
        // Enable marketplace sync
        'enabled' => env('INVENTORY_SYNC_ENABLED', false),

        // Marketplaces to sync
        'marketplaces' => [
            'shopee' => env('INVENTORY_SYNC_SHOPEE', false),
            'lazada' => env('INVENTORY_SYNC_LAZADA', false),
            'tiktok' => env('INVENTORY_SYNC_TIKTOK', false),
        ],
    ],
];
