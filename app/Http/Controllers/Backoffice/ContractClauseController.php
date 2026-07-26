<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ContractClauseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractClauseRequest;
use App\Http\Requests\UpdateContractClauseRequest;
use App\Models\Contest;
use App\Models\ContractClause;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContractClauseController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', ContractClause::class);
        $actor = $this->authenticatedUser($request);

        return view('backoffice.contracts.clauses.index', [
            'clauses' => $this->municipalScope
                ->contractClauses(ContractClause::query(), $actor)
                ->with(['program', 'contest'])
                ->orderBy('sort_order')
                ->paginate(30),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', ContractClause::class);

        return view('backoffice.contracts.clauses.create', $this->formData(
            $this->authenticatedUser($request),
        ));
    }

    public function store(StoreContractClauseRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', ContractClause::class);
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
        Gate::authorize('viewBackoffice', $contractClause);

        return view('backoffice.contracts.clauses.show', compact('contractClause'));
    }

    public function edit(Request $request, ContractClause $contractClause): View
    {
        Gate::authorize('updateBackoffice', $contractClause);

        return view('backoffice.contracts.clauses.edit', [
            'contractClause' => $contractClause,
            ...$this->formData($this->authenticatedUser($request)),
        ]);
    }

    public function update(UpdateContractClauseRequest $request, ContractClause $contractClause): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $contractClause);
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
        Gate::authorize('activateBackoffice', $contractClause);
        $contractClause->forceFill(['status' => ContractClauseStatus::Active, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->auditLogger->record(AuditEvents::APPROVE, $contractClause, 'contracts', 'contract_clause_activate', 'Cláusula contratual ativada.');

        return back()->with('success', 'Cláusula ativada.');
    }

    public function archive(Request $request, ContractClause $contractClause): RedirectResponse
    {
        Gate::authorize('archiveBackoffice', $contractClause);
        $contractClause->forceFill(['status' => ContractClauseStatus::Archived, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $contractClause, 'contracts', 'contract_clause_archive', 'Cláusula contratual arquivada.');

        return back()->with('success', 'Cláusula arquivada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(User $actor): array
    {
        return [
            'programs' => $this->municipalScope
                ->programs(Program::query(), $actor)
                ->orderBy('name')
                ->get(['id', 'name']),
            'contests' => $this->municipalScope
                ->contests(Contest::query(), $actor)
                ->orderBy('title')
                ->get(['id', 'title']),
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
