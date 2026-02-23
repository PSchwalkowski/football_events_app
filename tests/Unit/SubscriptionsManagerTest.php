<?php

declare(strict_types=1);

namespace Tests;

use App\FileStorage;
use App\NotificationClient;
use App\SubscriptionsManager;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscriptionsManagerTest extends TestCase
{
    private FileStorage & MockObject $fileStorage;
    private SubscriptionsManager $subscriptionsManager;

    protected function setUp(): void
    {
        $this->fileStorage = $this->createMock(FileStorage::class);
    }

    public function testShouldSubscribeForMatch(): void
    {
        $this->subscriptionsManager = new SubscriptionsManager($this->fileStorage);

        $this->fileStorage->expects(self::once())
            ->method('save')
            ->with([
                'url' => 'http://google.com/webhook/events',
                'match_id' => '1234',
            ]);

        $this->subscriptionsManager->subscribe('http://google.com/webhook/events', '1234');
    }

    #[TestWith(['invalid_url'])]
    #[TestWith(['https://google webhook/events'])]
    #[TestWith(['google.com/webhook/events'])]
    public function testShouldFailWhenInvalidUrlGiven(string $url): void
    {
        $this->subscriptionsManager = new SubscriptionsManager($this->fileStorage);

        $this->fileStorage->expects(self::never())
            ->method('save');

        self::expectExceptionMessage('Invalid URL provided');

        $this->subscriptionsManager->subscribe($url, '1234');
    }

    public function testShouldFailWhenInvalidMatchIDGiven(): void
    {
        $this->subscriptionsManager = new SubscriptionsManager($this->fileStorage);

        $this->fileStorage->expects(self::never())
            ->method('save');

        self::expectExceptionMessage('Invalid match_id provided');

        $this->subscriptionsManager->subscribe('http://google.com/webhook/events', '');
    }

    public function testShouldNotifyAboutEvent(): void
    {
        $notificationClient = $this->createMock(NotificationClient::class);
        $this->subscriptionsManager = new SubscriptionsManager($this->fileStorage, $notificationClient);

        $this->fileStorage->expects(self::once())
            ->method('getAll')
            ->willReturn([
                [
                    'url' => 'http://google.com/webhook/events',
                    'match_id' => '1234',
                ]
            ]);

        $event = [
            'type' => 'goal',
            'timestamp' => time(),
            'data' => [
                'type' => 'goal',
                'player' => 'Jane Smith',
                'minute' => 23,
                'second' => 34,
                'team_id' => 'team_a',
                'match_id' => '1234',
                'assisting_player' => 'John Smith',
            ],
        ];

        $notificationClient->expects(self::once())
            ->method('notify')
            ->with('http://google.com/webhook/events', $event);

        $this->subscriptionsManager->notifyAboutEvent($event);
    }
}