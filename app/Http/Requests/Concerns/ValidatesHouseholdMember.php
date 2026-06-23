<?php

namespace App\Http\Requests\Concerns;

use App\Enums\HouseholdRelationship;
use App\Enums\ProfessionalStatus;
use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesHouseholdMember
{
    use NormalizesCandidateBooleans;

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleans([
            'is_applicant',
            'works_in_municipality',
            'is_dependent',
            'is_student',
            'is_disabled',
            'has_multiple_disabilities',
            'is_pregnant',
            'has_reduced_mobility',
            'is_informal_caregiver',
            'has_no_income',
            'is_exempt_from_irs',
        ]);

        if ($this->filled('nif')) {
            $this->merge(['nif' => mb_strtoupper(trim((string) $this->input('nif')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function memberRules(): array
    {
        $household = $this->currentHousehold();
        $member = $this->route('member');

        return [
            'is_applicant' => ['boolean'],
            'full_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:50'],
            'relationship' => ['required', Rule::enum(HouseholdRelationship::class)],
            'nationality' => ['nullable', 'string', 'max:100'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_valid_until' => ['nullable', 'date'],
            'nif' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('household_members', 'nif')
                    ->where(fn ($query) => $query->where('household_id', $household?->id))
                    ->ignore($member),
            ],
            'marital_status' => ['nullable', 'string', 'max:100'],
            'professional_status' => ['nullable', Rule::enum(ProfessionalStatus::class)],
            'qualification_level' => ['nullable', 'integer', 'between:1,8'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'workplace_municipality' => ['nullable', 'string', 'max:100'],
            'works_in_municipality' => ['boolean'],
            'is_dependent' => ['boolean'],
            'is_student' => ['boolean'],
            'is_disabled' => ['boolean'],
            'has_multiple_disabilities' => ['boolean'],
            'is_pregnant' => ['boolean'],
            'disability_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'has_reduced_mobility' => ['boolean'],
            'is_informal_caregiver' => ['boolean'],
            'monthly_declared_income' => ['nullable', 'numeric', 'min:0'],
            'annual_declared_income' => ['nullable', 'numeric', 'min:0'],
            'has_no_income' => ['boolean'],
            'is_exempt_from_irs' => ['boolean'],
            'no_income_reason' => ['nullable', 'required_if:has_no_income,true', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function currentHousehold(): ?Household
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return null;
        }

        $registration = $user->adhesionRegistration()->first();

        if (! $registration instanceof AdhesionRegistration) {
            return null;
        }

        $household = $registration->household()->first();

        return $household instanceof Household ? $household : null;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('has_no_income')) {
                    return;
                }

                if ((float) $this->input('monthly_declared_income', 0) > 0
                    || (float) $this->input('annual_declared_income', 0) > 0) {
                    $validator->errors()->add(
                        'has_no_income',
                        'A ausência de rendimentos não pode coexistir com valores positivos.',
                    );
                }
            },
        ];
    }
}
