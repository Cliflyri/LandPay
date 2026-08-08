<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SmtpConfigurationService
{
    public function apply(): bool
    {
        if (! Schema::hasTable('app_settings') || AppSetting::valueFor('smtp_enabled', '0') !== '1') {
            return false;
        }
        $host = AppSetting::valueFor('smtp_host');
        $fromAddress = AppSetting::valueFor('smtp_from_address');
        if (blank($host) || blank($fromAddress)) {
            return false;
        }
        $security = AppSetting::valueFor('smtp_security', 'tls');
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $security === 'ssl' ? 'smtps' : null,
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) AppSetting::valueFor('smtp_port', $security === 'ssl' ? '465' : '587'),
            'mail.mailers.smtp.username' => AppSetting::valueFor('smtp_username'),
            'mail.mailers.smtp.password' => AppSetting::encryptedValueFor('smtp_password'),
            'mail.mailers.smtp.timeout' => 20,
            'mail.mailers.smtp.local_domain' => AppSetting::valueFor('smtp_ehlo_domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST),
            'mail.from.address' => $fromAddress,
            'mail.from.name' => AppSetting::valueFor('smtp_from_name', config('app.name', 'LandPay')),
        ]);
        Mail::purge('smtp');
        return true;
    }

    /** @return array<string, string> */
    public function values(): array
    {
        return [
            'smtp_enabled' => AppSetting::valueFor('smtp_enabled', '0'),
            'smtp_host' => AppSetting::valueFor('smtp_host', ''),
            'smtp_port' => AppSetting::valueFor('smtp_port', '587'),
            'smtp_security' => AppSetting::valueFor('smtp_security', 'tls'),
            'smtp_username' => AppSetting::valueFor('smtp_username', ''),
            'smtp_password_set' => AppSetting::encryptedValueFor('smtp_password') === null ? '0' : '1',
            'smtp_from_address' => AppSetting::valueFor('smtp_from_address', ''),
            'smtp_from_name' => AppSetting::valueFor('smtp_from_name', config('app.name', 'LandPay')),
            'smtp_ehlo_domain' => AppSetting::valueFor('smtp_ehlo_domain', ''),
        ];
    }
}
