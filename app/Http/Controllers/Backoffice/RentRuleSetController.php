<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\RentCalculationMethod;
use App\Enums\RentRuleSetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRentRuleSetRequest;
use App\Http\Requests\UpdateRentRuleSetRequest;
use App\Models\Contest;
use App\Models\Program;
use App\Models\RentRuleSet;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RentRuleSetController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        Gate::authorize('viewAny', RentRuleSet::class);

        return view('backoffice.contracts.rent-rule-sets.index', [
            'ruleSets' => RentRuleSet::query()->with(['program', 'contest'])->withCount(['rules', 'calculations'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', RentRuleSet::class);

        return view('backoffice.contracts.rent-rule-sets.create', $this->formData());
    }

    public function store(StoreRentRuleSetRequest $request): RedirectResponse
    {
        Gate::authorize('create', RentRuleSet::class);
        $data = $this->normalized($request->validated());
        $status = $data['status'];
        unset($data['status']);

        $ruleSet = RentRuleSet::query()->create($data);
        $ruleSet->forceFill([
            'status' => $status,
            'created_by' => $this->authenticatedUser($request)->id,
            'updated_by' => $this->authenticatedUser($request)->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $ruleSet, 'contracts', 'rent_rule_set_create', 'Regra de renda criada.');

        return to_route('backoffice.contracts.rent-rule-sets.show', $ruleSet)->with('success', 'Regra de renda criada.');
    }

    public function show(RentRuleSet $rentRuleSet): View
    {
        Gate::authorize('view', $rentRuleSet);
        $rentRuleSet->load(['program', 'contest', 'rules', 'createdBy', 'updatedBy'])->loadCount('calculations');

        return view('backoffice.contracts.rent-rule-sets.show', ['ruleSet' => $rentRuleSet]);
    }

    public function edit(RentRuleSet $rentRuleSet): View
    {
        Gate::authorize('update', $rentRuleSet);

        return view('backoffice.contracts.rent-rule-sets.edit', ['ruleSet' => $rentRuleSet, ...$this->formData()]);
    }

    public function update(UpdateRentRuleSetRequest $request, RentRuleSet $rentRuleSet): RedirectResponse
    {
        Gate::authorize('update', $rentRuleSet);
        $data = $this->normalized($request->validated());
        $status = $data['status'];
        unset($data['status']);
        $rentRuleSet->update($data);
        $rentRuleSet->forceFill(['status' => $status, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $rentRuleSet, 'contracts', 'rent_rule_set_update', 'Regra de renda atualizada.');

        return to_route('backoffice.contracts.rent-rule-sets.show', $rentRuleSet)->with('success', 'Regra de renda atualizada.');
    }

    public function activate(Request $request, RentRuleSet $rentRuleSet): RedirectResponse
    {
        Gate::authorize('activate', $rentRuleSet);
        $rentRuleSet->forceFill(['status' => RentRuleSetStatus::Active->value, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->auditLogger->record(AuditEvents::APPROVE, $rentRuleSet, 'contracts', 'rent_rule_set_activate', 'Regra de renda ativada.');

        return back()->with('success', 'Regra de renda ativada.');
    }

    public function archive(Request $request, RentRuleSet $rentRuleSet): RedirectResponse
    {
        Gate::authorize('archive', $rentRuleSet);
        $rentRuleSet->forceFill(['status' => RentRuleSetStatus::Archived->value, 'updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $rentRuleSet, 'contracts', 'rent_rule_set_archive', 'Regra de renda arquivada.');

        return back()->with('success', 'Regra de renda arquivada.');
    }

    public function duplicate(Request $request, RentRuleSet $rentRuleSet): RedirectResponse
    {
        Gate::authorize('duplicate', $rentRuleSet);
        $copy = $rentRuleSet->replicate();
        $copy->name = $rentRuleSet->name.' - cópia';
        $copy->forceFill([
            'status' => RentRuleSetStatus::Draft->value,
            'created_by' => $this->authenticatedUser($request)->id,
            'updated_by' => $this->authenticatedUser($request)->id,
        ]);
        $copy->save();

        foreach ($rentRuleSet->rules as $rule) {
            $ruleCopy = $rule->replicate();
            $ruleCopy->forceFill(['rent_rule_set_id' => $copy->id]);
            $ruleCopy->save();
        }

        $this->auditLogger->record(AuditEvents::CREATE, $copy, 'contracts', 'rent_rule_set_duplicate', 'Regra de renda duplicada.');

        return to_route('backoffice.contracts.rent-rule-sets.edit', $copy)->with('success', 'Cópia criada em rascunho.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'contests' => Contest::query()->orderBy('title')->get(['id', 'title']),
            'statuses' => RentRuleSetStatus::options(),
            'methods' => RentCalculationMethod::options(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        $data['status'] = RentRuleSetStatus::from($data['status']);
        $data['calculation_method'] = RentCalculationMethod::from($data['calculation_method']);
        $data['requires_manual_approval'] = (bool) ($data['requires_manual_approval'] ?? false);
        $data['allow_manual_override'] = (bool) ($data['allow_manual_override'] ?? false);

        return $data;
    }
}
