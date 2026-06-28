<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\Complaint;
use App\Models\Contest;
use App\Models\Contract;
use App\Models\DataSubjectRequest;
use App\Models\DocumentSubmission;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\SupportTicket;
use App\Services\Cases\CaseWorkspaceService;
use App\Services\Cases\EnterpriseCaseWorkspaceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CaseWorkspaceController extends Controller
{
    public function __construct(
        private readonly CaseWorkspaceService $cases,
        private readonly EnterpriseCaseWorkspaceService $enterpriseCases,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function application(Request $request, Application $application): View
    {
        $workspace = $this->cases->forApplication(
            $this->authenticatedUser($request),
            $application,
            $request->query('q'),
        );

        return view('cases.application.show', [
            'application' => $application,
            'workspace' => $workspace,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function contest(Request $request, Contest $contest): View
    {
        return $this->enterpriseView($request, 'contest', $contest);
    }

    /**
     * @throws AuthorizationException
     */
    public function contract(Request $request, Contract $contract): View
    {
        return $this->enterpriseView($request, 'contract', $contract);
    }

    /**
     * @throws AuthorizationException
     */
    public function maintenance(Request $request, MaintenanceRequest $maintenanceRequest): View
    {
        return $this->enterpriseView($request, 'maintenance_request', $maintenanceRequest);
    }

    /**
     * @throws AuthorizationException
     */
    public function inspection(Request $request, PropertyInspection $propertyInspection): View
    {
        return $this->enterpriseView($request, 'inspection', $propertyInspection);
    }

    /**
     * @throws AuthorizationException
     */
    public function complaint(Request $request, Complaint $complaint): View
    {
        return $this->enterpriseView($request, 'complaint', $complaint);
    }

    /**
     * @throws AuthorizationException
     */
    public function ticket(Request $request, SupportTicket $supportTicket): View
    {
        return $this->enterpriseView($request, 'support_ticket', $supportTicket);
    }

    /**
     * @throws AuthorizationException
     */
    public function housingUnit(Request $request, HousingUnit $housingUnit): View
    {
        return $this->enterpriseView($request, 'housing_unit', $housingUnit);
    }

    /**
     * @throws AuthorizationException
     */
    public function document(Request $request, DocumentSubmission $documentSubmission): View
    {
        return $this->enterpriseView($request, 'document_case', $documentSubmission);
    }

    /**
     * @throws AuthorizationException
     */
    public function rgpd(Request $request, DataSubjectRequest $dataSubjectRequest): View
    {
        return $this->enterpriseView($request, 'rgpd_request', $dataSubjectRequest);
    }

    /**
     * @throws AuthorizationException
     */
    public function audit(Request $request, AuditEvent $auditEvent): View
    {
        return $this->enterpriseView($request, 'audit_case', $auditEvent);
    }

    /**
     * @throws AuthorizationException
     */
    private function enterpriseView(Request $request, string $caseType, Model $case): View
    {
        $workspace = $this->enterpriseCases->forCase(
            $this->authenticatedUser($request),
            $caseType,
            $case,
            $request->query('q'),
        );

        return view('cases.enterprise.show', [
            'case' => $case,
            'workspace' => $workspace,
        ]);
    }
}
