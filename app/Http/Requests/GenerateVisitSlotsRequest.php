<?php

namespace App\Http\Requests;

use App\Models\VisitAvailability;
use Illuminate\Foundation\Http\FormRequest;

class GenerateVisitSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $availability = $this->route('visitAvailability');

        return $availability instanceof VisitAvailability
            && ($this->user()?->can('update', $availability) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_point' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
