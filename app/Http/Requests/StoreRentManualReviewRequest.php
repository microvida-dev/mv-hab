<?php

namespace App\Http\Requests;

use App\Models\RentCalculation;
use App\Models\RentManualReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentManualReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $calculation = $this->route('rentCalculation');

        return $calculation instanceof RentCalculation
            && $this->user()?->can('createBackoffice', [RentManualReview::class, $calculation]) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $calculation = $this->route('rentCalculation');
        $calculationId = $calculation instanceof RentCalculation
            ? (int) $calculation->getKey()
            : 0;

        return [
            'rent_calculation_id' => [
                'required',
                'exists:rent_calculations,id',
                Rule::in([$calculationId]),
            ],
            'proposed_rent' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:10', 'max:5000'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
