<?php

namespace App\Http\Requests;

use App\Enums\VisitCancellationReason;
use App\Models\HousingVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visit = $this->route('housingVisit');

        return $visit instanceof HousingVisit && ($this->user()?->can('cancel', $visit) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', Rule::in(VisitCancellationReason::values())],
            'cancellation_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
