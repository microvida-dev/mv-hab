<?php

namespace App\Http\Requests;

use App\Models\CorrectionRequest;
use Illuminate\Foundation\Http\FormRequest;

final class StartCorrectionRevalidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('correctionRequest');

        return $request instanceof CorrectionRequest
            && $this->user()?->can(
                'startRevalidationBackoffice',
                $request,
            ) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirm_start' => ['required', 'accepted'],
        ];
    }
}
