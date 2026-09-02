<?php

// Read via config('mcberto.admin.*'), never env() directly in application
// code — env() returns null once `config:cache` has run, since Laravel
// then skips loading .env entirely. Config files are the only place
// env() is safe to call, because `config:cache` captures their resolved
// values at cache-build time.
return [
    'operations' => [
        'cycle_start_day' => (int) env('MCBERTO_OPERATION_CYCLE_START_DAY', 14),
    ],

    'admin' => [
        'name' => env('ADMIN_NAME', 'Bertony Effa'),
        'email' => env('ADMIN_EMAIL', 'owner@mcberto.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],
];
