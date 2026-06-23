<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityOperator;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEligibilityCriterionRequest;
use App\Http\Requests\UpdateEligibilityCriterionRequest;
use App\Models\EligibilityCriterion;
use App\Models\EligibilityRuleSet;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EligibilityCriterionController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(EligibilityRuleSet $eligibilityRuleSet): View
    {
        Gate::authorize('view', $eligibilityRuleSet);
        $eligibilityRuleSet->load(['program', 'contest']);
        $criteria = $eligibilityRuleSet->criteria()->paginate(30);

        return view('backoffice.eligibility.criteria.index', [
            'ruleSet' => $eligibilityRuleSet,
            'criteria' => $criteria,
        ]);
    }

    public function create(EligibilityRuleSet $eligibilityRuleSet): View
    {
        Gate::authorize('create', [EligibilityCriterion::class, $eligibilityRuleSet]);

        return view('backoffice.eligibility.criteria.create', [
            'ruleSet' => $eligibilityRuleSet,
            ...$this->formData(),
        ]);
    }

    public function store(
        StoreEligibilityCriterionRequest $request,
        EligibilityRuleSet|string $eligibilityRuleSet,
    ): RedirectResponse {
        $eligibilityRuleSet = $request->ruleSet();
        $criterion = $eligibilityRuleSet->criteria()->create($this->normalized($request->validated()));

        $this->audit('create', AuditEvents::CREATE, $criterion, $request);

        return to_route('backoffice.eligibility.criteria.index', $eligibilityRuleSet)
            ->with('success', 'Critério criado.');
    }

    public function edit(EligibilityCriterion $eligibilityCriterion): View
    {
        Gate::authorize('update', $eligibilityCriterion);
        $eligibilityCriterion->load('ruleSet');

        return view('backoffice.eligibility.criteria.edit', [
            'criterion' => $eligibilityCriterion,
            'ruleSet' => $eligibilityCriterion->ruleSet,
            ...$this->formData(),
        ]);
    }

    public function update(
        UpdateEligibilityCriterionRequest $request,
        EligibilityCriterion $eligibilityCriterion,
    ): RedirectResponse {
        $eligibilityCriterion->update($this->normalized($request->validated()));
        $this->audit('update', AuditEvents::UPDATE, $eligibilityCriterion, $request);

        return to_route('backoffice.eligibility.criteria.index', $eligibilityCriterion->ruleSet)
            ->with('success', 'Critério atualizado.');
    }

    public function activate(Request $request, EligibilityCriterion $eligibilityCriterion): RedirectResponse
    {
        Gate::authorize('activate', $eligibilityCriterion);
        $eligibilityCriterion->update(['is_active' => true]);
        $this->audit('activate', AuditEvents::UPDATE, $eligibilityCriterion, $request);

        return back()->with('success', 'Critério ativado.');
    }

    public function inactivate(Request $request, EligibilityCriterion $eligibilityCriterion): RedirectResponse
    {
        Gate::authorize('activate', $eligibilityCriterion);
        $eligibilityCriterion->update(['is_active' => false]);
        $this->audit('inactivate', AuditEvents::UPDATE, $eligibilityCriterion, $request);

        return back()->with('success', 'Critério inativado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => EligibilityCriterionCategory::options(),
            'operators' => EligibilityOperator::options(),
            'targets' => [
                'adhesion_registration' => 'Registo de Adesão',
                'household' => 'Agregado',
                'household_member' => 'Membro do agregado',
                'income_records' => 'Rendimentos',
                'current_housing_situation' => 'Situação habitacional',
                'documents' => 'Documentos',
                'application' => 'Candidatura',
                'contest' => 'Concurso',
                'program' => 'Programa',
                'calculated_value' => 'Valor calculado',
                'manual' => 'Análise manual',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        $data['expected_value'] = $this->expectedValue($data['expected_value'] ?? null);
        $data['is_mandatory'] = (bool) ($data['is_mandatory'] ?? false);
        $data['requires_manual_review'] = (bool) ($data['requires_manual_review'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function expectedValue(?string $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return is_array($decoded) ? $decoded : ['value' => $decoded];
        }

        return ['value' => is_numeric($value) ? (float) $value : $value];
    }

    private function audit(string $action, string $event, EligibilityCriterion $criterion, Request $request): void
    {
        $this->auditLogger->record(
            event: $event,
            auditable: $criterion,
            module: 'eligibility',
            action: 'criterion_'.$action,
            description: 'Critério de elegibilidade: '.$action.'.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );
    }
}
