<?php

namespace Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class WeatherTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Get the current weather for a given city. Returns temperature, conditions, wind speed, and humidity.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $location = Http::get('https://geocoding-api.open-meteo.com/v1/search', [
            'name' => $request['city'],
            'count' => 1,
        ])->json();

        if (empty($location['results'])) {
            return "Could not find a location named \"{$request['city']}\".";
        }

        $place = $location['results'][0];

        $units = $request['units'] ?? 'celsius';

        $weather = Http::get('https://api.open-meteo.com/v1/forecast', [
            'latitude' => $place['latitude'],
            'longitude' => $place['longitude'],
            'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m',
            'temperature_unit' => $units === 'fahrenheit' ? 'fahrenheit' : 'celsius',
            'wind_speed_unit' => 'mph',
        ])->json();

        $current = $weather['current'];
        $tempUnit = $units === 'fahrenheit' ? '°F' : '°C';

        return implode("\n", [
            sprintf('Weather for %s, %s:', $place['name'], $place['country'] ?? $place['admin1'] ?? ''),
            sprintf('Temperature: %s%s (feels like %s%s)', $current['temperature_2m'], $tempUnit, $current['apparent_temperature'], $tempUnit),
            sprintf('Humidity: %s%%', $current['relative_humidity_2m']),
            sprintf('Wind: %s mph', $current['wind_speed_10m']),
            sprintf('Conditions: %s', $this->describeWeatherCode($current['weather_code'])),
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'city' => $schema->string()->description('The name of the city to get weather for.')->required(),
            'units' => $schema->string()->enum(['celsius', 'fahrenheit'])->description('Temperature units.'),
        ];
    }

    /**
     * Convert a WMO weather code to a human-readable description.
     */
    protected function describeWeatherCode(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear sky',
            $code <= 3 => 'Partly cloudy',
            $code <= 49 => 'Foggy',
            $code <= 59 => 'Drizzle',
            $code <= 69 => 'Rain',
            $code <= 79 => 'Snow',
            $code <= 84 => 'Rain showers',
            $code <= 86 => 'Snow showers',
            $code === 95 => 'Thunderstorm',
            $code <= 99 => 'Thunderstorm with hail',
            default => 'Unknown',
        };
    }
}
