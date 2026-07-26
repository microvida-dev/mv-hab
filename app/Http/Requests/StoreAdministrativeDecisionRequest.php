<?php

namespace App\Http\Requests;

use App\Models\AdministrativeDecision;
use App\Models\AdministrativeProcess;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdministrativeDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $process = $this->route('administrativeProcess');

        return $process instanceof AdministrativeProcess
            && ($this->user()?->can(
                'createBackoffice',
                [AdministrativeDecision::class, $process],
            ) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:3000'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'grounds' => ['required', 'string', 'max:5000'],
            'candidate_visible' => ['nullable', 'boolean'],
        ];
    }
}
