<?php

namespace App\Http\Requests;

use App\Models\MfaDevice;
use Illuminate\Foundation\Http\FormRequest;

class EnableMfaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', MfaDevice::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['name' => ['nullable', 'string', 'max:255']];
    }
}
