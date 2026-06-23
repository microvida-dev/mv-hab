<?php

namespace App\Http\Requests\Backoffice\PublicPortal;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicPortalSettingsRequest extends FormRequest
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
        return [
            'show_map' => ['boolean'],
            'map_center_lat' => ['required', 'numeric', 'between:-90,90'],
            'map_center_lng' => ['required', 'numeric', 'between:-180,180'],
            'map_zoom' => ['required', 'integer', 'min:1', 'max:18'],
            'portal_title' => ['required', 'string', 'max:120'],
            'portal_description' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $validated = $this->validated();
        $validated['show_map'] = $this->boolean('show_map');

        return $validated;
    }
}
