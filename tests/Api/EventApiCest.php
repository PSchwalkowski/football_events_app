<?php

namespace Tests\Api;

use Tests\Support\ApiTester;

class EventApiCest
{
    public function _before(ApiTester $I)
    {
        // Clean up storage files before each test
        $I->deleteFile('storage/events.txt');
        $I->deleteFile('storage/statistics.txt');
    }

    public function testFoulEvent(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'foul',
            'player' => 'William Saliba',
            'affected_player' => 'John Doe',
            'team_id' => 'arsenal',
            'match_id' => 'm1',
            'minute' => 45,
            'second' => 34,
        ]);
        
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Event saved successfully'
        ]);
        $I->seeResponseJsonMatchesJsonPath('$.event.type', 'foul');
    }

    public function testFoulEventWithoutRequiredFields(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'foul',
            'player' => 'William Saliba',
            'minute' => 45,
            'second' => 34
            // Missing team_id and match_id
        ]);
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'Fields player, affected_player, minute, second, team_id, match_id are required for this event',
        ]);
    }

    public function testInvalidJson(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', 'invalid json');
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'Invalid JSON'
        ]);
    }

    public function testEventWithoutType(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'player' => 'John Doe',
            'minute' => 23,
            'second' => 34
        ]);
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'Fields type are required for this event'
        ]);
    }

    public function testGoalEvent(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'goal',
            'player' => 'John Doe',
            'minute' => 23,
            'second' => 34,
            'team_id' => 'team_a',
            'match_id' => 'match_1',
            'assisting_player' => 'John Smith',
        ]);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Event saved successfully'
        ]);
        $I->seeResponseJsonMatchesJsonPath('$.event.type', 'goal');
    }

    public function testGoalEventWithoutRequiredFields(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'goal',
            'player' => 'John Doe',
//            'minute' => 23,
//            'second' => 34,
//            'team_id' => 'team_a',
//            'match_id' => 'match_1',
//            'assisting_player' => 'John Smith',
        ]);

        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'Fields player, minute, second, team_id, match_id, assisting_player are required for this event'
        ]);
    }

    public function testGetAllEvents(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');

        $event = [
            'type' => 'goal',
            'player' => 'John Doe',
            'minute' => 23,
            'second' => 34,
            'team_id' => 'team_a',
            'match_id' => 'match_1',
            'assisting_player' => 'John Smith',
        ];

        $I->sendPOST('/event', $event);

        $I->sendGET('/events');

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
            'events' => [
                [
                    'type' => 'goal',
//                    'timestamp' => 1771832788,
                    'data' => $event
                ]
            ],
        ]);
    }

    public function testGetFilteredEvents(ApiTester $I): void
    {
        $I->markTestSkipped('TODO');
    }
}
