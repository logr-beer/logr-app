<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static array $cache = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::$cache = []);
        static::deleted(fn () => static::$cache = []);
    }

    protected function casts(): array
    {
        return [
            'value' => 'encrypted:array',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, static::$cache)) {
            try {
                static::$cache[$key] = static::where('key', $key)->first()?->value;
            } catch (\Exception) {
                return $default;
            }
        }

        return static::$cache[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache[$key] = $value;
    }

    public static function remove(string $key): void
    {
        static::where('key', $key)->delete();
        unset(static::$cache[$key]);
    }

    public static function clearCache(): void
    {
        static::$cache = [];
    }
}
