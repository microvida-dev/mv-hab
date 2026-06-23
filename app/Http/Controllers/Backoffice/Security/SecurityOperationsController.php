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
use App\Services\Security\SensitiveFieldEncryptionReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SecurityOperationsController extends Controller
{
    public function alerts(Request $request): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('settings.audit'), 403);

        return view('backoffice.security.alerts', [
            'alerts' => SecurityAlert::query()->with('rule', 'user')->latest('detected_at')->paginate(20),
            'rules' => SecurityAlertRule::query()->latest()->get(),
        ]);
    }

    public function storeAlertRule(StoreSecurityAlertRuleRequest $request): RedirectResponse
    {
        SecurityAlertRule::query()->create($request->validated());

        return back()->with('status', 'Regra de alerta criada.');
    }

    public function updateAlertRule(UpdateSecurityAlertRuleRequest $request, SecurityAlertRule $securityAlertRule): RedirectResponse
    {
        $securityAlertRule->forceFill($request->validated())->save();

        return back()->with('status', 'Regra de alerta atualizada.');
    }

    public function reviewAlert(Request $request, SecurityAlert $securityAlert, SecurityAlertService $alerts): RedirectResponse
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('settings.audit'), 403);
        $alerts->review($securityAlert, $this->authenticatedUser($request));

        return back()->with('status', 'Alerta em análise.');
    }

    public function resolveAlert(ResolveSecurityAlertRequest $request, SecurityAlert $securityAlert, SecurityAlertService $alerts): RedirectResponse
    {
        $alerts->resolve($securityAlert, $this->authenticatedUser($request), $request->validated('resolution_notes'), $request->boolean('false_positive'));

        return back()->with('status', 'Alerta resolvido.');
    }

    public function storage(Request $request, DocumentStorageSecurityReviewService $storage): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('settings.audit'), 403);

        return view('backoffice.security.storage', ['review' => $storage->review()]);
    }

    public function encryptedFields(Request $request, SensitiveFieldEncryptionReviewService $fields): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('privacy.audit'), 403);
        $fields->seedDefaultRegistry($this->authenticatedUser($request));

        return view('backoffice.security.encrypted-fields', [
            'fields' => EncryptedFieldRegistry::query()->latest()->paginate(25),
        ]);
    }

    public function backups(Request $request): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('settings.audit'), 403);

        return view('backoffice.security.backups', [
            'reviews' => BackupReview::query()->latest('reviewed_at')->paginate(20),
        ]);
    }

    public function storeBackupReview(StoreBackupReviewRequest $request, BackupReviewService $backups): RedirectResponse
    {
        $backups->create($this->authenticatedUser($request), $request->validated());

        return back()->with('status', 'Revisão de backup registada.');
    }

    public function checklists(Request $request): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('settings.audit'), 403);

        return view('backoffice.security.checklists', [
            'checklists' => SecurityChecklist::query()->latest('started_at')->paginate(20),
        ]);
    }

    public function storeChecklist(StoreSecurityChecklistRequest $request, PreProductionSecurityChecklistService $checklists): RedirectResponse
    {
        $checklist = $checklists->create($this->authenticatedUser($request), $request->validated('environment') ?: 'pre-production');

        return redirect()->route('backoffice.security.checklists.show', $checklist)->with('status', 'Checklist criada.');
    }

    public function showChecklist(Request $request, SecurityChecklist $securityChecklist): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('settings.audit'), 403);

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
        abort_unless($this->authenticatedUser($request)->hasPermission('settings.audit') || $this->authenticatedUser($request)->hasPermission('privacy.approve'), 403);
        $checklists->approve($securityChecklist, $this->authenticatedUser($request));

        return back()->with('status', 'Checklist aprovada.');
    }
}
