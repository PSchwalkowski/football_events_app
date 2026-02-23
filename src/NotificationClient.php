<?php

declare(strict_types=1);

namespace App;

interface NotificationClient
{
    public function notify(string $url, array $event): void;
}