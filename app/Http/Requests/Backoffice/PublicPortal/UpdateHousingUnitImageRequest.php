<?php

namespace App\Http\Requests\Backoffice\PublicPortal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateHousingUnitImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'updateBackoffice',
            $this->route('image'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'is_cover' => ['boolean'],
            'is_public' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }
}
