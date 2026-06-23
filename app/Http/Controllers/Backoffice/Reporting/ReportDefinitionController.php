<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Enums\ReportSensitivityLevel;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\StoreReportDefinitionRequest;
use App\Http\Requests\Reporting\UpdateReportDefinitionRequest;
use App\Models\ReportDefinition;
use App\Services\Reporting\ReportDefinitionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReportDefinitionController extends Controller
{
    public function __construct(private readonly ReportDefinitionService $definitions) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ReportDefinition::class);
        $allowed = ReportDefinition::query()->get()->filter(fn ($report) => $this->currentUser()->can('view', $report))->pluck('id');

        return view('backoffice.reports.definitions.index', ['reports' => ReportDefinition::query()->whereIn('id', $allowed)->orderBy('name')->paginate(30)]);
    }

    public function create(): View
    {
        Gate::authorize('create', ReportDefinition::class);

        return view('backoffice.reports.definitions.create', $this->formOptions());
    }

    public function store(StoreReportDefinitionRequest $request): RedirectResponse
    {
        $report = $this->definitions->create($request->validated(), $this->authenticatedUser($request));

        return redirect()->route('backoffice.reports.definitions.show', $report)->with('success', 'Relatório criado.');
    }

    public function show(ReportDefinition $reportDefinition): View
    {
        Gate::authorize('view', $reportDefinition);

        return view('backoffice.reports.definitions.show', ['report' => $reportDefinition->load('presets')]);
    }

    public function edit(ReportDefinition $reportDefinition): View
    {
        Gate::authorize('update', $reportDefinition);

        return view('backoffice.reports.definitions.edit', ['report' => $reportDefinition] + $this->formOptions());
    }

    public function update(UpdateReportDefinitionRequest $request, ReportDefinition $reportDefinition): RedirectResponse
    {
        $this->definitions->update($reportDefinition, $request->validated(), $this->authenticatedUser($request));

        return redirect()->route('backoffice.reports.definitions.show', $reportDefinition)->with('success', 'Relatório atualizado.');
    }

    public function destroy(ReportDefinition $reportDefinition): RedirectResponse
    {
        Gate::authorize('delete', $reportDefinition);
        $reportDefinition->delete();

        return redirect()->route('backoffice.reports.definitions.index')->with('success', 'Relatório arquivado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'types' => ReportType::options(),
            'sensitivities' => ReportSensitivityLevel::options(),
            'formats' => ReportFormat::options(),
            'scopes' => ExportScope::options(),
        ];
    }
}
