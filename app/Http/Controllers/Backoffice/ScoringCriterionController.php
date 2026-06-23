<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ScoringCalculationType;
use App\Enums\ScoringOperator;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScoringCriterionRequest;
use App\Http\Requests\UpdateScoringCriterionRequest;
use App\Models\ScoringCriterion;
use App\Models\ScoringRuleSet;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScoringCriterionController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function index(ScoringRuleSet $scoringRuleSet): View
    {
        Gate::authorize('view', $scoringRuleSet);

        return view('backoffice.scoring.criteria.index', [
            'ruleSet' => $scoringRuleSet,
            'criteria' => $scoringRuleSet
                ->criteria()
                ->withCount('rules')
                ->paginate(30),
        ]);
    }

    public function create(ScoringRuleSet $scoringRuleSet): View
    {
        Gate::authorize('create', [ScoringCriterion::class, $scoringRuleSet]);

        return view('backoffice.scoring.criteria.create', [
            'ruleSet' => $scoringRuleSet,
            ...$this->formData(),
        ]);
    }

    public function store(
        StoreScoringCriterionRequest $request,
        ScoringRuleSet $scoringRuleSet
    ): RedirectResponse {
        $criterion = $scoringRuleSet
            ->criteria()
            ->create($this->normalized($request->validated()));

        $this->audit(
            'criterion_create',
            AuditEvents::CREATE,
            $criterion,
            $request
        );

        return to_route(
            'backoffice.scoring.criteria.index',
            $scoringRuleSet
        )->with('success', 'Critério de classificação criado.');
    }

    public function edit(ScoringCriterion $scoringCriterion): View
    {
        Gate::authorize('update', $scoringCriterion);

        $scoringCriterion->load('ruleSet');

        return view('backoffice.scoring.criteria.edit', [
            'criterion' => $scoringCriterion,
            'ruleSet' => $scoringCriterion->ruleSet,
            ...$this->formData(),
        ]);
    }

    public function update(
        UpdateScoringCriterionRequest $request,
        ScoringCriterion $scoringCriterion
    ): RedirectResponse {
        $scoringCriterion->update(
            $this->normalized($request->validated())
        );

        $this->audit(
            'criterion_update',
            AuditEvents::UPDATE,
            $scoringCriterion,
            $request
        );

        return to_route(
            'backoffice.scoring.criteria.index',
            $scoringCriterion->ruleSet
        )->with('success', 'Critério de classificação atualizado.');
    }

    public function activate(
        Request $request,
        ScoringCriterion $scoringCriterion
    ): RedirectResponse {
        Gate::authorize('activate', $scoringCriterion);

        $scoringCriterion->update([
            'is_active' => true,
        ]);

        $this->audit(
            'criterion_activate',
            AuditEvents::UPDATE,
            $scoringCriterion,
            $request
        );

        return back()->with('success', 'Critério ativado.');
    }

    public function inactivate(
        Request $request,
        ScoringCriterion $scoringCriterion
    ): RedirectResponse {
        Gate::authorize('activate', $scoringCriterion);

        $scoringCriterion->update([
            'is_active' => false,
        ]);

        $this->audit(
            'criterion_inactivate',
            AuditEvents::UPDATE,
            $scoringCriterion,
            $request
        );

        return back()->with('success', 'Critério inativado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => [
                'household' => 'Agregado',
                'income' => 'Rendimentos',
                'housing_situation' => 'Situação habitacional',
                'residence' => 'Residência',
                'employment' => 'Emprego',
                'age' => 'Idade',
                'disability' => 'Deficiência/incapacidade',
                'dependency' => 'Dependência',
                'vulnerability' => 'Vulnerabilidade',
                'documents' => 'Documentos',
                'eligibility' => 'Elegibilidade',
                'typology' => 'Tipologia',
                'manual_assessment' => 'Avaliação manual',
                'other' => 'Outro',
            ],
            'targets' => [
                'application' => 'Candidatura',
                'adhesion_registration' => 'Registo de adesão',
                'household' => 'Agregado',
                'household_member' => 'Membro do agregado',
                'income_records' => 'Rendimentos',
                'current_housing_situation' => 'Situação habitacional',
                'documents' => 'Documentos',
                'eligibility_check' => 'Elegibilidade',
                'calculated_value' => 'Valor calculado',
                'manual' => 'Manual',
            ],
            'calculationTypes' => ScoringCalculationType::options(),
            'operators' => ScoringOperator::options(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        unset($data['scoring_rule_set_id']);

        $data['expected_value'] = $this->jsonValue(
            $data['expected_value'] ?? null
        );

        $data['weight'] = $data['weight'] ?? 1;
        $data['requires_manual_review'] = (bool) ($data['requires_manual_review'] ?? false);
        $data['is_exclusionary'] = (bool) ($data['is_exclusionary'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function jsonValue(mixed $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return is_array($decoded)
                ? $decoded
                : ['value' => $decoded];
        }

        return [
            'value' => is_numeric($value)
                ? (float) $value
                : $value,
        ];
    }

    private function audit(
        string $action,
        string $event,
        ScoringCriterion $criterion,
        Request $request
    ): void {
        $user = $this->authenticatedUser($request);

        $this->auditLogger->record(
            event: $event,
            auditable: $criterion,
            module: 'scoring',
            action: $action,
            description: "Critério de classificação: {$action}.",
            metadata: [
                'actor_id' => $user->id,
            ],
        );
    }
}
