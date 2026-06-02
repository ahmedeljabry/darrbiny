<?php

declare(strict_types=1);

namespace App\Support;

final class NotificationPayloadSanitizer
{
    private const UUID_PATTERN = '/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i';
    private const UUID_EXACT_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public static function withoutUuids(array $payload): array
    {
        $displayNumber = self::displayNumber($payload);

        return self::sanitizeArray($payload, $displayNumber);
    }

    private static function sanitizeArray(array $payload, ?string $displayNumber): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            if ($value === null) {
                $sanitized[$key] = null;

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $displayNumber);

                continue;
            }

            if (is_string($value) && self::containsUuid($value)) {
                if (self::isIdentifierKey((string) $key) && self::isUuid($value)) {
                    continue;
                }

                $sanitized[$key] = self::replaceUuidText($value, $displayNumber);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private static function displayNumber(array $payload): ?string
    {
        foreach (['display_order_number', 'course_number', 'request_number', 'order_number', 'formatted_order_number'] as $key) {
            $value = $payload[$key] ?? null;

            if (is_scalar($value) && (string) $value !== '' && ! self::containsUuid((string) $value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private static function isIdentifierKey(string $key): bool
    {
        return $key === 'id'
            || str_ends_with($key, '_id')
            || str_ends_with($key, '_uuid')
            || str_ends_with($key, '_token');
    }

    private static function isUuid(string $value): bool
    {
        return preg_match(self::UUID_EXACT_PATTERN, $value) === 1;
    }

    private static function containsUuid(string $value): bool
    {
        return preg_match(self::UUID_PATTERN, $value) === 1;
    }

    private static function replaceUuidText(string $value, ?string $displayNumber): string
    {
        $replacement = $displayNumber ?? '';

        return trim((string) preg_replace(self::UUID_PATTERN, $replacement, $value));
    }
}
