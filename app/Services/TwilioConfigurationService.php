<?php

namespace App\Services;

use App\Models\AppSetting;

class TwilioConfigurationService
{
    public function values(): array
    {
        return [
            'enabled' => AppSetting::valueFor('twilio_sms_enabled', '0') === '1',
            'account_sid' => AppSetting::valueFor('twilio_account_sid', ''),
            'auth_token' => AppSetting::encryptedValueFor('twilio_auth_token'),
            'auth_token_set' => filled(AppSetting::encryptedValueFor('twilio_auth_token')),
            'messaging_service_sid' => AppSetting::valueFor('twilio_messaging_service_sid', ''),
            'disabled_notice' => AppSetting::valueFor('twilio_disabled_client_notice', 'SMS messages are currently disabled by the administrator. Please ensure you are receiving email notices from LandPay.'),
        ];
    }

    public function configured(): bool
    {
        $v = $this->values();
        return filled($v['account_sid']) && filled($v['auth_token']) && filled($v['messaging_service_sid']);
    }
}
