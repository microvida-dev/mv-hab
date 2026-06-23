<?php

namespace App\Models;

use App\Enums\HouseholdRelationship;
use App\Enums\ProfessionalStatus;
use Carbon\CarbonInterface;
use Database\Factories\HouseholdMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseholdMember extends Model
{
    /** @use HasFactory<HouseholdMemberFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'is_applicant',
        'full_name',
        'birth_date',
        'gender',
        'relationship',
        'nationality',
        'document_type',
        'document_number',
        'document_valid_until',
        'nif',
        'marital_status',
        'professional_status',
        'qualification_level',
        'employment_type',
        'employer_name',
        'workplace_municipality',
        'works_in_municipality',
        'is_dependent',
        'is_student',
        'is_disabled',
        'has_multiple_disabilities',
        'is_pregnant',
        'disability_percentage',
        'has_reduced_mobility',
        'is_informal_caregiver',
        'is_elderly',
        'has_no_income',
        'is_exempt_from_irs',
        'no_income_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_applicant' => 'boolean',
            'birth_date' => 'date',
            'relationship' => HouseholdRelationship::class,
            'document_valid_until' => 'date',
            'professional_status' => ProfessionalStatus::class,
            'qualification_level' => 'integer',
            'works_in_municipality' => 'boolean',
            'is_dependent' => 'boolean',
            'is_student' => 'boolean',
            'is_disabled' => 'boolean',
            'has_multiple_disabilities' => 'boolean',
            'is_pregnant' => 'boolean',
            'disability_percentage' => 'decimal:2',
            'has_reduced_mobility' => 'boolean',
            'is_informal_caregiver' => 'boolean',
            'is_elderly' => 'boolean',
            'monthly_declared_income' => 'decimal:2',
            'annual_declared_income' => 'decimal:2',
            'has_no_income' => 'boolean',
            'is_exempt_from_irs' => 'boolean',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<AdhesionRegistration, $this> */
    public function adhesionRegistration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class);
    }

    /** @return HasMany<IncomeRecord, $this> */
    public function incomeRecords(): HasMany
    {
        return $this->hasMany(IncomeRecord::class);
    }

    /** @return HasMany<DocumentSubmission, $this> */
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    public function age(): ?int
    {
        $birthDate = $this->getAttribute('birth_date');

        return $birthDate instanceof CarbonInterface
            ? $birthDate->age
            : null;
    }
}
