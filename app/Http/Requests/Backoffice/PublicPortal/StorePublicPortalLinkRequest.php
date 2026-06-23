<?php

namespace App\Http\Requests\Backoffice\PublicPortal;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicPortalLinkRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:2048'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'opens_new_tab' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }
}
