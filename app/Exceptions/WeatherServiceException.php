<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class WeatherServiceException extends RuntimeException
{
    public static function cityNotFound(string $city): self
    {
        return new self("City '{$city}' not found.", 404);
    }

    public static function apiKeyMissing(): self
    {
        return new self('OpenWeatherMap API key is not configured.', 500);
    }

    public static function apiFailure(string $message, int $code = 502): self
    {
        return new self("Weather API error: {$message}", $code);
    }
}
