<?php

namespace App\Models;

use App\Enums\TenantTransitionStatus;
use Database\Factories\TenantTransitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $winner_registration_id
 * @property int|null $key_handover_appointment_id
 * @property int|null $allocation_id
 * @property int|null $lease_contract_id
 * @property int|null $tenant_financial_account_id
 * @property int $application_id
 * @property int $user_id
 * @property int|null $housing_unit_id
 * @property TenantTransitionStatus $status
 * @property Carbon|null $completed_at
 * @property-read WinnerRegistration $winnerRegistration
 */
class TenantTransition extends Model
{
    /** @use HasFactory<TenantTransitionFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'completed_at', 'completed_by', 'blocked_at', 'failed_at', 'cancelled_at', 'cancelled_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => TenantTransitionStatus::class,
            'preconditions' => 'array',
            'warnings' => 'array',
            'metadata' => 'array',
            'completed_at' => 'datetime',
            'blocked_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WinnerRegistration, $this> */
    public function winnerRegistration(): BelongsTo
    {
        return $this->belongsTo(WinnerRegistration::class);
    }

    /** @return BelongsTo<KeyHandoverAppointment, $this> */
    public function keyHandoverAppointment(): BelongsTo
    {
        return $this->belongsTo(KeyHandoverAppointment::class);
    }

    /** @return BelongsTo<Allocation, $this> */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
    }

    /** @return BelongsTo<Contract, $this> */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /** @return BelongsTo<TenantFinancialAccount, $this> */
    public function tenantFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(TenantFinancialAccount::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }
}
