<?php

namespace App\Http\Requests;

use App\Models\InternalAlert;
use Illuminate\Foundation\Http\FormRequest;

class ResolveInternalAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        $alert = $this->route('internalAlert');

        if (! $alert instanceof InternalAlert) {
            return false;
        }

        $ability = $this->routeIs('backoffice.internal-alerts.dismiss')
            ? 'dismissBackoffice'
            : 'resolveBackoffice';

        return $this->user()?->can($ability, $alert) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
