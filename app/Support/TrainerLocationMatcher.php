<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\TrainerProfile;
use App\Models\UserRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class TrainerLocationMatcher
{
    private const BASE_FIELDS = ['country_id', 'area_level_1'];

    private const OPTIONAL_FIELDS = ['area_level_2', 'area_level_3'];

    public static function profileValue(?TrainerProfile $profile, string $field): ?string
    {
        if (! $profile) {
            return null;
        }

        $value = $profile->getAttribute($field);
        if (
            $profile->pending_approval
            && is_array($profile->pending_changes)
            && array_key_exists($field, $profile->pending_changes)
        ) {
            $value = $profile->pending_changes[$field];
        }

        return is_scalar($value) ? (string) $value : null;
    }

    public static function hasBaseLocation(?TrainerProfile $profile): bool
    {
        return self::normalize(self::profileValue($profile, 'country_id')) !== null
            && self::normalize(self::profileValue($profile, 'area_level_1')) !== null;
    }

    public static function matchesRequest(
        TrainerProfile $profile,
        UserRequest $request,
        bool $requireBaseLocation = true
    ): bool {
        foreach (self::BASE_FIELDS as $field) {
            $profileValue = self::normalize(self::profileValue($profile, $field));
            $requestValue = self::normalize($request->getAttribute($field));

            if ($requireBaseLocation && ($profileValue === null || $requestValue === null)) {
                return false;
            }

            if ($profileValue !== null && $requestValue !== null && $profileValue !== $requestValue) {
                return false;
            }
        }

        foreach (self::OPTIONAL_FIELDS as $field) {
            $profileValue = self::normalize(self::profileValue($profile, $field));
            $requestValue = self::normalize($request->getAttribute($field));

            if ($profileValue !== null && $requestValue !== null && $profileValue !== $requestValue) {
                return false;
            }
        }

        return true;
    }

    public static function applyOpenRequestScope(Builder $query, TrainerProfile $profile): Builder
    {
        if (! self::hasBaseLocation($profile)) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereNull('trainer_id')
            ->whereIn('status', [
                UserRequest::STATUS_PENDING_PAYMENT,
                UserRequest::STATUS_AWAITING_OFFERS,
            ]);

        foreach (self::BASE_FIELDS as $field) {
            self::whereNormalizedEquals($query, $field, self::profileValue($profile, $field));
        }

        foreach (self::OPTIONAL_FIELDS as $field) {
            self::whereBlankOrNormalizedEquals($query, $field, self::profileValue($profile, $field));
        }

        return $query;
    }

    public static function applyEligibleTrainerProfilesScope(Builder $query, UserRequest $request): Builder
    {
        $query->whereNotNull('verified_at');

        foreach (self::BASE_FIELDS as $field) {
            self::whereNormalizedEquals($query, $field, $request->getAttribute($field));
        }

        foreach (self::OPTIONAL_FIELDS as $field) {
            self::whereBlankOrNormalizedEquals($query, $field, $request->getAttribute($field));
        }

        return $query;
    }

    public static function normalize(mixed $value): ?string
    {
        if ($value === null || ! is_scalar($value)) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        return Str::lower($normalized);
    }

    private static function whereNormalizedEquals(Builder $query, string $column, mixed $value): void
    {
        $normalized = self::normalize($value);
        if ($normalized === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($column === 'country_id') {
            $query->where($column, (string) $value);

            return;
        }

        $wrappedColumn = self::wrapColumn($query, $column);
        $query->whereRaw("LOWER(TRIM(COALESCE({$wrappedColumn}, ''))) = ?", [$normalized]);
    }

    private static function whereBlankOrNormalizedEquals(Builder $query, string $column, mixed $value): void
    {
        $normalized = self::normalize($value);
        if ($normalized === null) {
            return;
        }

        $wrappedColumn = self::wrapColumn($query, $column);
        $query->where(function (Builder $nestedQuery) use ($column, $normalized, $wrappedColumn): void {
            $nestedQuery->whereNull($column)
                ->orWhereRaw("TRIM(COALESCE({$wrappedColumn}, '')) = ''")
                ->orWhereRaw("LOWER(TRIM(COALESCE({$wrappedColumn}, ''))) = ?", [$normalized]);
        });
    }

    private static function wrapColumn(Builder $query, string $column): string
    {
        return $query->getQuery()->getGrammar()->wrap($column);
    }
}
