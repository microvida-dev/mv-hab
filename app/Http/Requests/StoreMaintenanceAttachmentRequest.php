<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesMaintenanceBooleans;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceAttachmentRequest extends FormRequest
{
    use NormalizesMaintenanceBooleans;

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
            'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:10240'],
            'visible_to_tenant' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
