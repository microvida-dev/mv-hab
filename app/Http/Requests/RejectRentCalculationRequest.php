<?php

namespace App\Http\Requests;

use App\Models\RentCalculation;
use Illuminate\Foundation\Http\FormRequest;

class RejectRentCalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $calculation = $this->route('rentCalculation');

        return $calculation instanceof RentCalculation
            && $this->user()?->can('rejectBackoffice', $calculation) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:3000']];
    }
}
