<?php

namespace Tests\Unit\Notifications;

use App\Services\Notifications\ProceduralNotificationPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProceduralNotificationPolicyTest extends TestCase
{
    /**
     * @return iterable<string, array{eventCode: string, official: bool, expected: bool}>
     */
    public static function eventCases(): iterable
    {
        yield 'known procedural event cannot be downgraded by template metadata' => [
            'eventCode' => 'application_submitted',
            'official' => false,
            'expected' => true,
        ];

        yield 'generic official notification remains mandatory' => [
            'eventCode' => 'other',
            'official' => true,
            'expected' => true,
        ];

        yield 'legacy candidate visit is operational and non-procedural' => [
            'eventCode' => 'visit_scheduled',
            'official' => true,
            'expected' => false,
        ];

        yield 'public open-house reservation is non-procedural' => [
            'eventCode' => 'public_visit_booked',
            'official' => true,
            'expected' => false,
        ];

        yield 'support interaction is non-procedural' => [
            'eventCode' => 'support_ticket_created',
            'official' => true,
            'expected' => false,
        ];

        yield 'non-official unknown event is optional' => [
            'eventCode' => 'product_update_available',
            'official' => false,
            'expected' => false,
        ];

        yield 'unknown official event fails closed' => [
            'eventCode' => 'municipal_decision_published',
            'official' => true,
            'expected' => true,
        ];
    }

    #[DataProvider('eventCases')]
    public function test_it_classifies_mandatory_email_events(
        string $eventCode,
        bool $official,
        bool $expected,
    ): void {
        self::assertSame(
            $expected,
            (new ProceduralNotificationPolicy)
                ->requiresMandatoryEmail($eventCode, $official),
        );
    }
}
