<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Livewire JavaScript Asset
    |--------------------------------------------------------------------------
    |
    | The online deployment was not resolving Livewire's dynamic JS endpoint
    | (/livewire/livewire.js). Use the published static asset instead so the
    | POS and dashboard can hydrate reliably behind shared hosting rewrites.
    |
    */

    'asset_url' => env('LIVEWIRE_ASSET_URL', '/vendor/livewire/livewire.js'),

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#dc2626',
    ],
];
