<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\WeatherServiceException;
use App\Http\Controllers\Controller;
use App\Services\Weather\WeatherServiceInterface;
use Illuminate\Http\JsonResponse;

final class WeatherController extends Controller
{
    public function __construct(
        private readonly WeatherServiceInterface $weatherService,
    ) {}

    public function show(string $city): JsonResponse
    {
        $this->validateCity($city);

        try {
            $weather = $this->weatherService->getCurrentWeather($city);

            return response()->json($weather->toArray('external'));
        } catch (WeatherServiceException $e) {
            return $this->errorResponse($e);
        }
    }

    public function cached(string $city): JsonResponse
    {
        $this->validateCity($city);

        try {
            $weather = $this->weatherService->getCachedWeather($city);

            return response()->json($weather->toArray('cache'));
        } catch (WeatherServiceException $e) {
            return $this->errorResponse($e);
        }
    }

    private function validateCity(string $city): void
    {
        if (trim($city) === '' || strlen($city) > 100) {
            throw new WeatherServiceException(
                'Invalid city name provided.',
                422,
            );
        }
    }

    private function errorResponse(WeatherServiceException $e): JsonResponse
    {
        $statusCode = match (true) {
            $e->getCode() >= 400 && $e->getCode() < 600 => $e->getCode(),
            default => 500,
        };

        return response()->json([
            'error' => $e->getMessage(),
        ], $statusCode);
    }
}
