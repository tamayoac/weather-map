<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\DataTransferObjects\WeatherData;
use App\Exceptions\WeatherServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class OpenWeatherMapService implements WeatherServiceInterface
{
    private const CACHE_TTL_MINUTES = 10;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {}

    public function getCurrentWeather(string $city): WeatherData
    {
        $this->ensureApiKeyConfigured();

        return $this->fetchFromApi($city);
    }

    public function getCachedWeather(string $city): WeatherData
    {
        $this->ensureApiKeyConfigured();

        $cacheKey = $this->buildCacheKey($city);

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): WeatherData => $this->fetchFromApi($city),
        );
    }

    private function fetchFromApi(string $city): WeatherData
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 500, throw: false)
                ->get("{$this->baseUrl}/weather", [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                ]);
        } catch (ConnectionException $e) {
            throw WeatherServiceException::apiFailure(
                'Unable to connect to weather service.',
            );
        } catch (RequestException $e) {
            throw WeatherServiceException::apiFailure(
                $e->response?->json('message', 'Unknown error') ?? $e->getMessage(),
                $e->response?->status() ?? 502,
            );
        }

        return $this->handleResponse($response, $city);
    }

    private function handleResponse(Response $response, string $city): WeatherData
    {
        if ($response->status() === 404) {
            throw WeatherServiceException::cityNotFound($city);
        }

        if ($response->failed()) {
            throw WeatherServiceException::apiFailure(
                $response->json('message', 'Unknown error'),
                $response->status(),
            );
        }

        return WeatherData::fromApiResponse($response->json());
    }

    private function ensureApiKeyConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw WeatherServiceException::apiKeyMissing();
        }
    }

    private function buildCacheKey(string $city): string
    {
        return 'weather:' . strtolower(trim($city));
    }
}
