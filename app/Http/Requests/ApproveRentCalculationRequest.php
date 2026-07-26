<?php

namespace App\Http\Requests;

use App\Models\RentCalculation;
use Illuminate\Foundation\Http\FormRequest;

class ApproveRentCalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $calculation = $this->route('rentCalculation');

        return $calculation instanceof RentCalculation
            && $this->user()?->can('approveBackoffice', $calculation) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['notes' => ['nullable', 'string', 'max:3000']];
    }
}
