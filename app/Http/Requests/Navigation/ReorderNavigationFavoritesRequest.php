<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Foundation\Http\FormRequest;

class ReorderNavigationFavoritesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'favorites' => ['required', 'array', 'max:50'],
            'favorites.*' => ['integer', 'distinct', 'exists:navigation_favorites,id'],
        ];
    }
}
