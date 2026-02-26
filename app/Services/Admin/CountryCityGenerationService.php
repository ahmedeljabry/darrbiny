<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class CountryCityGenerationService
{
    /**
     * @return array<int, string>
     */
    public function generate(string $countryName, ?string $iso2 = null, ?string $currency = null): array
    {
        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI API key is missing. Set OPENAI_API_KEY in environment.');
        }

        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $model = (string) config('services.openai.model', 'gpt-4o-mini');
        $timeout = (int) config('services.openai.timeout', 45);

        $prompt = sprintf(
            "Country name: %s\nISO2: %s\nCurrency: %s\n\nReturn a comprehensive list of major and medium cities in this country.",
            $countryName,
            $iso2 ?: 'unknown',
            $currency ?: 'unknown'
        );

        $response = Http::baseUrl($baseUrl)
            ->withToken($apiKey)
            ->timeout($timeout)
            ->acceptJson()
            ->post('/chat/completions', [
                'model' => $model,
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a geographic data assistant. Return ONLY valid JSON in one of these forms: {"cities":["City A","City B"]} or ["City A","City B"]. No markdown, no extra text.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('OpenAI request failed with status ' . $response->status());
        }

        $content = (string) Arr::get($response->json(), 'choices.0.message.content', '');
        if ($content === '') {
            throw new RuntimeException('OpenAI response did not include content.');
        }

        $cities = $this->extractCities($content);
        if (empty($cities)) {
            throw new RuntimeException('Could not parse city names from OpenAI response.');
        }

        return $cities;
    }

    /**
     * @return array<int, string>
     */
    private function extractCities(string $content): array
    {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->normalizeCities($this->extractArrayFromDecoded($decoded));
        }

        if (preg_match('/\{[\s\S]*\}/u', $content, $objectMatch)) {
            $decodedObject = json_decode($objectMatch[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->normalizeCities($this->extractArrayFromDecoded($decodedObject));
            }
        }

        if (preg_match('/\[[\s\S]*\]/u', $content, $arrayMatch)) {
            $decodedArray = json_decode($arrayMatch[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->normalizeCities($this->extractArrayFromDecoded($decodedArray));
            }
        }

        $lines = preg_split('/\R/u', $content) ?: [];

        return $this->normalizeCities($lines);
    }

    /**
     * @return array<int, mixed>
     */
    private function extractArrayFromDecoded(mixed $decoded): array
    {
        if (is_array($decoded) && array_is_list($decoded)) {
            return $decoded;
        }

        if (is_array($decoded) && isset($decoded['cities']) && is_array($decoded['cities'])) {
            return $decoded['cities'];
        }

        return [];
    }

    /**
     * @param array<int, mixed> $cities
     * @return array<int, string>
     */
    private function normalizeCities(array $cities): array
    {
        return collect($cities)
            ->map(fn ($city) => preg_replace('/^[\-\*\d\.\)\(]+\s*/u', '', trim((string) $city)))
            ->map(fn ($city) => preg_replace('/\s+/u', ' ', (string) $city))
            ->filter(fn ($city) => is_string($city) && $city !== '')
            ->unique(fn ($city) => mb_strtolower((string) $city))
            ->take(300)
            ->values()
            ->all();
    }
}

