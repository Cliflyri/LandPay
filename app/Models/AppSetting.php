<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class AppSetting extends Model
{
    protected $guarded = ['id'];

    public static function valueFor(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }
    public static function encryptedValueFor(string $key): ?string
    {
        $value = static::valueFor($key);
        if (blank($value)) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return null;
        }
    }

    public static function putEncrypted(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => Crypt::encryptString($value)]);
    }


    /** @param array<string, string|null> $settings */
    public static function putMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
