<?php

namespace App\Http\Controllers\Backoffice\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveSecurityAlertRequest;
use App\Http\Requests\StoreBackupReviewRequest;
use App\Http\Requests\StoreSecurityAlertRuleRequest;
use App\Http\Requests\StoreSecurityChecklistRequest;
use App\Http\Requests\UpdateSecurityAlertRuleRequest;
use App\Http\Requests\UpdateSecurityChecklistItemRequest;
use App\Models\BackupReview;
use App\Models\EncryptedFieldRegistry;
use App\Models\SecurityAlert;
use App\Models\SecurityAlertRule;
use App\Models\SecurityChecklist;
use App\Models\SecurityChecklistItem;
use App\Services\Security\BackupReviewService;
use App\Services\Security\DocumentStorageSecurityReviewService;
use App\Services\Security\PreProductionSecurityChecklistService;
use App\Services\Security\SecurityAlertService;
use App\Services\Security\SecurityMunicipalScopeService;
use App\Services\Security\SensitiveFieldEncryptionReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SecurityOperationsController extends Controller
{
    public function __construct(
        private readonly SecurityMunicipalScopeService $scope,
    ) {}

    public function alerts(Request $request): View
    {
        $actor = $this->authenticatedUser($request);
        Gate::authorize('viewAny', SecurityAlert::class);

        return view('backoffice.security.alerts', [
            'alerts' => $this->scope
                ->alerts(SecurityAlert::query(), $actor)
                ->with('rule', 'user')
                ->latest('detected_at')
                ->paginate(20),
            'rules' => $this->scope
                ->alertRules(SecurityAlertRule::query(), $actor)
                ->latest()
                ->get(),
        ]);
    }

    public function storeAlertRule(StoreSecurityAlertRuleRequest $request): RedirectResponse
    {
        $actor = $this->authenticatedUser($request);
        Gate::authorize('create', SecurityAlertRule::class);
        SecurityAlertRule::query()->create([
            ...$request->validated(),
            'municipality_id' => $actor->municipality_id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        return back()->with('status', 'Regra de alerta criada.');
    }

    public function updateAlertRule(UpdateSecurityAlertRuleRequest $request, SecurityAlertRule $securityAlertRule): RedirectResponse
    {
        Gate::authorize('update', $securityAlertRule);
        $securityAlertRule->forceFill([
            ...$request->validated(),
            'updated_by' => $this->authenticatedUser($request)->id,
        ])->save();

        return back()->with('status', 'Regra de alerta atualizada.');
    }

    public function reviewAlert(Request $request, SecurityAlert $securityAlert, SecurityAlertService $alerts): RedirectResponse
    {
        Gate::authorize('update', $securityAlert);
        $alerts->review($securityAlert, $this->authenticatedUser($request));

        return back()->with('status', 'Alerta em análise.');
    }

    public function resolveAlert(ResolveSecurityAlertRequest $request, SecurityAlert $securityAlert, SecurityAlertService $alerts): RedirectResponse
    {
        Gate::authorize('resolve', $securityAlert);
        $alerts->resolve($securityAlert, $this->authenticatedUser($request), $request->validated('resolution_notes'), $request->boolean('false_positive'));

        return back()->with('status', 'Alerta resolvido.');
    }

    public function storage(Request $request, DocumentStorageSecurityReviewService $storage): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('security.view'), 403);

        return view('backoffice.security.storage', ['review' => $storage->review()]);
    }

    public function encryptedFields(Request $request, SensitiveFieldEncryptionReviewService $fields): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('security.view'), 403);

        return view('backoffice.security.encrypted-fields', [
            'fields' => EncryptedFieldRegistry::query()->latest()->paginate(25),
        ]);
    }

    public function backups(Request $request): View
    {
        $actor = $this->authenticatedUser($request);
        Gate::authorize('viewAny', BackupReview::class);

        return view('backoffice.security.backups', [
            'reviews' => $this->scope
                ->backupReviews(BackupReview::query(), $actor)
                ->latest('reviewed_at')
                ->paginate(20),
        ]);
    }

    public function storeBackupReview(StoreBackupReviewRequest $request, BackupReviewService $backups): RedirectResponse
    {
        $backups->create($this->authenticatedUser($request), $request->validated());

        return back()->with('status', 'Revisão de backup registada.');
    }

    public function checklists(Request $request): View
    {
        $actor = $this->authenticatedUser($request);
        Gate::authorize('viewAny', SecurityChecklist::class);

        return view('backoffice.security.checklists', [
            'checklists' => $this->scope
                ->checklists(SecurityChecklist::query(), $actor)
                ->latest('started_at')
                ->paginate(20),
        ]);
    }

    public function storeChecklist(StoreSecurityChecklistRequest $request, PreProductionSecurityChecklistService $checklists): RedirectResponse
    {
        $checklist = $checklists->create($this->authenticatedUser($request), $request->validated('environment') ?: 'pre-production');

        return redirect()->route('backoffice.security.checklists.show', $checklist)->with('status', 'Checklist criada.');
    }

    public function showChecklist(Request $request, SecurityChecklist $securityChecklist): View
    {
        Gate::authorize('view', $securityChecklist);

        return view('backoffice.security.checklist', [
            'checklist' => $securityChecklist->load('items'),
        ]);
    }

    public function updateChecklistItem(UpdateSecurityChecklistItemRequest $request, SecurityChecklistItem $securityChecklistItem, PreProductionSecurityChecklistService $checklists): RedirectResponse
    {
        $checklists->updateItem($securityChecklistItem, $this->authenticatedUser($request), $request->validated('status'), $request->validated('evidence'));

        return back()->with('status', 'Item de checklist atualizado.');
    }

    public function approveChecklist(Request $request, SecurityChecklist $securityChecklist, PreProductionSecurityChecklistService $checklists): RedirectResponse
    {
        Gate::authorize('approve', $securityChecklist);
        $checklists->approve($securityChecklist, $this->authenticatedUser($request));

        return back()->with('status', 'Checklist aprovada.');
    }
}
