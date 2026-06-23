<?php

namespace App\Http\Requests\Concerns;

use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesIncomeRecord
{
    use NormalizesCandidateBooleans;

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleans(['is_current', 'is_taxable']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function incomeRules(): array
    {
        return [
            'household_member_id' => ['required', 'integer', 'exists:household_members,id'],
            'income_source_id' => [
                'required',
                Rule::exists('income_sources', 'id')->where('is_active', true),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'monthly_amount' => ['nullable', 'numeric', 'min:0'],
            'annual_amount' => ['nullable', 'numeric', 'min:0'],
            'reference_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_current' => ['boolean'],
            'is_taxable' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! filled($this->input('monthly_amount')) && ! filled($this->input('annual_amount'))) {
                    $validator->errors()->add(
                        'monthly_amount',
                        'Indique pelo menos o rendimento mensal ou anual.',
                    );
                }

                $household = $this->currentHousehold();
                $memberId = (int) $this->input('household_member_id');

                if ($household !== null
                    && ! $household->members()->whereKey($memberId)->exists()) {
                    $validator->errors()->add(
                        'household_member_id',
                        'O membro selecionado não pertence ao seu agregado.',
                    );
                }
            },
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
}
