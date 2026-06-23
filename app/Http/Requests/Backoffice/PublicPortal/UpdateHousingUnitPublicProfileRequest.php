<?php

namespace App\Http\Requests\Backoffice\PublicPortal;

use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Enums\PublicVisibilityStatus;
use App\Models\HousingUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHousingUnitPublicProfileRequest extends FormRequest
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
        $housingUnit = $this->route('housingUnit');
        $housingUnitId = $housingUnit instanceof HousingUnit ? $housingUnit->getKey() : null;

        return [
            'municipality_id' => ['nullable', 'exists:municipalities,id'],
            'public_reference' => ['nullable', 'string', 'max:100', Rule::unique('housing_units', 'public_reference')->ignore($housingUnitId)],
            'public_title' => ['required', 'string', 'max:255'],
            'public_slug' => ['nullable', 'string', 'max:255', Rule::unique('housing_units', 'public_slug')->ignore($housingUnitId)],
            'public_summary' => ['nullable', 'string', 'max:500'],
            'public_description' => ['nullable', 'string', 'max:5000'],
            'parish' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'floor' => ['nullable', 'string', 'max:50'],
            'gross_area_sqm' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'usable_area_sqm' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'energy_rating' => ['nullable', 'string', 'max:20'],
            'public_location_description' => ['nullable', 'string', 'max:255'],
            'public_address_visible' => ['boolean'],
            'public_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'public_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'public_location_precision' => ['required', Rule::in(HousingLocationPrecision::values())],
            'public_status' => ['required', Rule::in(HousingPublicStatus::values())],
            'public_visibility_status' => ['required', Rule::in(PublicVisibilityStatus::values())],
            'is_public' => ['boolean'],
            'public_sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function profileData(): array
    {
        $validated = $this->validated();
        $validated['public_address_visible'] = $this->boolean('public_address_visible');
        $validated['is_public'] = $this->boolean('is_public');

        return $validated;
    }
}
