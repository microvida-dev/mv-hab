<?php

namespace App\Http\Requests;

use App\Enums\HousingApplicationStatus;
use App\Models\HousingApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHousingApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createBackoffice', HousingApplication::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'citizen_id' => [
                'required',
                Rule::exists('citizens', 'id')
                    ->where('municipality_id', $this->user()->municipality_id ?? -1),
            ],
            'household_id' => [
                'nullable',
                Rule::exists('households', 'id')
                    ->where('municipality_id', $this->user()->municipality_id ?? -1),
            ],
            'status' => ['required', Rule::enum(HousingApplicationStatus::class)],
            'priority_score' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'submitted_at' => ['nullable', 'date'],
        ];
    }
}
