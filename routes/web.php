<?php

use Illuminate\Support\Facades\Route;

/*
| The frontend is a standalone Nuxt application (see resources/). Laravel
| serves only the JSON API defined in routes/api.php. A health check is
| exposed at /up via bootstrap/app.php.
*/

Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url('/api/invoices'),
]));
