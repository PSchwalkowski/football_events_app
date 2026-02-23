<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use Throwable;

readonly class SubscriptionsManager
{
    private ?NotificationClient $notificationClient;

    public function __construct(
        private FileStorage $storage,
        ?NotificationClient $notificationClient = null,
    )
    {
        $this->notificationClient = $notificationClient ?? new CurlNotificationClient();
    }

    public function subscribe(string $url, string $match_id): void
    {
        $url = filter_var($url, FILTER_VALIDATE_URL);
        if (!$url) {
            throw new InvalidArgumentException('Invalid URL provided');
        }

        if (empty($match_id)) {
            throw new InvalidArgumentException('Invalid match_id provided');
        }

        $this->storage->save([
            'url' => $url,
            'match_id' => $match_id,
        ]);
    }

    // TODO: Use queues and retry options
    // TODO: Use objects
    public function notifyAboutEvent(array $event): void
    {
        $subscriptions = $this->storage->getAll();
        $subscriptions = array_filter(
            $subscriptions,
            fn (array $subscriber) => $subscriber['match_id'] === $event['data']['match_id']
        );

        if (empty($subscriptions)) {
            return;
        }

        foreach ($subscriptions as $subscriber) {
            try {
                $this->notificationClient->notify($subscriber['url'], $event);
            } catch (Throwable $e) {
                // TODO: log error
            }
        }
    }
}