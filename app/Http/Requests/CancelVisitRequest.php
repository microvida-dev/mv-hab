<?php

namespace App\Http\Requests;

use App\Enums\VisitCancellationReason;
use App\Models\HousingVisit;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visit = $this->route('housingVisit');
        $actor = $this->user();

        return $actor instanceof User
            && $visit instanceof HousingVisit
            && $actor->can('cancelBackoffice', $visit);
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
