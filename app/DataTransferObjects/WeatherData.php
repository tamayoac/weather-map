<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

final readonly class WeatherData
{
    public function __construct(
        public string $city,
        public float $temperature,
        public string $description,
        public CarbonImmutable $timestamp,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            city: $data['name'],
            temperature: (float) $data['main']['temp'],
            description: $data['weather'][0]['description'] ?? 'N/A',
            timestamp: CarbonImmutable::now(),
        );
    }

    public function toArray(string $source): array
    {
        return [
            'city' => $this->city,
            'temperature' => $this->temperature,
            'description' => $this->description,
            'timestamp' => $this->timestamp->toIso8601String(),
            'source' => $source,
        ];
    }
}
