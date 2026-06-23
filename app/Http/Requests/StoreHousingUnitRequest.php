<?php

namespace App\Http\Requests;

use App\Enums\HousingUnitStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHousingUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', Rule::unique('housing_units', 'code')],
            'address' => ['required', 'string', 'max:255'],
            'typology' => ['required', 'string', 'max:100'],
            'bedrooms' => ['required', 'integer', 'min:0'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(HousingUnitStatus::class)],
        ];
    }
}
