<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'tenant_id',
    ];

    public static function value(string $key, mixed $default = null, int $tenantId = 0): mixed
    {
        return static::query()->where('key', $key)->where('tenant_id', $tenantId)->value('value') ?? $default;
    }

    public static function allWithDefaults(array $defaults, int $tenantId = 0): array
    {
        $saved = static::query()->where('tenant_id', $tenantId)->pluck('value', 'key')->all();

        $merged = array_replace($defaults, $saved);

        foreach ($defaults as $key => $defaultValue) {
            if (!array_key_exists($key, $merged)) {
                continue;
            }

            if (is_bool($defaultValue)) {
                $merged[$key] = filter_var($merged[$key], FILTER_VALIDATE_BOOLEAN);
                continue;
            }

            if (is_int($defaultValue)) {
                $merged[$key] = (int) $merged[$key];
                continue;
            }
        }

        return $merged;
    }

    /**
     * Layers config defaults -> platform-wide settings (tenant_id 0) -> per-tenant overrides.
     * Use this for tenant-facing branding (e.g. documents emitted by a specific tenant)
     * so a tenant only overrides what it explicitly saved, inheriting the platform default
     * for everything else.
     */
    public static function allForTenant(array $defaults, int $tenantId): array
    {
        $platformWide = static::allWithDefaults($defaults, 0);

        if ($tenantId <= 0) {
            return $platformWide;
        }

        return static::allWithDefaults($platformWide, $tenantId);
    }

    public static function setValues(array $values, int $tenantId = 0): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key, 'tenant_id' => $tenantId],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value],
            );
        }
    }
}