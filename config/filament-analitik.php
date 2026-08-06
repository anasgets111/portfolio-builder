<?php

return [
    // Disabling this stops both page-view and interaction collection.
    'enabled' => env('ANALYTICS_ENABLED', true),

    // This schema remains compatible with kholil/filament-analitik's model.
    'table_name' => 'analytics_page_views',

    // Trusted proxy country codes avoid an external geolocation request.
    'country_headers' => [
        'CF-IPCountry',
        'X-Vercel-IP-Country',
    ],
];
