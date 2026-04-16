# Weather Map API

A Laravel API that fetches real-time weather data from the OpenWeatherMap API, with a caching layer that serves cached responses for 10 minutes.

## Requirements

- PHP 8.2+
- Composer
- An [OpenWeatherMap](https://openweathermap.org/api) API key (free tier works)

## Setup

```bash
git clone <repository-url>
cd weather-map
composer install
cp .env.example .env
php artisan key:generate
```

Add your OpenWeatherMap API key to `.env`:

```
OPENWEATHERMAP_API_KEY=your_api_key_here
```

Run the development server:

```bash
php artisan serve
```

## API Endpoints

### `GET /api/weather/{city}`

Fetches real-time weather data directly from OpenWeatherMap.

**Example:** `GET /api/weather/London`

```json
{
    "city": "London",
    "temperature": 18.5,
    "description": "clear sky",
    "timestamp": "2026-04-16T12:00:00+00:00",
    "source": "external"
}
```

### `GET /api/weather/{city}/cached`

Returns the same weather data, but serves from cache when available. Results are cached for 10 minutes.

**Example:** `GET /api/weather/London/cached`

```json
{
    "city": "London",
    "temperature": 18.5,
    "description": "clear sky",
    "timestamp": "2026-04-16T12:00:00+00:00",
    "source": "cache"
}
```

### Error Responses

All errors return a consistent JSON structure:

```json
{
    "error": "City 'InvalidCity' not found."
}
```

| Status | Meaning |
|--------|---------|
| 404 | City not found |
| 422 | Invalid city name |
| 500 | API key missing or internal error |
| 502 | External API unreachable |

## Running Tests

```bash
php artisan test
```

Or with PHPUnit directly:

```bash
./vendor/bin/phpunit
```

## Approach

**Architecture:** The project uses a service-oriented architecture with clear separation of concerns:

- **`WeatherServiceInterface`** — Defines the contract for weather data retrieval, making the implementation swappable (e.g., for testing or switching providers).
- **`OpenWeatherMapService`** — Concrete implementation that handles HTTP communication with the OpenWeatherMap API, including retry logic and caching via Laravel's `Cache` facade.
- **`WeatherData` DTO** — An immutable read-only data transfer object that decouples the internal data representation from the external API response shape.
- **`WeatherServiceException`** — A domain-specific exception with static factory methods for clean, consistent error creation.
- **`WeatherController`** — Thin controller that delegates to the service layer and maps exceptions to appropriate HTTP responses.

**Caching:** The `/cached` endpoint uses `Cache::remember()` with a 10-minute TTL. The cache key is normalized (lowercased, trimmed) so that `London`, `london`, and `  london  ` all hit the same cache entry.

**Testing:** Tests use Laravel's `Http::fake()` to mock external API calls, verifying both happy paths and error scenarios without hitting the real API. The test suite covers: successful responses, cache behavior (verifying only one HTTP call is made), 404 handling, server errors, missing API keys, and connection failures.
