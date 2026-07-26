<?php

namespace App\Models;

use App\Enums\PlatformOperatorGrantSource;
use App\Enums\PlatformOperatorStatus;
use Database\Factories\PlatformOperatorAssignmentFactory;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property PlatformOperatorStatus $status
 * @property PlatformOperatorGrantSource $grant_source
 * @property Carbon $granted_at
 * @property Carbon|null $revoked_at
 */
class PlatformOperatorAssignment extends Model
{
    /** @use HasFactory<PlatformOperatorAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'grant_source',
        'granted_by',
        'granted_at',
        'grant_justification',
        'approval_reference_primary',
        'approval_reference_secondary',
        'revoked_by',
        'revoked_at',
        'revoke_justification',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlatformOperatorStatus::class,
            'grant_source' => PlatformOperatorGrantSource::class,
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $assignment): void {
            $assignment->guardEvidenceInvariants();
        });
        static::deleting(fn (): bool => false);
    }

    /** @param Builder<PlatformOperatorAssignment> $query */
    public function scopeActive(Builder $query): void
    {
        $query
            ->where('status', PlatformOperatorStatus::Active)
            ->whereNull('revoked_at');
    }

    public function isActive(): bool
    {
        return $this->status === PlatformOperatorStatus::Active
            && $this->revoked_at === null;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    private function guardEvidenceInvariants(): void
    {
        if (trim((string) $this->grant_justification) === '') {
            throw new DomainException('A associação exige justificação de concessão.');
        }

        if ($this->status === PlatformOperatorStatus::Active
            && ($this->revoked_at !== null
                || $this->revoked_by !== null
                || $this->revoke_justification !== null)) {
            throw new DomainException('Uma associação ativa não pode conter evidência de revogação.');
        }

        if ($this->status === PlatformOperatorStatus::Revoked
            && ($this->revoked_at === null
                || trim((string) $this->revoke_justification) === '')) {
            throw new DomainException('Uma associação revogada exige data e justificação.');
        }

        if ($this->grant_source === PlatformOperatorGrantSource::Bootstrap
            && ($this->granted_by !== null
                || trim((string) $this->approval_reference_primary) === ''
                || trim((string) $this->approval_reference_secondary) === '')) {
            throw new DomainException('O bootstrap exige duas aprovações e não aceita ator interno.');
        }

        if ($this->grant_source === PlatformOperatorGrantSource::PlatformOperator
            && $this->granted_by === null) {
            throw new DomainException('A concessão normal exige um operador responsável.');
        }
    }
}
