<?php

declare(strict_types=1);

return [
    'title' => 'RackList',
    'description' => 'Sync your products and prices with the RackList directory',
    'settings_saved' => 'RackList settings saved.',
    'reference_refreshed' => 'RackList categories and spec keys reloaded.',
    'mapping_saved' => 'Mapping saved. A sync will be triggered.',
    'test_success' => 'Test passed: :sent product(s) checked, :rejected rejected. Nothing was saved on RackList.',
    'sync_success' => 'Catalogue synced: :sent product(s) sent, :rejected rejected.',
    'sync_failed' => 'Sync failed [:code]: :message',

    'errors' => [
        'too_many_products' => ':count products to send, RackList accepts :max at most. Exclude products or groups.',
        'empty_catalogue' => 'No product to send: an empty sync would remove every offer from RackList. Map at least one group to a category.',
        'invalid_key_format' => 'The key must start with "rk_" followed by 64 hexadecimal characters (Dashboard > Integrations on RackList).',
        'reference_unavailable' => 'Unable to load RackList categories: :message',
    ],

    'connection' => [
        'help' => 'Your full catalogue is sent to RackList. Products missing from a sync are removed from your listing.',
        'api_key' => 'Sync key',
        'api_key_help' => 'Created in Dashboard > Integrations on RackList. Leave empty to keep the current key.',
        'auto_sync' => 'Sync automatically whenever a product, price or group changes',
        'remove_key' => 'Remove the saved key',
        'key_configured' => 'Saved key: :key',
        'key_missing' => 'No key saved.',
    ],

    'actions' => [
        'test' => 'Test (dry run)',
        'sync' => 'Sync now',
        'sync_confirm' => 'The RackList catalogue will be replaced by the products listed below. Continue?',
        'refresh_reference' => 'Reload categories',
        'sync_warning' => 'A full sync also runs every night at 04:00.',
    ],

    'report' => [
        'title' => 'Last sync',
        'dry_run_title' => 'Test result',
        'sent' => ':count product sent|:count products sent',
        'success' => 'Succeeded',
        'failed' => 'Failed',
        'triggers' => [
            'manual' => 'Manual',
            'auto' => 'Automatic',
            'cron' => 'Scheduled',
        ],
        'counters' => [
            'products_created' => 'Created',
            'products_updated' => 'Updated',
            'products_deactivated' => 'Deactivated',
            'products_rejected' => 'Rejected',
            'conflicts_detected' => 'Conflicts',
        ],
    ],

    'groups' => [
        'title' => 'Group mapping',
        'help' => 'Pick the RackList category of each group. Its products inherit it; a subgroup inherits from its parent group.',
        'group' => 'Group',
        'products' => 'Products',
        'category' => 'RackList category',
        'inactive' => 'inactive',
        'inherit_parent' => 'Inherit from parent group',
        'not_listed' => 'Not listed on RackList',
        'empty' => 'No group.',
        'save' => 'Save mapping',
    ],

    'products' => [
        'title' => 'Catalogue preview',
        'help' => ':count / :max products will be sent. Prices in :currency (default store currency).',
        'product' => 'Product',
        'category' => 'Category',
        'price' => 'Price',
        'specs' => 'Specs',
        'month' => 'month',
        'tiers' => 'billing cycle(s)',
        'configure' => 'Configure',
        'reasons' => [
            'excluded' => 'Manually excluded',
            'no_category' => 'No RackList category',
            'no_pricing' => 'No price in the default currency',
            'inactive_group' => 'Inactive or missing group',
        ],
    ],

    'product' => [
        'title' => 'RackList: :name',
        'back' => 'Back to RackList',
        'category' => 'RackList category',
        'inherit' => 'Inherit from group (:category)',
        'excluded' => 'Do not list this product on RackList',
        'specs' => 'Specs',
        'specs_auto' => 'Greyed values are read from the :type configuration. Fill a field to override it.',
        'specs_manual' => 'This product type provides no automatic specs: fill them here.',
        'specs_need_category' => 'Pick a category first to show the accepted spec keys.',
    ],
];
