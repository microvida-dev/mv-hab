<?php

namespace App\Http\Requests;

use App\Models\HousingVisit;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visit = $this->route('housingVisit');
        $actor = $this->user();

        return $actor instanceof User
            && $visit instanceof HousingVisit
            && $actor->can('update', $visit);
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
