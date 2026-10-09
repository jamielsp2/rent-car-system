<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value settings stored in the shared `settings` table.
 */
#[Table('settings')]
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::cached()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings.all');
    }

    protected static function cached(): array
    {
        return Cache::remember('settings.all', 600, fn () => static::query()->pluck('value', 'key')->all());
    }
}
