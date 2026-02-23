<?php

namespace App;

class StatisticsManager
{
    private FileStorage $storage;
    private string $statsFile;
    
    public function __construct(string $statsFile = '../storage/statistics.txt')
    {
        $this->storage = new FileStorage($statsFile);
        $this->statsFile = $statsFile;
        
        $directory = dirname($statsFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
    
    public function addTeamStatistics(string $matchId, string $teamId, string $statType, int $value = 1): void
    {
        $stats = [
            'match_id' => $matchId,
            'team_id' => $teamId,
            'stat_type' => $statType,
            'value' => $value,
        ];

        file_put_contents($this->statsFile, json_encode($stats) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    public function getTeamStatistics(string $matchId, string $teamId): array
    {
        $stats = $this->getStatistics();
        return $stats[$matchId][$teamId] ?? [];
    }
    
    public function getMatchStatistics(string $matchId): array
    {
        $stats = $this->getStatistics();
        return $stats[$matchId] ?? [];
    }

    private function getStatistics(): array
    {
        if (!file_exists($this->statsFile)) {
            return [];
        }

        $aggregated = [];
        $handle = fopen($this->statsFile, 'r');

        while (($line = fgets($handle)) !== false) {
            $entry = json_decode(trim($line), true);
            if (!$entry) {
                continue;
            }

            $currentValue = $aggregated[$entry['match_id']][$entry['team_id']][$entry['stat_type']] ?? 0;
            $aggregated[$entry['match_id']][$entry['team_id']][$entry['stat_type']] = $currentValue + $entry['value'];
        }

        fclose($handle);
        return $aggregated;
    }
}
