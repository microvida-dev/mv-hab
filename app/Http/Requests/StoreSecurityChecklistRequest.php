<?php

namespace App\Http\Requests;

use App\Models\SecurityChecklist;
use Illuminate\Foundation\Http\FormRequest;

class StoreSecurityChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SecurityChecklist::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['environment' => ['nullable', 'string', 'max:80']];
    }
}
