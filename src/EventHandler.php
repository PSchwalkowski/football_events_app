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
        if (!isset($data['type'])) {
            throw new InvalidArgumentException('Event type is required');
        }
        
        $event = [
            'type' => $data['type'],
            'timestamp' => time(),
            'data' => $data
        ];
        
        $this->storage->save($event);
        
        // Update statistics for foul events
        if ($data['type'] === 'foul') {
            $this->validate($data, [
                'match_id',
                'team_id',
            ]);
            
            $this->statisticsManager->updateTeamStatistics(
                $data['match_id'],
                $data['team_id'],
                'fouls'
            );
        } elseif ($data['type'] === 'goal') {
            $this->validate($data, [
                'player',
                'minute',
                'second',
                'team_id',
                'match_id',
                'assisting_player',
            ]);

            $this->statisticsManager->updateTeamStatistics(
                $data['match_id'],
                $data['team_id'],
                'goals',
            );
        }
        
        return [
            'status' => 'success',
            'message' => 'Event saved successfully',
            'event' => $event
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
}