<?php

namespace App\Http\Requests\Backoffice\Access;

use App\Models\User;
use App\Policies\UserAdministrationPolicy;
use Illuminate\Foundation\Http\FormRequest;

class AccessJustificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('user');

        if (! $actor instanceof User || ! $target instanceof User) {
            return false;
        }

        $ability = match ($this->route()?->getName()) {
            'backoffice.users.deactivate' => 'deactivate',
            'backoffice.users.reactivate' => 'reactivate',
            'backoffice.users.force-mfa' => 'forceMfa',
            'backoffice.users.reset-password' => 'resetPassword',
            default => null,
        };

        return $ability !== null
            && app(UserAdministrationPolicy::class)->{$ability}($actor, $target);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
