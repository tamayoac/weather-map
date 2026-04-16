<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\DataTransferObjects\WeatherData;

interface WeatherServiceInterface
{
    public function getCurrentWeather(string $city): WeatherData;

    public function getCachedWeather(string $city): WeatherData;
}
