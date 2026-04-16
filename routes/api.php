<?php

declare(strict_types=1);

use App\Http\Controllers\Api\WeatherController;
use Illuminate\Support\Facades\Route;

Route::prefix('weather')->group(function (): void {
    Route::get('/{city}', [WeatherController::class, 'show']);
    Route::get('/{city}/cached', [WeatherController::class, 'cached']);
});
