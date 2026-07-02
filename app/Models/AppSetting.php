<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function value(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function allWithDefaults(array $defaults): array
    {
        $saved = static::query()->pluck('value', 'key')->all();

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

    public static function setValues(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value],
            );
        }
    }
}