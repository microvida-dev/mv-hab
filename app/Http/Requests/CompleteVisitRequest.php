<?php

namespace App\Http\Requests;

use App\Models\HousingVisit;
use Illuminate\Foundation\Http\FormRequest;

class CompleteVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visit = $this->route('housingVisit');

        return $visit instanceof HousingVisit && ($this->user()?->can('approve', $visit) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'staff_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
