<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\DocumentAiSuggestionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\AcceptDocumentAiSuggestionRequest;
use App\Http\Requests\Backoffice\DismissDocumentAiSuggestionRequest;
use App\Http\Requests\Backoffice\FilterDocumentAiAssistantRequest;
use App\Http\Requests\Backoffice\RecalculateDocumentAiScoreRequest;
use App\Http\Requests\Backoffice\UpdateDocumentAiSuggestionRequest;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiScore;
use App\Models\DocumentAiSuggestion;
use App\Policies\DocumentAiAssistantPolicy;
use App\Services\Audit\AuditLogger;
use App\Services\DocumentIntelligence\DocumentAiAssistantDashboardService;
use App\Services\DocumentIntelligence\DocumentAiManualAnalysisService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentAiAssistantController extends Controller
{
    public function __construct(
        private readonly DocumentAiAssistantDashboardService $dashboardService,
        private readonly DocumentAiManualAnalysisService $manualAnalysisService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(FilterDocumentAiAssistantRequest $request): View
    {
        Gate::authorize('viewAny', DocumentAiScore::class);
        $filters = array_filter(
            $request->validated(),
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );

        return view('backoffice.document-ai.assistant.index', [
            'scores' => $this->dashboardService->scores($filters),
            'filters' => $filters,
            'totals' => $this->dashboardService->totals(),
        ]);
    }

    public function show(DocumentAiAnalysis $analysis): View
    {
        $user = request()->user();
        abort_unless($user !== null && app(DocumentAiAssistantPolicy::class)->view($user, $analysis), 403);

        $analysis = $this->dashboardService->analysisForShow($analysis);
        $score = $analysis->latestScore;

        $this->auditLogger->record(
            event: AuditEvents::ACCESS,
            auditable: $analysis,
            module: 'documents',
            action: 'document_ai_assistant_viewed',
            description: 'Consulta do assistente IA documental.',
            metadata: [
                'document_ai_analysis_id' => $analysis->id,
                'score_id' => $score?->id,
            ],
        );

        return view('backoffice.document-ai.assistant.show', [
            'analysis' => $analysis,
            'score' => $score,
            'flags' => $analysis->flags->sortByDesc('score_impact')->values(),
            'suggestions' => $analysis->suggestions->sortBy('status')->values(),
        ]);
    }

    public function score(DocumentAiScore $score): RedirectResponse
    {
        Gate::authorize('view', $score);

        return redirect()->route('backoffice.document-ai.assistant.show', $score->analysis);
    }

    public function recalculate(RecalculateDocumentAiScoreRequest $request, DocumentAiAnalysis $analysis): RedirectResponse
    {
        $processed = $this->manualAnalysisService->reprocess($analysis, $this->authenticatedUser($request));

        return redirect()
            ->route('backoffice.document-ai.assistant.show', $processed)
            ->with('success', 'Análise IA documental reprocessada.');
    }

    public function updateSuggestion(UpdateDocumentAiSuggestionRequest $request, DocumentAiSuggestion $suggestion): RedirectResponse
    {
        $suggestion->forceFill([
            'suggestion' => $request->validated('suggestion'),
            'status' => DocumentAiSuggestionStatus::Edited,
        ]);
        $suggestion->save();

        $this->auditSuggestion($suggestion, 'document_ai_suggestion_updated', 'Sugestão IA editada pelo técnico.');

        return redirect()
            ->route('backoffice.document-ai.assistant.show', $suggestion->analysis)
            ->with('success', 'Sugestão atualizada.');
    }

    public function acceptSuggestion(AcceptDocumentAiSuggestionRequest $request, DocumentAiSuggestion $suggestion): RedirectResponse
    {
        $suggestion->forceFill([
            'status' => DocumentAiSuggestionStatus::Accepted,
            'accepted_at' => now(),
            'accepted_by' => $request->user()?->id,
            'metadata' => [
                ...($suggestion->metadata ?? []),
                'accepted_reason' => $request->validated('accept_reason'),
            ],
        ]);
        $suggestion->save();

        $this->auditSuggestion($suggestion, 'document_ai_suggestion_accepted', 'Sugestão IA aceite para eventual pedido de aperfeiçoamento.');

        return redirect()
            ->route('backoffice.document-ai.assistant.show', $suggestion->analysis)
            ->with('success', 'Sugestão aceite. Nenhuma comunicação foi enviada automaticamente.');
    }

    public function dismissSuggestion(DismissDocumentAiSuggestionRequest $request, DocumentAiSuggestion $suggestion): RedirectResponse
    {
        $suggestion->forceFill([
            'status' => DocumentAiSuggestionStatus::Dismissed,
            'dismissed_at' => now(),
            'dismissed_by' => $request->user()?->id,
            'dismiss_reason' => $request->validated('dismiss_reason'),
        ]);
        $suggestion->save();

        $this->auditSuggestion($suggestion, 'document_ai_suggestion_dismissed', 'Sugestão IA descartada pelo técnico.');

        return redirect()
            ->route('backoffice.document-ai.assistant.show', $suggestion->analysis)
            ->with('success', 'Sugestão descartada.');
    }

    private function auditSuggestion(DocumentAiSuggestion $suggestion, string $action, string $description): void
    {
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $suggestion,
            module: 'documents',
            action: $action,
            description: $description,
            metadata: [
                'document_ai_analysis_id' => $suggestion->document_ai_analysis_id,
                'document_ai_score_id' => $suggestion->document_ai_score_id,
                'flag_code' => $suggestion->flag_code,
                'status' => $suggestion->status->value,
                'has_justification' => true,
            ],
        );
    }
}
