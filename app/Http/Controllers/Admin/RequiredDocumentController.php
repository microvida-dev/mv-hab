<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentReferencePeriodUnit;
use App\Enums\RequiredDocumentConditionOperator;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequiredDocumentRequest;
use App\Http\Requests\UpdateRequiredDocumentRequest;
use App\Models\Contest;
use App\Models\DocumentType;
use App\Models\Program;
use App\Models\RequiredDocument;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RequiredDocumentController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', RequiredDocument::class);

        $requiredDocuments = RequiredDocument::query()
            ->with(['documentType', 'program', 'contest'])
            ->orderBy('sort_order')
            ->latest()
            ->paginate(15);

        return view('admin.required-documents.index', compact('requiredDocuments'));
    }

    public function create(): View
    {
        Gate::authorize('createBackoffice', RequiredDocument::class);

        return view('admin.required-documents.create', $this->formData());
    }

    public function store(StoreRequiredDocumentRequest $request): RedirectResponse
    {
        $requiredDocument = RequiredDocument::query()->create($this->normalizedData($request->validated()));
        $this->auditLogger->record(
            event: AuditEvents::CREATE,
            auditable: $requiredDocument,
            module: 'documents',
            action: 'required_document_create',
            description: 'Regra de documento obrigatório criada.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );

        return to_route('admin.required-documents.index')
            ->with('success', 'Regra documental criada.');
    }

    public function edit(RequiredDocument $requiredDocument): View
    {
        Gate::authorize('updateBackoffice', $requiredDocument);

        return view('admin.required-documents.edit', [
            'requiredDocument' => $requiredDocument,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateRequiredDocumentRequest $request, RequiredDocument $requiredDocument): RedirectResponse
    {
        $requiredDocument->update($this->normalizedData($request->validated()));
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $requiredDocument,
            module: 'documents',
            action: 'required_document_update',
            description: 'Regra de documento obrigatório atualizada.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );

        return to_route('admin.required-documents.index')
            ->with('success', 'Regra documental atualizada.');
    }

    public function destroy(Request $request, RequiredDocument $requiredDocument): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $requiredDocument);
        $requiredDocument->delete();
        $this->auditLogger->record(
            event: AuditEvents::DELETE,
            auditable: $requiredDocument,
            module: 'documents',
            action: 'required_document_delete',
            description: 'Regra de documento obrigatório removida por soft delete.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );

        return to_route('admin.required-documents.index')
            ->with('success', 'Regra documental removida.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'documentTypes' => DocumentType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'programs' => Program::query()
                ->orderBy('name')
                ->get(['id', 'name']),

            'contests' => Contest::query()
                ->with('program:id,name')
                ->orderBy('title')
                ->get([
                    'id',
                    'program_id',
                    'title',
                ]),

            'requiredFor' => DocumentAppliesTo::options(),
            'operators' => RequiredDocumentConditionOperator::options(),

            'referencePeriodUnits' => collect(
                DocumentReferencePeriodUnit::cases(),
            )->mapWithKeys(
                fn (DocumentReferencePeriodUnit $unit): array => [
                    $unit->value => $unit->label(),
                ],
            )->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizedData(array $data): array
    {
        $data['is_required'] = (bool) (
            $data['is_required'] ?? false
        );

        $data['is_active'] = (bool) (
            $data['is_active'] ?? false
        );

        $data['requires_distinct_reference_periods'] = (bool) (
            $data['requires_distinct_reference_periods'] ?? false
        );

        $data['required_submissions'] = (int) (
            $data['required_submissions'] ?? 1
        );

        $data['sort_order'] = (int) (
            $data['sort_order'] ?? 0
        );

        $data['reference_period_unit'] = filled(
            $data['reference_period_unit'] ?? null,
        )
            ? $data['reference_period_unit']
            : null;

        $data['reference_period_recency'] = filled(
            $data['reference_period_recency'] ?? null,
        )
            ? (int) $data['reference_period_recency']
            : null;

        if ($data['reference_period_unit'] === null) {
            $data['requires_distinct_reference_periods'] = false;
            $data['reference_period_recency'] = null;
        }

        if (filled($data['contest_id'] ?? null)) {
            $contest = Contest::query()
                ->findOrFail((int) $data['contest_id']);

            $data['program_id'] = $contest->program_id;
        } else {
            $data['contest_id'] = null;
            $data['program_id'] = filled($data['program_id'] ?? null)
                ? (int) $data['program_id']
                : null;
        }

        return $data;
    }
}
