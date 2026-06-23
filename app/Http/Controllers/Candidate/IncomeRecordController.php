<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\IncomeSourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncomeRecordRequest;
use App\Http\Requests\UpdateIncomeRecordRequest;
use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Services\Candidate\IncomeService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncomeRecordController extends Controller
{
    public function __construct(private readonly IncomeService $incomeService) {}

    public function index(Request $request): View|RedirectResponse
    {
        $household = $this->householdFor($request);

        if ($household === null) {
            return to_route('candidate.household.show')
                ->with('info', 'Crie o agregado antes de declarar rendimentos.');
        }

        Gate::authorize('view', $household);
        $household->load(['members.incomeRecords.incomeSource', 'incomeRecords']);
        $totals = $this->incomeService->totals($household);

        return view('candidate.income-records.index', compact('household', 'totals'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $household = $this->householdFor($request);

        if ($household === null) {
            return to_route('candidate.household.show');
        }

        Gate::authorize('update', $household);

        return view('candidate.income-records.create', [
            'household' => $household->load('members'),
            'incomeSources' => $this->incomeSources(),
        ]);
    }

    public function store(StoreIncomeRecordRequest $request): RedirectResponse
    {
        $household = $this->householdFor($request);
        abort_if($household === null, 404);

        $member = $household->members()->findOrFail($request->integer('household_member_id'));

        $this->incomeService->create($household, $member, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.income-records.index')
            ->with('success', 'Rendimento adicionado ao agregado.');
    }

    public function edit(Request $request, IncomeRecord $incomeRecord): View
    {
        Gate::authorize('update', $incomeRecord);

        $household = $incomeRecord->household;

        abort_unless($household instanceof Household, 404);

        $household->load('members');

        return view('candidate.income-records.edit', [
            'household' => $household,
            'incomeRecord' => $incomeRecord,
            'incomeSources' => $this->incomeSources(),
        ]);
    }

    public function update(
        UpdateIncomeRecordRequest $request,
        IncomeRecord $incomeRecord,
    ): RedirectResponse {
        $this->incomeService->update($incomeRecord, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.income-records.index')
            ->with('success', 'Rendimento atualizado.');
    }

    public function destroy(Request $request, IncomeRecord $incomeRecord): RedirectResponse
    {
        Gate::authorize('delete', $incomeRecord);
        $this->incomeService->delete($incomeRecord, $this->authenticatedUser($request));

        return to_route('candidate.income-records.index')
            ->with('success', 'Rendimento removido.');
    }

    private function householdFor(Request $request): ?Household
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration()->first();

        if (! $registration instanceof AdhesionRegistration) {
            return null;
        }

        $household = $registration->household()->first();

        return $household instanceof Household ? $household : null;
    }

    /**
     * @return Collection<int, IncomeSource>
     */
    private function incomeSources(): Collection
    {
        return IncomeSource::query()
            ->where('is_active', true)
            ->where('code', '!=', IncomeSourceType::NoIncome->value)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
