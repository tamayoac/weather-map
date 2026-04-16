<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Weather;

use App\DataTransferObjects\WeatherData;
use App\Exceptions\WeatherServiceException;
use App\Services\Weather\OpenWeatherMapService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenWeatherMapServiceTest extends TestCase
{
    private OpenWeatherMapService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new OpenWeatherMapService(
            apiKey: 'test-key',
            baseUrl: 'https://api.openweathermap.org/data/2.5',
        );
    }

    private function fakeSuccessResponse(): array
    {
        return [
            'name' => 'Tokyo',
            'main' => ['temp' => 22.3],
            'weather' => [['description' => 'broken clouds']],
        ];
    }

    public function test_get_current_weather_returns_weather_data(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response($this->fakeSuccessResponse()),
        ]);

        $result = $this->service->getCurrentWeather('Tokyo');

        $this->assertInstanceOf(WeatherData::class, $result);
        $this->assertSame('Tokyo', $result->city);
        $this->assertSame(22.3, $result->temperature);
        $this->assertSame('broken clouds', $result->description);
    }

    public function test_get_cached_weather_caches_result(): void
    {
        Cache::flush();

        Http::fake([
            'api.openweathermap.org/*' => Http::response($this->fakeSuccessResponse()),
        ]);

        $first = $this->service->getCachedWeather('Tokyo');
        $second = $this->service->getCachedWeather('Tokyo');

        $this->assertSame($first->city, $second->city);
        Http::assertSentCount(1);
    }

    public function test_throws_exception_when_api_key_is_empty(): void
    {
        $service = new OpenWeatherMapService(apiKey: '', baseUrl: 'https://example.com');

        $this->expectException(WeatherServiceException::class);
        $this->expectExceptionMessage('API key is not configured');

        $service->getCurrentWeather('London');
    }

    public function test_throws_exception_for_unknown_city(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response(['message' => 'city not found'], 404),
        ]);

        $this->expectException(WeatherServiceException::class);
        $this->expectExceptionMessage("City 'FakeCity' not found");

        $this->service->getCurrentWeather('FakeCity');
    }

    public function test_throws_exception_on_server_error(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response(['message' => 'Internal error'], 500),
        ]);

        $this->expectException(WeatherServiceException::class);

        $this->service->getCurrentWeather('London');
    }
}
