<?php

namespace App\Http\Controllers\Backoffice\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignDataSubjectRequestRequest;
use App\Http\Requests\CompleteDataSubjectRequestRequest;
use App\Http\Requests\GenerateDataExportPackageRequest;
use App\Http\Requests\RejectDataSubjectRequestRequest;
use App\Http\Requests\RunRetentionSimulationRequest;
use App\Http\Requests\StoreAnonymizationRequestRequest;
use App\Http\Requests\StoreConsentPurposeRequest;
use App\Http\Requests\StoreDataSubjectRequestRequest;
use App\Http\Requests\StoreRetentionPolicyRequest;
use App\Http\Requests\UpdateConsentPurposeRequest;
use App\Http\Requests\UpdateRetentionPolicyRequest;
use App\Models\AnonymizationRequest;
use App\Models\ConsentPurpose;
use App\Models\DataExportPackage;
use App\Models\DataSubjectRequest;
use App\Models\RetentionExecution;
use App\Models\RetentionPolicy;
use App\Models\User;
use App\Services\Rgpd\AnonymizationService;
use App\Services\Rgpd\ConsentPurposeService;
use App\Services\Rgpd\DataExportService;
use App\Services\Rgpd\DataSubjectRequestService;
use App\Services\Rgpd\RetentionExecutionService;
use App\Services\Rgpd\RetentionPolicyService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivacyController extends Controller
{
    private const PER_PAGE = 20;

    private function authorizePermission(Request $request, string $permission): User
    {
        $user = $this->authenticatedUser($request);

        abort_unless(
            $user->hasPermission($permission),
            403
        );

        return $user;
    }

    /**
     * @return Collection<int, User>
     */
    private function assignableUsers(): Collection
    {
        return User::query()
            ->orderBy('name')
            ->limit(100)
            ->get([
                'id',
                'name',
                'email',
            ]);
    }

    private function findUserOrFail(mixed $id): User
    {
        $user = User::query()->findOrFail($id);

        abort_unless($user instanceof User, 404);

        return $user;
    }

    public function purposes(Request $request): View
    {
        $this->authorizePermission($request, 'privacy.view');

        return view('backoffice.security.privacy.purposes', [
            'purposes' => ConsentPurpose::query()
                ->latest()
                ->paginate(self::PER_PAGE),
        ]);
    }

    public function storePurpose(
        StoreConsentPurposeRequest $request,
        ConsentPurposeService $purposes
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        $purposes->create(
            $request->validated(),
            $user
        );

        return back()->with('status', 'Finalidade RGPD criada.');
    }

    public function updatePurpose(
        UpdateConsentPurposeRequest $request,
        ConsentPurpose $consentPurpose,
        ConsentPurposeService $purposes
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        $purposes->update(
            $consentPurpose,
            $request->validated(),
            $user
        );

        return back()->with('status', 'Finalidade RGPD atualizada.');
    }

    public function requests(Request $request): View
    {
        $this->authorizePermission($request, 'privacy.view');

        return view('backoffice.security.privacy.requests', [
            'requests' => DataSubjectRequest::query()
                ->with([
                    'user',
                    'assignedTo',
                ])
                ->latest('received_at')
                ->paginate(self::PER_PAGE),

            'users' => $this->assignableUsers(),
        ]);
    }

    public function storeRequest(
        StoreDataSubjectRequestRequest $request,
        DataSubjectRequestService $requests
    ): RedirectResponse {
        $user = $this->authorizePermission($request, 'privacy.create');

        $data = $request->validated();

        $subject = ! empty($data['user_id'])
            ? $this->findUserOrFail($data['user_id'])
            : null;

        $rgpdRequest = $requests->create(
            $data,
            $subject,
            $user
        );

        return redirect()
            ->route(
                'backoffice.security.privacy.requests.show',
                $rgpdRequest
            )
            ->with('status', 'Pedido RGPD criado.');
    }

    public function showRequest(
        Request $request,
        DataSubjectRequest $dataSubjectRequest
    ): View {
        $this->authorizePermission($request, 'privacy.view');

        $dataSubjectRequest->loadMissing([
            'user',
            'assignedTo',
            'actions',
            'exports',
            'anonymizationRequests',
        ]);

        return view(
            'backoffice.security.privacy.request',
            [
                'requestRecord' => $dataSubjectRequest,
                'users' => $this->assignableUsers(),
            ]
        );
    }

    public function assignRequest(
        AssignDataSubjectRequestRequest $request,
        DataSubjectRequest $dataSubjectRequest,
        DataSubjectRequestService $requests
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        $requests->assign(
            $dataSubjectRequest,
            $this->findUserOrFail($request->validated('assigned_to')),
            $user
        );

        return back()->with('status', 'Pedido RGPD atribuído.');
    }

    public function completeRequest(
        CompleteDataSubjectRequestRequest $request,
        DataSubjectRequest $dataSubjectRequest,
        DataSubjectRequestService $requests
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        $requests->complete(
            $dataSubjectRequest,
            $user,
            $request->validated('summary')
        );

        return back()->with('status', 'Pedido RGPD concluído.');
    }

    public function rejectRequest(
        RejectDataSubjectRequestRequest $request,
        DataSubjectRequest $dataSubjectRequest,
        DataSubjectRequestService $requests
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        $requests->reject(
            $dataSubjectRequest,
            $user,
            $request->validated('reason')
        );

        return back()->with('status', 'Pedido RGPD rejeitado.');
    }

    public function generateExport(
        GenerateDataExportPackageRequest $request,
        DataSubjectRequest $dataSubjectRequest,
        DataExportService $exports
    ): RedirectResponse {
        $package = $exports->generate(
            $dataSubjectRequest,
            $this->authenticatedUser($request)
        );

        return redirect()
            ->route(
                'backoffice.security.privacy.exports.show',
                $package
            )
            ->with(
                'status',
                'Pacote de exportação RGPD gerado.'
            );
    }

    public function showExport(
        Request $request,
        DataExportPackage $dataExportPackage
    ): View {
        $this->authorizePermission($request, 'privacy.view');

        $dataExportPackage->loadMissing([
            'request',
            'user',
        ]);

        return view(
            'backoffice.security.privacy.export',
            [
                'package' => $dataExportPackage,
            ]
        );
    }

    public function downloadExport(
        Request $request,
        DataExportPackage $dataExportPackage,
        DataExportService $exports
    ): StreamedResponse {
        $user = $this->authorizePermission(
            $request,
            'privacy.export'
        );

        return $exports->download(
            $dataExportPackage,
            $user
        );
    }

    public function retention(Request $request): View
    {
        $this->authorizePermission($request, 'privacy.view');

        return view(
            'backoffice.security.privacy.retention',
            [
                'policies' => RetentionPolicy::query()
                    ->with('executions')
                    ->latest()
                    ->paginate(self::PER_PAGE),

                'executions' => RetentionExecution::query()
                    ->with('policy')
                    ->latest()
                    ->limit(15)
                    ->get(),
            ]
        );
    }

    public function storeRetention(
        StoreRetentionPolicyRequest $request,
        RetentionPolicyService $policies
    ): RedirectResponse {
        $policies->create(
            $request->validated(),
            $this->authenticatedUser($request)
        );

        return back()->with(
            'status',
            'Política de retenção criada.'
        );
    }

    public function updateRetention(
        UpdateRetentionPolicyRequest $request,
        RetentionPolicy $retentionPolicy,
        RetentionPolicyService $policies
    ): RedirectResponse {
        $policies->update(
            $retentionPolicy,
            $request->validated()
        );

        return back()->with(
            'status',
            'Política de retenção atualizada.'
        );
    }

    public function simulateRetention(
        RunRetentionSimulationRequest $request,
        RetentionPolicy $retentionPolicy,
        RetentionExecutionService $executions
    ): RedirectResponse {
        $execution = $executions->simulate(
            $retentionPolicy,
            $this->authenticatedUser($request)
        );

        return back()->with(
            'status',
            sprintf(
                'Simulação executada: %d registos encontrados.',
                $execution->matched_records_count
            )
        );
    }

    public function approveRetention(
        Request $request,
        RetentionExecution $retentionExecution,
        RetentionExecutionService $executions
    ): RedirectResponse {
        $user = $this->authorizePermission(
            $request,
            'privacy.approve'
        );

        $executions->approve(
            $retentionExecution,
            $user
        );

        return back()->with(
            'status',
            'Execução de retenção aprovada.'
        );
    }

    public function runRetention(
        Request $request,
        RetentionExecution $retentionExecution,
        RetentionExecutionService $executions
    ): RedirectResponse {
        $user = $this->authorizePermission(
            $request,
            'privacy.approve'
        );

        $executions->run(
            $retentionExecution,
            $user
        );

        return back()->with(
            'status',
            'Execução de retenção registada.'
        );
    }

    public function anonymization(
        Request $request
    ): View {
        $this->authorizePermission($request, 'privacy.view');

        return view(
            'backoffice.security.privacy.anonymization',
            [
                'requests' => AnonymizationRequest::query()
                    ->with([
                        'user',
                        'request',
                    ])
                    ->latest()
                    ->paginate(self::PER_PAGE),

                'users' => $this->assignableUsers(),
            ]
        );
    }

    public function storeAnonymization(
        StoreAnonymizationRequestRequest $request,
        AnonymizationService $anonymization
    ): RedirectResponse {
        $anonRequest = $anonymization->create(
            $request->payload(),
            $this->authenticatedUser($request)
        );

        return redirect()
            ->route(
                'backoffice.security.privacy.anonymization.show',
                $anonRequest
            )
            ->with(
                'status',
                'Pedido de anonimização criado.'
            );
    }

    public function showAnonymization(
        Request $request,
        AnonymizationRequest $anonymizationRequest
    ): View {
        $this->authorizePermission($request, 'privacy.view');

        $anonymizationRequest->loadMissing([
            'user',
            'request',
        ]);

        return view(
            'backoffice.security.privacy.anonymization-show',
            [
                'anonymizationRequest' => $anonymizationRequest,
            ]
        );
    }

    public function approveAnonymization(
        Request $request,
        AnonymizationRequest $anonymizationRequest,
        AnonymizationService $anonymization
    ): RedirectResponse {
        $user = $this->authorizePermission(
            $request,
            'privacy.approve'
        );

        $anonymization->approve(
            $anonymizationRequest,
            $user
        );

        return back()->with(
            'status',
            'Pedido de anonimização aprovado.'
        );
    }

    public function runAnonymization(
        Request $request,
        AnonymizationRequest $anonymizationRequest,
        AnonymizationService $anonymization
    ): RedirectResponse {
        $user = $this->authorizePermission(
            $request,
            'privacy.approve'
        );

        $anonymization->run(
            $anonymizationRequest,
            $user
        );

        return back()->with(
            'status',
            'Anonimização executada.'
        );
    }
}
