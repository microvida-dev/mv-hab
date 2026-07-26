<?php

namespace App\Http\Requests\Backoffice\Platform;

use App\Models\PlatformOperatorAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class GrantPlatformOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', PlatformOperatorAssignment::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'justification' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
