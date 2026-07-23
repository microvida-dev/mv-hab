<?php

namespace App\Http\Requests;

use App\Models\SecurityAlert;
use Illuminate\Foundation\Http\FormRequest;

class ResolveSecurityAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        $alert = $this->route('securityAlert');

        return $alert instanceof SecurityAlert
            && ($this->user()?->can('resolve', $alert) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['resolution_notes' => ['required', 'string', 'min:3', 'max:5000']];
    }
}
