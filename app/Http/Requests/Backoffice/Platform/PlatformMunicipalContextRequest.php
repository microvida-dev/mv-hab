<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Platform;

use App\Enums\ActorProfile;
use App\Models\User;
use App\Services\Platform\ActorProfileResolver;
use Illuminate\Foundation\Http\FormRequest;

abstract class PlatformMunicipalContextRequest extends FormRequest
{
    final public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && app(ActorProfileResolver::class)->primary($user)
                === ActorProfile::PlatformAdministrator
            && $user->hasPermission('municipalities.view');
    }

    /** @return list<string> */
    protected function justificationRules(): array
    {
        return [
            'required',
            'string',
            'min:10',
            'max:500',
            'not_regex:/<[^>]*>/',
        ];
    }

    protected function normalizeJustification(): void
    {
        $this->merge([
            'justification' => trim(
                (string) $this->input('justification'),
            ),
        ]);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'municipality_id' => 'Município',
            'justification' => 'justificação',
            'confirm' => 'confirmação',
        ];
    }
}
