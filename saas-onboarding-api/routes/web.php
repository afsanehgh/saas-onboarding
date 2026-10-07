<?php

use Illuminate\Support\Facades\Route;

// This service is API-only. The root URL returns a short JSON description
// so anyone opening it in a browser can see the service is up.
Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'description' => 'Onboarding and personalisation API',
        'health' => url('/api/health'),
    ]);
});
