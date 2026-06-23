<?php

namespace App\Models;

use App\Enums\AdhesionRegistrationStatus;
use Carbon\CarbonInterface;
use Database\Factories\AdhesionRegistrationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property AdhesionRegistrationStatus $status
 */
class AdhesionRegistration extends Model
{
    /** @use HasFactory<AdhesionRegistrationFactory> */
    use HasFactory, SoftDeletes;

    public const REQUIRED_FIELDS = [
        'full_name',
        'email',
        'nif',
        'birth_date',
        'address',
        'postal_code',
        'city',
        'municipality',
        'accepts_terms',
        'accepts_data_processing',
    ];

    public const REQUIRED_FIELD_LABELS = [
        'full_name' => 'Nome completo',
        'email' => 'Email',
        'nif' => 'NIF',
        'birth_date' => 'Data de nascimento',
        'address' => 'Morada',
        'postal_code' => 'Código postal',
        'city' => 'Localidade',
        'municipality' => 'Município',
        'accepts_terms' => 'Aceitação dos termos',
        'accepts_data_processing' => 'Aceitação do tratamento de dados',
    ];

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'mobile_phone',
        'document_type',
        'document_number',
        'document_valid_until',
        'nif',
        'birth_date',
        'nationality',
        'address',
        'postal_code',
        'city',
        'parish',
        'municipality',
        'wants_email_notifications',
        'wants_sms_notifications',
        'wants_postal_notifications',
        'accepts_terms',
        'accepts_data_processing',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AdhesionRegistrationStatus::class,
            'document_valid_until' => 'date',
            'birth_date' => 'date',
            'wants_email_notifications' => 'boolean',
            'wants_sms_notifications' => 'boolean',
            'wants_postal_notifications' => 'boolean',
            'accepts_terms' => 'boolean',
            'accepts_data_processing' => 'boolean',
            'accepted_terms_at' => 'datetime',
            'accepted_data_processing_at' => 'datetime',
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'removed_at' => 'datetime',
            'blocked_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AdhesionRegistrationStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(AdhesionRegistrationStatusHistory::class)
            ->latest();
    }

    /**
     * @return HasOne<Household, $this>
     */
    public function household(): HasOne
    {
        return $this->hasOne(Household::class);
    }

    /**
     * @return HasManyThrough<HouseholdMember, Household, $this>
     */
    public function householdMembers(): HasManyThrough
    {
        return $this->hasManyThrough(
            HouseholdMember::class,
            Household::class
        );
    }

    /**
     * @return HasMany<IncomeRecord, $this>
     */
    public function incomeRecords(): HasMany
    {
        return $this->hasMany(IncomeRecord::class);
    }

    /**
     * @return HasOne<CurrentHousingSituation, $this>
     */
    public function currentHousingSituation(): HasOne
    {
        return $this->hasOne(CurrentHousingSituation::class);
    }

    /**
     * @return HasMany<DocumentSubmission, $this>
     */
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return HasMany<EligibilityCheck, $this>
     */
    public function eligibilityChecks(): HasMany
    {
        return $this->hasMany(EligibilityCheck::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where(
            'status',
            AdhesionRegistrationStatus::Incomplete->value
        );
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where(
            'status',
            AdhesionRegistrationStatus::Registered->value
        );
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where(
            'status',
            AdhesionRegistrationStatus::Cancelled->value
        );
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AdhesionRegistrationStatus::Incomplete->value,
            AdhesionRegistrationStatus::Registered->value,
        ]);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where(
            'user_id',
            $user instanceof User ? $user->getKey() : $user
        );
    }

    public function canBeFinalized(): bool
    {
        return $this->status === AdhesionRegistrationStatus::Incomplete
            && $this->missingRequiredFields() === []
            && $this->isAdult();
    }

    public function canBeCancelled(): bool
    {
        return in_array(
            $this->status,
            [
                AdhesionRegistrationStatus::Incomplete,
                AdhesionRegistrationStatus::Registered,
            ],
            true
        );
    }

    public function canBeRemoved(): bool
    {
        return ! in_array(
            $this->status,
            [
                AdhesionRegistrationStatus::Removed,
                AdhesionRegistrationStatus::Blocked,
            ],
            true
        ) && ! $this->hasApplications();
    }

    public function markAsRegistered(): static
    {
        return $this->forceFill([
            'status' => AdhesionRegistrationStatus::Registered,
            'submitted_at' => now(),
            'cancelled_at' => null,
        ]);
    }

    public function markAsCancelled(): static
    {
        return $this->forceFill([
            'status' => AdhesionRegistrationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function markAsRemoved(): static
    {
        return $this->forceFill([
            'status' => AdhesionRegistrationStatus::Removed,
            'removed_at' => now(),
        ]);
    }

    public function completionPercentage(): int
    {
        $completed = count(self::REQUIRED_FIELDS)
            - count($this->missingRequiredFields());

        return (int) round(
            ($completed / count(self::REQUIRED_FIELDS)) * 100
        );
    }

    /**
     * @return list<string>
     */
    public function missingRequiredFields(): array
    {
        /** @var array<string, string> $labels */
        $labels = [
            'full_name' => 'Nome completo',
            'email' => 'Email',
            'nif' => 'NIF',
            'birth_date' => 'Data de nascimento',
            'address' => 'Morada',
            'postal_code' => 'Código postal',
            'city' => 'Localidade',
            'municipality' => 'Município',
            'accepts_terms' => 'Aceitação dos termos',
            'accepts_data_processing' => 'Aceitação do tratamento de dados',
        ];

        /** @var list<string> */
        return collect(self::REQUIRED_FIELDS)
            ->filter(function (string $field): bool {
                if (in_array(
                    $field,
                    ['accepts_terms', 'accepts_data_processing'],
                    true
                )) {
                    return $this->{$field} !== true;
                }

                return blank($this->{$field});
            })
            ->map(
                static fn (string $field): string => self::REQUIRED_FIELD_LABELS[$field] ?? $field
            )
            ->values()
            ->all();
    }

    public function isAdult(): bool
    {
        /** @var CarbonInterface|null $birthDate */
        $birthDate = $this->birth_date;

        return $birthDate !== null
            && $birthDate->lte(now()->subYears(18));
    }

    public function hasApplications(): bool
    {
        return $this->applications()->exists();
    }
}
