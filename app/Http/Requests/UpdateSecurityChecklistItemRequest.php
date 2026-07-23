<?php

namespace App\Http\Requests;

use App\Enums\SecurityChecklistStatus;
use App\Models\SecurityChecklistItem;
use App\Services\Security\SecurityMunicipalScopeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSecurityChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $item = $this->route('securityChecklistItem');

        return $user !== null
            && $user->hasPermission('security.update')
            && $item instanceof SecurityChecklistItem
            && app(SecurityMunicipalScopeService::class)->ownsChecklistItem($user, $item);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(SecurityChecklistStatus::values())],
            'evidence' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
