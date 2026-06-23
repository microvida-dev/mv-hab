<?php

namespace App\Http\Requests\Backoffice\PublicPortal;

use Illuminate\Foundation\Http\FormRequest;

class StoreHousingUnitImageRequest extends FormRequest
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
            'image' => ['required', 'image', 'max:5120'],
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'is_cover' => ['boolean'],
            'is_public' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }
}
