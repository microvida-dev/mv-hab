<?php

namespace App\Models;

use Database\Factories\WorkTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkTask extends Model
{
    /** @use HasFactory<WorkTaskFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_ANALYSIS = 'in_analysis';

    public const STATUS_WAITING_CANDIDATE = 'waiting_candidate';

    public const STATUS_WAITING_INTERNAL = 'waiting_internal';

    public const STATUS_WAITING_EXTERNAL = 'waiting_external';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_OVERDUE = 'overdue';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const TYPE_DOCUMENT_REVIEW = 'document_review';

    public const TYPE_ELIGIBILITY_REVIEW = 'eligibility_review';

    public const TYPE_SCORING_REVIEW = 'scoring_review';

    public const TYPE_COMPLAINT_REVIEW = 'complaint_review';

    public const TYPE_HEARING_REVIEW = 'hearing_review';

    public const TYPE_CONTRACT_REVIEW = 'contract_review';

    public const TYPE_RENT_REVIEW = 'rent_review';

    public const TYPE_PAYMENT_REVIEW = 'payment_review';

    public const TYPE_MAINTENANCE_TRIAGE = 'maintenance_triage';

    public const TYPE_INSPECTION_SCHEDULE = 'inspection_schedule';

    public const TYPE_VISIT_SCHEDULE = 'visit_schedule';

    public const TYPE_SUPPORT_TICKET = 'support_ticket';

    public const TYPE_RGPD_REQUEST = 'rgpd_request';

    public const TYPE_AUDIT_REVIEW = 'audit_review';

    protected $fillable = [
        'task_number',
        'type',
        'source',
        'related_type',
        'related_id',
        'priority',
        'status',
        'municipal_team_id',
        'assigned_user_id',
        'due_at',
        'assigned_at',
        'completed_at',
        'cancelled_at',
        'reassignment_reason',
        'cancellation_reason',
        'outcome_note',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'task_number';
    }

    /** @return MorphTo<Model, $this> */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<MunicipalTeam, $this> */
    public function municipalTeam(): BelongsTo
    {
        return $this->belongsTo(MunicipalTeam::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<WorkTaskHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(WorkTaskHistory::class)->latest('occurred_at');
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assigned_user_id === $user->getKey();
    }

    public function isInTeamOf(User $user): bool
    {
        if ($this->municipal_team_id === null) {
            return false;
        }

        return $user->municipalTeams()
            ->where('municipal_teams.id', $this->municipal_team_id)
            ->wherePivotNull('left_at')
            ->exists();
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_ASSIGNED => 'Atribuída',
            self::STATUS_IN_ANALYSIS => 'Em análise',
            self::STATUS_WAITING_CANDIDATE => 'Em espera pelo candidato',
            self::STATUS_WAITING_INTERNAL => 'Em espera interna',
            self::STATUS_WAITING_EXTERNAL => 'Em espera externa',
            self::STATUS_COMPLETED => 'Concluída',
            self::STATUS_CANCELLED => 'Cancelada',
            self::STATUS_OVERDUE => 'Vencida',
            default => $status,
        };
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_DOCUMENT_REVIEW => 'Revisão documental',
            self::TYPE_ELIGIBILITY_REVIEW => 'Revisão de elegibilidade',
            self::TYPE_SCORING_REVIEW => 'Revisão de classificação',
            self::TYPE_COMPLAINT_REVIEW => 'Reclamação',
            self::TYPE_HEARING_REVIEW => 'Audiência prévia',
            self::TYPE_CONTRACT_REVIEW => 'Contrato',
            self::TYPE_RENT_REVIEW => 'Renda',
            self::TYPE_PAYMENT_REVIEW => 'Pagamento',
            self::TYPE_MAINTENANCE_TRIAGE => 'Triagem de manutenção',
            self::TYPE_INSPECTION_SCHEDULE => 'Agendamento de vistoria',
            self::TYPE_VISIT_SCHEDULE => 'Agendamento de visita',
            self::TYPE_SUPPORT_TICKET => 'Pedido de apoio',
            self::TYPE_RGPD_REQUEST => 'Pedido RGPD',
            self::TYPE_AUDIT_REVIEW => 'Revisão de auditoria',
            default => $type,
        };
    }
}
