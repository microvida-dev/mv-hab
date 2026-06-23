<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ContractClauseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractClauseRequest;
use App\Http\Requests\UpdateContractClauseRequest;
use App\Models\Contest;
use App\Models\ContractClause;
use App\Models\Program;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContractClauseController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ContractClause::class);

        return view('backoffice.contracts.clauses.index', [
            'clauses' => ContractClause::query()->with(['program', 'contest'])->orderBy('sort_order')->paginate(30),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ContractClause::class);

        return view('backoffice.contracts.clauses.create', $this->formData());
    }

    public function store(StoreContractClauseRequest $request): RedirectResponse
    {
        Gate::authorize('create', ContractClause::class);
        $data = $this->normalized($request->validated());
        $status = $data['status'];
        unset($data['status']);
        $clause = ContractClause::query()->create($data);
        $clause->forceFill(['status' => $status, 'created_by' => $this->authenticatedUser($request)->id, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->auditLogger->record(AuditEvents::CREATE, $clause, 'contracts', 'contract_clause_create', 'Cláusula contratual criada.');

        return to_route('backoffice.contracts.clauses.show', $clause)->with('success', 'Cláusula criada.');
    }

    public function show(ContractClause $contractClause): View
    {
        Gate::authorize('view', $contractClause);

        return view('backoffice.contracts.clauses.show', compact('contractClause'));
    }

    public function edit(ContractClause $contractClause): View
    {
        Gate::authorize('update', $contractClause);

        return view('backoffice.contracts.clauses.edit', ['contractClause' => $contractClause, ...$this->formData()]);
    }

    public function update(UpdateContractClauseRequest $request, ContractClause $contractClause): RedirectResponse
    {
        Gate::authorize('update', $contractClause);
        $data = $this->normalized($request->validated());
        $status = $data['status'];
        unset($data['status']);
        $contractClause->update($data);
        $contractClause->forceFill(['status' => $status, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $contractClause, 'contracts', 'contract_clause_update', 'Cláusula contratual atualizada.');

        return to_route('backoffice.contracts.clauses.show', $contractClause)->with('success', 'Cláusula atualizada.');
    }

    public function activate(Request $request, ContractClause $contractClause): RedirectResponse
    {
        Gate::authorize('activate', $contractClause);
        $contractClause->forceFill(['status' => ContractClauseStatus::Active, 'updated_by' => $this->authenticatedUser($request)->id])->save();

        return back()->with('success', 'Cláusula ativada.');
    }

    public function archive(Request $request, ContractClause $contractClause): RedirectResponse
    {
        Gate::authorize('archive', $contractClause);
        $contractClause->forceFill(['status' => ContractClauseStatus::Archived, 'updated_by' => $this->authenticatedUser($request)->id])->save();

        return back()->with('success', 'Cláusula arquivada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'contests' => Contest::query()->orderBy('title')->get(['id', 'title']),
            'statuses' => ContractClauseStatus::options(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        $data['status'] = ContractClauseStatus::from((string) $data['status'])->value;
        $data['is_mandatory'] = (bool) ($data['is_mandatory'] ?? false);

        return $data;
    }
}
