<?php

namespace App\Services;

use RuntimeException;
use Twilio\Rest\Client;

class TwilioSmsTransport
{
    public function __construct(private readonly TwilioConfigurationService $configuration) {}

    public function send(string $to, string $body): array
    {
        $config = $this->configuration->values();
        if (! $this->configuration->configured()) throw new RuntimeException('Twilio SMS credentials are incomplete.');
        $message = (new Client($config['account_sid'], $config['auth_token']))->messages->create($to, [
            'messagingServiceSid' => $config['messaging_service_sid'],
            'body' => $body,
        ]);
        return ['sid' => $message->sid, 'status' => (string) $message->status];
    }
}
