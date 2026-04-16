<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Weather\OpenWeatherMapService;
use App\Services\Weather\WeatherServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WeatherServiceInterface::class, function (): OpenWeatherMapService {
            return new OpenWeatherMapService(
                apiKey: (string) config('services.openweathermap.api_key'),
                baseUrl: (string) config('services.openweathermap.base_url'),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
