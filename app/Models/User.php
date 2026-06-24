<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;

#[Fillable([
    'municipality_id',
    'name',
    'email',
    'password',
    'status',
    'last_login_at',
    'mfa_required',
    'internal_notes',
    'deactivated_at',
    'deactivated_by',
    'reactivated_at',
    'reactivated_by',
])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'mfa_required' => 'boolean',
            'deactivated_at' => 'datetime',
            'reactivated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /** @return HasOne<AdhesionRegistration, $this> */
    public function adhesionRegistration(): HasOne
    {
        return $this->hasOne(AdhesionRegistration::class)->withTrashed();
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /** @return HasMany<EligibilityCheck, $this> */
    public function eligibilityChecks(): HasMany
    {
        return $this->hasMany(EligibilityCheck::class);
    }

    /** @return HasMany<AdministrativeProcess, $this> */
    public function assignedAdministrativeProcesses(): HasMany
    {
        return $this->hasMany(AdministrativeProcess::class, 'assigned_to');
    }

    /** @return HasMany<AdministrativeProcess, $this> */
    public function administrativeProcessesCreated(): HasMany
    {
        return $this->hasMany(AdministrativeProcess::class, 'created_by');
    }

    /** @return HasMany<CorrectionRequest, $this> */
    public function correctionRequestsIssued(): HasMany
    {
        return $this->hasMany(CorrectionRequest::class, 'issued_by');
    }

    /** @return HasMany<CorrectionResponse, $this> */
    public function correctionResponses(): HasMany
    {
        return $this->hasMany(CorrectionResponse::class);
    }

    /** @return HasMany<AdministrativeDecision, $this> */
    public function administrativeDecisions(): HasMany
    {
        return $this->hasMany(AdministrativeDecision::class, 'decided_by');
    }

    /** @return HasMany<AdministrativeTask, $this> */
    public function administrativeTasks(): HasMany
    {
        return $this->hasMany(AdministrativeTask::class, 'assigned_to');
    }

    /** @return HasMany<AdministrativeProcessNote, $this> */
    public function administrativeProcessNotes(): HasMany
    {
        return $this->hasMany(AdministrativeProcessNote::class);
    }

    /** @return HasMany<Complaint, $this> */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /** @return HasMany<Hearing, $this> */
    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class);
    }

    /** @return HasMany<OfficialNotification, $this> */
    public function officialNotifications(): HasMany
    {
        return $this->hasMany(OfficialNotification::class);
    }

    /** @return HasMany<CommunicationLog, $this> */
    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class, 'recipient_user_id');
    }

    /** @return HasOne<NotificationPreference, $this> */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /** @return HasMany<GeneratedOfficialDocument, $this> */
    public function generatedOfficialDocuments(): HasMany
    {
        return $this->hasMany(GeneratedOfficialDocument::class, 'recipient_user_id');
    }

    /** @return HasMany<HousingPreference, $this> */
    public function housingPreferences(): HasMany
    {
        return $this->hasMany(HousingPreference::class);
    }

    /** @return HasMany<Allocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /** @return HasMany<Contract, $this> */
    public function leaseContracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /** @return HasMany<TenantFinancialAccount, $this> */
    public function tenantFinancialAccounts(): HasMany
    {
        return $this->hasMany(TenantFinancialAccount::class);
    }

    /** @return HasOne<TenantProfile, $this> */
    public function tenantProfile(): HasOne
    {
        return $this->hasOne(TenantProfile::class);
    }

    /** @return HasMany<TenantContractAccess, $this> */
    public function tenantContractAccesses(): HasMany
    {
        return $this->hasMany(TenantContractAccess::class);
    }

    /** @return HasMany<TenantInvoice, $this> */
    public function tenantInvoices(): HasMany
    {
        return $this->hasMany(TenantInvoice::class);
    }

    /** @return HasMany<TenantPayment, $this> */
    public function tenantPayments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    /** @return HasMany<TenantCommunication, $this> */
    public function tenantCommunications(): HasMany
    {
        return $this->hasMany(TenantCommunication::class);
    }

    /** @return HasMany<RentInstallment, $this> */
    public function rentInstallments(): HasMany
    {
        return $this->hasMany(RentInstallment::class);
    }

    /** @return HasMany<LeasePayment, $this> */
    public function leasePayments(): HasMany
    {
        return $this->hasMany(LeasePayment::class);
    }

    /** @return HasMany<PaymentReceipt, $this> */
    public function paymentReceipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    /** @return HasMany<Arrear, $this> */
    public function arrears(): HasMany
    {
        return $this->hasMany(Arrear::class);
    }

    /** @return HasMany<RentReview, $this> */
    public function rentReviews(): HasMany
    {
        return $this->hasMany(RentReview::class);
    }

    /** @return HasMany<IncomeChangeDeclaration, $this> */
    public function incomeChangeDeclarations(): HasMany
    {
        return $this->hasMany(IncomeChangeDeclaration::class);
    }

    /** @return HasMany<AnnualDocumentUpdateRequest, $this> */
    public function annualDocumentUpdateRequests(): HasMany
    {
        return $this->hasMany(AnnualDocumentUpdateRequest::class);
    }

    /** @return HasMany<MaintenanceRequest, $this> */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /** @return HasMany<MaintenanceAssignment, $this> */
    public function assignedMaintenanceAssignments(): HasMany
    {
        return $this->hasMany(MaintenanceAssignment::class, 'assigned_user_id');
    }

    /** @return HasMany<MaintenanceIntervention, $this> */
    public function maintenanceInterventions(): HasMany
    {
        return $this->hasMany(MaintenanceIntervention::class, 'performed_by_user_id');
    }

    /** @return HasMany<PropertyInspection, $this> */
    public function propertyInspections(): HasMany
    {
        return $this->hasMany(PropertyInspection::class, 'inspector_user_id');
    }

    /** @return HasMany<RentCalculation, $this> */
    public function rentCalculations(): HasMany
    {
        return $this->hasMany(RentCalculation::class);
    }

    /** @return HasMany<AllocationOffer, $this> */
    public function allocationOffers(): HasMany
    {
        return $this->hasMany(AllocationOffer::class);
    }

    /** @return HasMany<ReserveListEntry, $this> */
    public function reserveListEntries(): HasMany
    {
        return $this->hasMany(ReserveListEntry::class);
    }

    /** @return HasMany<AuditEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }

    /** @return HasMany<AccessLog, $this> */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    /** @return HasMany<SensitiveDataAccessLog, $this> */
    public function sensitiveDataAccessLogs(): HasMany
    {
        return $this->hasMany(SensitiveDataAccessLog::class);
    }

    /**
     * @return HasMany<UserConsent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    /**
     * @return HasMany<DataSubjectRequest, $this>
     */
    public function dataSubjectRequests(): HasMany
    {
        return $this->hasMany(DataSubjectRequest::class);
    }

    /**
     * @return HasMany<DataExportPackage, $this>
     */
    public function dataExportPackages(): HasMany
    {
        return $this->hasMany(DataExportPackage::class);
    }

    /** @return HasMany<MfaDevice, $this> */
    public function mfaDevices(): HasMany
    {
        return $this->hasMany(MfaDevice::class);
    }

    /** @return HasMany<MfaRecoveryCode, $this> */
    public function mfaRecoveryCodes(): HasMany
    {
        return $this->hasMany(MfaRecoveryCode::class);
    }

    /** @return BelongsToMany<MunicipalTeam, $this> */
    public function municipalTeams(): BelongsToMany
    {
        return $this->belongsToMany(MunicipalTeam::class)
            ->withPivot(['role_in_team', 'joined_at', 'left_at', 'created_by'])
            ->withTimestamps();
    }

    /** @return HasMany<AccessChangeEvent, $this> */
    public function accessChangeEvents(): HasMany
    {
        return $this->hasMany(AccessChangeEvent::class, 'target_user_id');
    }

    /** @return HasMany<SecurityAlert, $this> */
    public function securityAlerts(): HasMany
    {
        return $this->hasMany(SecurityAlert::class);
    }

    /**
     * @return HasMany<SimulationSession, $this>
     */
    public function simulationSessions(): HasMany
    {
        return $this->hasMany(SimulationSession::class);
    }

    /**
     * @return HasMany<CandidateDataReuseProfile, $this>
     */
    public function candidateDataReuseProfiles(): HasMany
    {
        return $this->hasMany(CandidateDataReuseProfile::class);
    }

    /**
     * @return HasMany<ApplicationPrefill, $this>
     */
    public function applicationPrefills(): HasMany
    {
        return $this->hasMany(ApplicationPrefill::class);
    }

    /**
     * @return HasMany<RegistrationRenewal, $this>
     */
    public function registrationRenewals(): HasMany
    {
        return $this->hasMany(RegistrationRenewal::class);
    }

    /**
     * @return HasMany<HousingVisit, $this>
     */
    public function housingVisits(): HasMany
    {
        return $this->hasMany(HousingVisit::class, 'candidate_user_id');
    }

    /**
     * @return HasMany<VisitAvailability, $this>
     */
    public function visitAvailabilities(): HasMany
    {
        return $this->hasMany(VisitAvailability::class, 'staff_user_id');
    }

    /**
     * @return HasMany<VisitSlot, $this>
     */
    public function visitSlots(): HasMany
    {
        return $this->hasMany(VisitSlot::class, 'staff_user_id');
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function assignedSupportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    /**
     * @return HasMany<CandidateInteraction, $this>
     */
    public function candidateInteractions(): HasMany
    {
        return $this->hasMany(CandidateInteraction::class);
    }

    /**
     * @return HasMany<ApplicationSimulationInconsistency, $this>
     */
    public function applicationSimulationInconsistencies(): HasMany
    {
        return $this->hasMany(ApplicationSimulationInconsistency::class);
    }

    /**
     * @param  string|array<int, string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        return $this->roles()
            ->whereIn('name', Arr::wrap($roles))
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
        [$module, $action] = str_contains($permission, '.')
            ? explode('.', $permission, 2)
            : [$permission, null];

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission, $module, $action) {
                $query->where('name', '*')
                    ->orWhere('name', $permission)
                    ->orWhere('name', $module.'.*');

                if ($action !== null) {
                    $query->orWhere('name', '*.'.$action);
                }
            })
            ->exists();
    }

    public function hasPermissionTo(string $module, string $action): bool
    {
        return $this->hasPermission($module.'.'.$action);
    }

    public function assignRole(Role|string $role): void
    {
        $roleId = $role instanceof Role
            ? $role->getKey()
            : Role::query()->where('name', $role)->value('id');

        if ($roleId !== null) {
            $this->roles()->syncWithoutDetaching([$roleId]);
        }
    }
}
