<?php

namespace App\Http\Requests;

use App\Models\HousingVisit;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visit = $this->route('housingVisit');

        return $visit instanceof HousingVisit && ($this->user()?->can('update', $visit) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'new_visit_slot_id' => ['required', 'exists:visit_slots,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
