<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');

        return $household instanceof Household
            && $this->user()?->can('updateBackoffice', $household) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'citizen_id' => [
                'required',
                Rule::exists('citizens', 'id')
                    ->where('municipality_id', $this->user()->municipality_id ?? -1),
            ],
            'name' => ['required', 'string', 'max:255'],
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'members_count' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
