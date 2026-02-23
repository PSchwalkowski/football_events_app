<?php

namespace App;

use InvalidArgumentException;

class EventHandler
{
    private FileStorage $storage;
    private StatisticsManager $statisticsManager;
    
    public function __construct(string $storagePath, ?StatisticsManager $statisticsManager = null)
    {
        $this->storage = new FileStorage($storagePath);
        $this->statisticsManager = $statisticsManager ?? new StatisticsManager(__DIR__ . '/../storage/statistics.txt');
    }
    
    public function handleEvent(array $data): array
    {
        $this->validateEvent($data);
        
        $event = [
            'type' => $data['type'],
            'timestamp' => time(),
            'data' => $data,
        ];
        
        $this->storage->save($event);
        $this->updateStatistics($event);
        
        return [
            'status' => 'success',
            'message' => 'Event saved successfully',
            'event' => $event,
        ];
    }

    private function validate(array $data, array $requiredFields): void
    {
        $hasValidationError = false;
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $hasValidationError = true;
                break;
            }
        }

        if ($hasValidationError) {
            throw new InvalidArgumentException(sprintf(
                'Fields %s are required for this event',
                implode(', ', $requiredFields)
            ));
        }
    }

    private function validateEvent(array $data): void
    {
        $this->validate($data, ['type']);

        if (!in_array($data['type'], ['foul', 'goal'])) {
            throw new InvalidArgumentException('Invalid event type');
        }

        switch ($data['type']) {
            case 'foul':
                $this->validate($data, [
                    'player',
                    'affected_player',
                    'minute',
                    'second',
                    'team_id',
                    'match_id',
                ]);
                break;

            case 'goal':
                $this->validate($data, [
                    'player',
                    'minute',
                    'second',
                    'team_id',
                    'match_id',
                    'assisting_player',
                ]);
                break;

            default:
                break;
        }
    }

    // TODO: Add more filters
    // TODO: Use some kind of pagination
    public function getEvents(?string $type = null): array
    {
        $events = $this->storage->getAll();

        // TODO: This is not the best place for filtering results
        if ($type !== null) {
            $events = array_filter($events, function ($event) use ($type) {
                return $event['type'] === $type;
            });
        }

        return [
            'status' => 'success',
            'events' => array_values($events),
        ];
    }

    // TODO: This should be processed async via queues
    private function updateStatistics(array $event): void
    {
        $eventType = match($event['type']) {
            'goal' => 'goals',
            'foul' => 'fouls',
        };

        $this->statisticsManager->addTeamStatistics($event['match_id'], $event['team_id'], $eventType);
    }
}