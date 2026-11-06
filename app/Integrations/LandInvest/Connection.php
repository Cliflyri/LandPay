<?php

namespace App\Integrations\LandInvest;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class Connection
{
    public const SETTING = 'landinvest_connection';

    public function lockName(): string
    {
        return str_replace('_', '-', static::SETTING);
    }

    public function state(): array
    {
        $raw = AppSetting::encryptedValueFor(static::SETTING);
        return $raw ? json_decode($raw, true, 32, JSON_THROW_ON_ERROR) : ['enabled' => false];
    }

    public function save(array $state): void
    {
        AppSetting::putEncrypted(static::SETTING, json_encode($state, JSON_THROW_ON_ERROR));
    }

    public function cache()
    {
        $store = config('cache.default');
        if (! in_array(config("cache.stores.$store.driver"), ['file', 'database', 'redis'], true)) {
            throw new \RuntimeException('Persistent integration cache is required.');
        }
        return Cache::store($store);
    }

    public static function signature(string $secret, string $keyId, string $timestamp, string $nonce, string $body): string
    {
        return hash_hmac('sha256', implode("\n", [
            'landinvest-v1', 'POST', '/webhooks/landinvest/repayments',
            $keyId, $timestamp, $nonce, hash('sha256', $body),
        ]), $secret);
    }

    public function metadata(): array
    {
        $state = $this->state();
        return [
            'enabled' => $state['enabled'] ?? false,
            'key_id' => $state['current']['id'] ?? null,
            'created_at' => $state['current']['created_at'] ?? null,
            'previous_expires_at' => isset($state['previous']['expires']) ? gmdate('c', $state['previous']['expires']) : null,
            'last_received_at' => $state['last_received_at'] ?? null,
        ];
    }
}
