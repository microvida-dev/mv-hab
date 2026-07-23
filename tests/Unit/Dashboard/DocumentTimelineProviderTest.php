<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Enums\AdditionalDocumentStatus;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\DocumentDossierStatus;
use App\Enums\DocumentStatus;
use App\Enums\ProcessActionStatus;
use App\Models\AdditionalDocumentRequest;
use App\Models\AdditionalDocumentSubmission;
use App\Models\DocumentDossier;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\DocumentTimelineProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class DocumentTimelineProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_returns_no_events_without_permission(): void
    {
        DocumentSubmission::factory()->create([
            'status' => DocumentStatus::Submitted,
            'submitted_at' => now(),
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('documents.view')
            ->once()
            ->andReturnFalse();

        $events = (new DocumentTimelineProvider)->forUser($user);

        $this->assertSame([], $events);
    }

    public function test_builds_document_submitted_event(): void
    {
        $submittedAt = now()->startOfMinute();

        DocumentSubmission::factory()->create([
            'status' => DocumentStatus::Submitted,
            'submitted_at' => $submittedAt,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('documents.view')
            ->once()
            ->andReturnTrue();

        $events = (new DocumentTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::DocumentSubmitted, $events[0]->type);
        $this->assertSame(TimelinePriority::Medium, $events[0]->priority);
        $this->assertSame(TimelineWorkspace::Applications, $events[0]->workspace);
        $this->assertSame('Documento submetido', $events[0]->title);
        $this->assertSame($submittedAt->toIso8601String(), $events[0]->datetime->toIso8601String());
    }

    public function test_builds_document_under_review_event(): void
    {
        $reviewedAt = now()->startOfMinute();

        DocumentSubmission::factory()->create([
            'status' => DocumentStatus::UnderReview,
            'submitted_at' => now()->subDay(),
            'reviewed_at' => $reviewedAt,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('documents.view')
            ->once()
            ->andReturnTrue();

        $events = (new DocumentTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::DocumentUnderReview, $events[0]->type);
        $this->assertSame(TimelinePriority::High, $events[0]->priority);
        $this->assertSame('Documento em análise', $events[0]->title);
        $this->assertSame($reviewedAt->toIso8601String(), $events[0]->datetime->toIso8601String());
    }

    public function test_builds_incomplete_dossier_event(): void
    {
        DocumentDossier::factory()->create([
            'status' => DocumentDossierStatus::Incomplete,
            'missing_documents_count' => 2,
            'updated_at' => now()->startOfMinute(),
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('documents.view')
            ->once()
            ->andReturnTrue();

        $events = (new DocumentTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::DocumentDossierIncomplete, $events[0]->type);
        $this->assertSame(TimelinePriority::High, $events[0]->priority);
        $this->assertSame('Dossier documental incompleto', $events[0]->title);
    }

    public function test_builds_additional_document_request_event(): void
    {
        $dueAt = now()->addDays(3)->startOfMinute();

        AdditionalDocumentRequest::factory()->create([
            'status' => ProcessActionStatus::Available,
            'due_at' => $dueAt,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('documents.view')
            ->once()
            ->andReturnTrue();

        $events = (new DocumentTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::AdditionalDocumentRequested, $events[0]->type);
        $this->assertSame(TimelinePriority::High, $events[0]->priority);
        $this->assertSame('Pedido de documentação adicional', $events[0]->title);
        $this->assertSame($dueAt->toIso8601String(), $events[0]->datetime->toIso8601String());
    }

    public function test_builds_additional_document_submission_event(): void
    {
        $submittedAt = now()->startOfMinute();

        AdditionalDocumentSubmission::factory()->create([
            'status' => AdditionalDocumentStatus::Submitted,
            'submitted_at' => $submittedAt,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('documents.view')
            ->once()
            ->andReturnTrue();

        $events = (new DocumentTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::AdditionalDocumentSubmitted, $events[0]->type);
        $this->assertSame(TimelinePriority::Medium, $events[0]->priority);
        $this->assertSame('Documentação adicional recebida', $events[0]->title);
        $this->assertSame($submittedAt->toIso8601String(), $events[0]->datetime->toIso8601String());
    }
}
