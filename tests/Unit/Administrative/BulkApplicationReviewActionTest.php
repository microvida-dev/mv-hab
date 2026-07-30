<?php

namespace Tests\Unit\Administrative;

use App\Enums\BulkApplicationReviewAction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BulkApplicationReviewActionTest extends TestCase
{
    /**
     * @return iterable<string, array{
     *     BulkApplicationReviewAction,
     *     bool,
     *     bool,
     *     bool
     * }>
     */
    public static function actionContracts(): iterable
    {
        yield 'assign analyst' => [
            BulkApplicationReviewAction::AssignAnalyst,
            true,
            false,
            false,
        ];

        yield 'mark under review' => [
            BulkApplicationReviewAction::MarkDocumentsUnderReview,
            false,
            true,
            false,
        ];

        yield 'validate' => [
            BulkApplicationReviewAction::ValidateDocuments,
            false,
            true,
            false,
        ];

        yield 'reject' => [
            BulkApplicationReviewAction::RejectDocuments,
            false,
            true,
            true,
        ];

        yield 'ready' => [
            BulkApplicationReviewAction::MarkReadyForClosure,
            false,
            false,
            false,
        ];

        yield 'reopen' => [
            BulkApplicationReviewAction::ReopenReview,
            false,
            false,
            true,
        ];
    }

    #[DataProvider('actionContracts')]
    public function test_action_contracts(
        BulkApplicationReviewAction $action,
        bool $requiresAssignee,
        bool $requiresDocuments,
        bool $requiresReason,
    ): void {
        self::assertSame(
            $requiresAssignee,
            $action->requiresAssignee(),
        );
        self::assertSame(
            $requiresDocuments,
            $action->requiresDocuments(),
        );
        self::assertSame(
            $requiresReason,
            $action->requiresReason(),
        );
        self::assertSame(
            $requiresDocuments,
            $action->isDocumentAction(),
        );
        self::assertNotSame('', $action->label());
    }
}
