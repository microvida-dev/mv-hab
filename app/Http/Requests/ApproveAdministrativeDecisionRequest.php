<?php

namespace App\Http\Requests;

use App\Models\AdministrativeDecision;
use Illuminate\Foundation\Http\FormRequest;

class ApproveAdministrativeDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $decision = $this->route('administrativeDecision');

        return $decision instanceof AdministrativeDecision
            && ($this->user()?->can('approveBackoffice', $decision) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm_decision' => ['accepted'],
        ];
    }
}
