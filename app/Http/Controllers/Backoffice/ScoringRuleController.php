<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ScoringOperator;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScoringRuleRequest;
use App\Http\Requests\UpdateScoringRuleRequest;
use App\Models\ScoringCriterion;
use App\Models\ScoringRule;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScoringRuleController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(ScoringCriterion $scoringCriterion): View
    {
        Gate::authorize('view', $scoringCriterion);
        $rules = $scoringCriterion->rules()->paginate(30);

        return view('backoffice.scoring.rules.index', [
            'criterion' => $scoringCriterion->load('ruleSet'),
            'rules' => $rules,
        ]);
    }

    public function create(ScoringCriterion $scoringCriterion): View
    {
        Gate::authorize('create', [ScoringRule::class, $scoringCriterion]);

        return view('backoffice.scoring.rules.create', [
            'criterion' => $scoringCriterion->load('ruleSet'),
            'operators' => ScoringOperator::options(),
        ]);
    }

    public function store(StoreScoringRuleRequest $request, ScoringCriterion $scoringCriterion): RedirectResponse
    {
        $rule = $scoringCriterion->rules()->create($this->normalized($request->validated()));

        $this->audit('scoring_rule_create', AuditEvents::CREATE, $rule, $request);

        return to_route('backoffice.scoring.rules.index', $scoringCriterion)
            ->with('success', 'Regra de pontuação criada.');
    }

    public function edit(ScoringRule $scoringRule): View
    {
        Gate::authorize('update', $scoringRule);
        $scoringRule->load('criterion.ruleSet');

        return view('backoffice.scoring.rules.edit', [
            'rule' => $scoringRule,
            'criterion' => $scoringRule->criterion,
            'operators' => ScoringOperator::options(),
        ]);
    }

    public function update(UpdateScoringRuleRequest $request, ScoringRule $scoringRule): RedirectResponse
    {
        $scoringRule->update($this->normalized($request->validated()));
        $this->audit('scoring_rule_update', AuditEvents::UPDATE, $scoringRule, $request);

        return to_route('backoffice.scoring.rules.index', $scoringRule->criterion)
            ->with('success', 'Regra de pontuação atualizada.');
    }

    public function destroy(Request $request, ScoringRule $scoringRule): RedirectResponse
    {
        Gate::authorize('delete', $scoringRule);
        $criterion = $scoringRule->criterion;
        abort_unless($criterion instanceof ScoringCriterion, 500);

        $scoringRule->delete();
        $this->audit('scoring_rule_delete', AuditEvents::DELETE, $scoringRule, $request);

        return to_route('backoffice.scoring.rules.index', $criterion)
            ->with('success', 'Regra de pontuação removida.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        unset($data['scoring_criterion_id']);
        $data['value'] = $this->jsonValue($data['value'] ?? null);
        $data['weight'] = $data['weight'] ?? 1;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

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
            return is_array($decoded) ? $decoded : ['value' => $decoded];
        }

        return ['value' => is_numeric($value) ? (float) $value : $value];
    }

    private function audit(string $action, string $event, ScoringRule $rule, Request $request): void
    {
        $this->auditLogger->record(
            event: $event,
            auditable: $rule,
            module: 'scoring',
            action: $action,
            description: 'Regra de pontuação: '.$action.'.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );
    }
}
