<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ContractTemplateStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractTemplateRequest;
use App\Http\Requests\UpdateContractTemplateRequest;
use App\Models\Contest;
use App\Models\ContractClause;
use App\Models\ContractTemplate;
use App\Models\Program;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContractTemplateController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ContractTemplate::class);

        return view('backoffice.contracts.templates.index', [
            'templates' => ContractTemplate::query()->with(['program', 'contest'])->withCount('clauses')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ContractTemplate::class);

        return view('backoffice.contracts.templates.create', $this->formData());
    }

    public function store(StoreContractTemplateRequest $request): RedirectResponse
    {
        Gate::authorize('create', ContractTemplate::class);
        $data = $this->normalized($request->validated());
        $status = $data['status'];
        $clauses = $data['clause_ids'] ?? [];
        unset($data['status'], $data['clause_ids']);
        $template = ContractTemplate::query()->create($data);
        $template->forceFill(['status' => $status, 'created_by' => $this->authenticatedUser($request)->id, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->syncClauses($template, $clauses);
        $this->auditLogger->record(AuditEvents::CREATE, $template, 'contracts', 'contract_template_create', 'Minuta contratual criada.');

        return to_route('backoffice.contracts.templates.show', $template)->with('success', 'Minuta criada.');
    }

    public function show(ContractTemplate $contractTemplate): View
    {
        Gate::authorize('view', $contractTemplate);
        $contractTemplate->load(['program', 'contest', 'clauses']);

        return view('backoffice.contracts.templates.show', compact('contractTemplate'));
    }

    public function edit(ContractTemplate $contractTemplate): View
    {
        Gate::authorize('update', $contractTemplate);

        return view('backoffice.contracts.templates.edit', ['contractTemplate' => $contractTemplate->load('clauses'), ...$this->formData()]);
    }

    public function update(UpdateContractTemplateRequest $request, ContractTemplate $contractTemplate): RedirectResponse
    {
        Gate::authorize('update', $contractTemplate);
        $data = $this->normalized($request->validated());
        $status = $data['status'];
        $clauses = $data['clause_ids'] ?? [];
        unset($data['status'], $data['clause_ids']);
        $contractTemplate->update($data);
        $contractTemplate->forceFill(['status' => $status, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->syncClauses($contractTemplate, $clauses);
        $this->auditLogger->record(AuditEvents::UPDATE, $contractTemplate, 'contracts', 'contract_template_update', 'Minuta contratual atualizada.');

        return to_route('backoffice.contracts.templates.show', $contractTemplate)->with('success', 'Minuta atualizada.');
    }

    public function activate(Request $request, ContractTemplate $contractTemplate): RedirectResponse
    {
        Gate::authorize('activate', $contractTemplate);
        $contractTemplate->forceFill(['status' => ContractTemplateStatus::Active, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->auditLogger->record(AuditEvents::APPROVE, $contractTemplate, 'contracts', 'contract_template_activate', 'Minuta contratual ativada.');

        return back()->with('success', 'Minuta ativada.');
    }

    public function archive(Request $request, ContractTemplate $contractTemplate): RedirectResponse
    {
        Gate::authorize('archive', $contractTemplate);
        $contractTemplate->forceFill(['status' => ContractTemplateStatus::Archived, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $contractTemplate, 'contracts', 'contract_template_archive', 'Minuta contratual arquivada.');

        return back()->with('success', 'Minuta arquivada.');
    }

    public function duplicate(Request $request, ContractTemplate $contractTemplate): RedirectResponse
    {
        Gate::authorize('duplicate', $contractTemplate);
        $copy = $contractTemplate->replicate();
        $copy->name = $contractTemplate->name.' - cópia';
        $copy->forceFill([
            'status' => ContractTemplateStatus::Draft->value,
            'created_by' => $this->authenticatedUser($request)->id,
            'updated_by' => $this->authenticatedUser($request)->id,
        ]);
        $copy->version_number = $contractTemplate->version_number + 1;
        $copy->save();
        $this->syncClauses($copy, $contractTemplate->clauses()->pluck('contract_clauses.id')->all());

        return to_route('backoffice.contracts.templates.edit', $copy)->with('success', 'Cópia criada em rascunho.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'contests' => Contest::query()->orderBy('title')->get(['id', 'title']),
            'clauses' => ContractClause::query()->orderBy('title')->get(['id', 'title', 'code']),
            'statuses' => ContractTemplateStatus::options(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        $data['status'] = ContractTemplateStatus::from((string) $data['status'])->value;

        return $data;
    }

    /**
     * @param  array<int, int|string>  $clauseIds
     */
    private function syncClauses(ContractTemplate $template, array $clauseIds): void
    {
        $sync = collect($clauseIds)->values()->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index + 1, 'is_active' => true]])->all();
        $template->clauses()->sync($sync);
    }
}
