<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\TieBreakerDirection;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTieBreakerRuleRequest;
use App\Http\Requests\UpdateTieBreakerRuleRequest;
use App\Models\ScoringRuleSet;
use App\Models\TieBreakerRule;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TieBreakerRuleController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(ScoringRuleSet $scoringRuleSet): View
    {
        Gate::authorize('view', $scoringRuleSet);
        $rules = $scoringRuleSet->tieBreakerRules()->paginate(30);

        return view('backoffice.scoring.tie-breakers.index', [
            'ruleSet' => $scoringRuleSet,
            'rules' => $rules,
        ]);
    }

    public function create(ScoringRuleSet $scoringRuleSet): View
    {
        Gate::authorize('create', [TieBreakerRule::class, $scoringRuleSet]);

        return view('backoffice.scoring.tie-breakers.create', [
            'ruleSet' => $scoringRuleSet,
            'directions' => TieBreakerDirection::options(),
            'targets' => $this->targets(),
        ]);
    }

    public function store(StoreTieBreakerRuleRequest $request, ScoringRuleSet $scoringRuleSet): RedirectResponse
    {
        $rule = $scoringRuleSet->tieBreakerRules()->create($this->normalized($request->validated()));

        $this->audit('tie_breaker_create', AuditEvents::CREATE, $rule, $request);

        return to_route('backoffice.scoring.tie-breakers.index', $scoringRuleSet)
            ->with('success', 'Regra de desempate criada.');
    }

    public function edit(TieBreakerRule $tieBreakerRule): View
    {
        Gate::authorize('update', $tieBreakerRule);
        $tieBreakerRule->load('ruleSet');

        return view('backoffice.scoring.tie-breakers.edit', [
            'rule' => $tieBreakerRule,
            'ruleSet' => $tieBreakerRule->ruleSet,
            'directions' => TieBreakerDirection::options(),
            'targets' => $this->targets(),
        ]);
    }

    public function update(UpdateTieBreakerRuleRequest $request, TieBreakerRule $tieBreakerRule): RedirectResponse
    {
        $tieBreakerRule->update($this->normalized($request->validated()));
        $this->audit('tie_breaker_update', AuditEvents::UPDATE, $tieBreakerRule, $request);

        return to_route('backoffice.scoring.tie-breakers.index', $tieBreakerRule->ruleSet)
            ->with('success', 'Regra de desempate atualizada.');
    }

    public function activate(Request $request, TieBreakerRule $tieBreakerRule): RedirectResponse
    {
        Gate::authorize('activate', $tieBreakerRule);
        $tieBreakerRule->update(['is_active' => true]);
        $this->audit('tie_breaker_activate', AuditEvents::UPDATE, $tieBreakerRule, $request);

        return back()->with('success', 'Regra de desempate ativada.');
    }

    public function inactivate(Request $request, TieBreakerRule $tieBreakerRule): RedirectResponse
    {
        Gate::authorize('activate', $tieBreakerRule);
        $tieBreakerRule->update(['is_active' => false]);
        $this->audit('tie_breaker_inactivate', AuditEvents::UPDATE, $tieBreakerRule, $request);

        return back()->with('success', 'Regra de desempate inativada.');
    }

    /**
     * @return array<string, string>
     */
    private function targets(): array
    {
        return [
            'submitted_at' => 'Data de submissão',
            'monthly_income_per_capita' => 'Rendimento mensal per capita',
            'annual_income_per_capita' => 'Rendimento anual per capita',
            'number_of_dependents' => 'Número de dependentes',
            'number_of_minors' => 'Número de menores',
            'years_residing_in_municipality' => 'Anos de residência no município',
            'current_rent_burden' => 'Taxa de esforço',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        unset($data['scoring_rule_set_id']);
        $data['priority_order'] = $data['priority_order'] ?? 0;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function audit(string $action, string $event, TieBreakerRule $rule, Request $request): void
    {
        $this->auditLogger->record(
            event: $event,
            auditable: $rule,
            module: 'scoring',
            action: $action,
            description: 'Regra de desempate: '.$action.'.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );
    }
}
