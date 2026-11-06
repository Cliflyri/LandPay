<?php

namespace App\Integrations\LandInvest;

class ExportConnection extends Connection
{
    public const SETTING = 'landinvest_export_connection';

    public static function signature(string $secret, string $keyId, string $timestamp, string $nonce, string $body = ''): string
    {
        return hash_hmac('sha256', implode("\n", [
            'landinvest-v1', 'GET', '/webhooks/landinvest/export',
            $keyId, $timestamp, $nonce, hash('sha256', $body),
        ]), $secret);
    }
}
