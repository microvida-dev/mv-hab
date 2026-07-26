<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('contract');

        return $contract instanceof Contract
            && ($this->user()?->can('updateBackoffice', $contract) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()?->municipality_id;

        return [
            'citizen_id' => [
                'required',
                Rule::exists('citizens', 'id')->when(
                    $municipalityId !== null,
                    fn ($rule) => $rule->where('municipality_id', $municipalityId),
                ),
            ],
            'housing_unit_id' => [
                'required',
                Rule::exists('housing_units', 'id')->when(
                    $municipalityId !== null,
                    fn ($rule) => $rule->where('municipality_id', $municipalityId),
                ),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
        ];
    }
}
