<?php

namespace App\Http\Requests\Backoffice\Platform;

use App\Models\PlatformOperatorAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class RevokePlatformOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('platformOperatorAssignment');

        return $assignment instanceof PlatformOperatorAssignment
            && Gate::allows('revoke', $assignment);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'justification' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
