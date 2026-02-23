<?php

declare(strict_types=1);

namespace App;

class CurlNotificationClient implements NotificationClient
{
    public function notify(string $url, array $event): void
    {
        // TODO: Use Psr\Http\Client\ClientInterface
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($event),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}