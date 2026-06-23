<?php

namespace App\Http\Requests\PublicPortal;

use App\Enums\HousingPublicStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchHousingOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rentMaxRules = ['nullable', 'numeric', 'min:0'];

        if ($this->filled('rent_min')) {
            $rentMaxRules[] = 'gte:rent_min';
        }

        return [
            'q' => ['nullable', 'string', 'max:120'],
            'typology' => ['nullable', 'string', 'max:50'],
            'parish' => ['nullable', 'string', 'max:120'],
            'public_status' => ['nullable', Rule::in(HousingPublicStatus::values())],
            'rent_min' => ['nullable', 'numeric', 'min:0'],
            'rent_max' => $rentMaxRules,
            'contest' => ['nullable', 'string', 'max:160'],
            'contest_status' => ['nullable', Rule::in(['open', 'upcoming', 'closed'])],
            'accessible' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['published_desc', 'rent_asc', 'rent_desc', 'typology'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return collect($this->validated())
            ->filter(fn (mixed $value) => $value !== null && $value !== '')
            ->all();
    }
}
