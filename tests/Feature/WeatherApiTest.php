<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    private const API_BASE = 'https://api.openweathermap.org/data/2.5/*';

    private function fakeApiKey(): void
    {
        config(['services.openweathermap.api_key' => 'test-api-key']);
    }

    private function fakeSuccessResponse(string $city = 'London'): array
    {
        return [
            'name' => $city,
            'main' => ['temp' => 18.5],
            'weather' => [['description' => 'clear sky']],
        ];
    }

    public function test_get_weather_returns_external_data(): void
    {
        $this->fakeApiKey();

        Http::fake([
            self::API_BASE => Http::response($this->fakeSuccessResponse()),
        ]);

        $response = $this->getJson('/api/weather/London');

        $response->assertOk()
            ->assertJsonStructure(['city', 'temperature', 'description', 'timestamp', 'source'])
            ->assertJson([
                'city' => 'London',
                'temperature' => 18.5,
                'description' => 'clear sky',
                'source' => 'external',
            ]);
    }

    public function test_get_cached_weather_returns_cache_source(): void
    {
        $this->fakeApiKey();
        Cache::flush();

        Http::fake([
            self::API_BASE => Http::response($this->fakeSuccessResponse()),
        ]);

        $response = $this->getJson('/api/weather/London/cached');

        $response->assertOk()
            ->assertJson(['source' => 'cache', 'city' => 'London']);
    }

    public function test_cached_endpoint_serves_from_cache_on_second_call(): void
    {
        $this->fakeApiKey();
        Cache::flush();

        Http::fake([
            self::API_BASE => Http::sequence()
                ->push($this->fakeSuccessResponse())
                ->push($this->fakeSuccessResponse('Paris')),
        ]);

        $this->getJson('/api/weather/London/cached')->assertOk();
        $second = $this->getJson('/api/weather/London/cached');

        $second->assertOk()
            ->assertJson(['city' => 'London', 'source' => 'cache']);

        Http::assertSentCount(1);
    }

    public function test_weather_endpoint_returns_404_for_unknown_city(): void
    {
        $this->fakeApiKey();

        Http::fake([
            self::API_BASE => Http::response(['message' => 'city not found'], 404),
        ]);

        $response = $this->getJson('/api/weather/NonExistentCityXYZ');

        $response->assertNotFound()
            ->assertJson(['error' => "City 'NonExistentCityXYZ' not found."]);
    }

    public function test_weather_endpoint_returns_error_on_api_failure(): void
    {
        $this->fakeApiKey();

        Http::fake([
            self::API_BASE => Http::response(['message' => 'Internal error'], 500),
        ]);

        $response = $this->getJson('/api/weather/London');

        $response->assertStatus(500)
            ->assertJsonStructure(['error']);
    }

    public function test_weather_endpoint_returns_error_when_api_key_missing(): void
    {
        config(['services.openweathermap.api_key' => '']);

        $response = $this->getJson('/api/weather/London');

        $response->assertStatus(500)
            ->assertJson(['error' => 'OpenWeatherMap API key is not configured.']);
    }

    public function test_weather_endpoint_handles_connection_failure(): void
    {
        $this->fakeApiKey();

        Http::fake([
            self::API_BASE => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection timed out'),
        ]);

        $response = $this->getJson('/api/weather/London');

        $response->assertStatus(502)
            ->assertJsonStructure(['error']);
    }
}
