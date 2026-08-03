<?php

namespace App\Http\Requests;

use App\Models\CorrectionRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CorrectionResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('correctionRequest');
        $ability = $this->routeIs(
            'backoffice.correction-revalidations.seal',
        ) ? 'sealRevalidationBackoffice' : 'previewRevalidationBackoffice';

        return $request instanceof CorrectionRequest
            && $this->user()?->can($ability, $request) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            'preview_token' => [
                'nullable',
                'string',
                'size:64',
                'regex:/\A[a-f0-9]{64}\z/',
            ],
            'confirm_seal' => ['nullable', 'accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->routeIs(
                'backoffice.correction-revalidations.seal',
            )) {
                return;
            }

            if (trim((string) $this->input('preview_token')) === '') {
                $validator->errors()->add(
                    'preview_token',
                    'A segunda análise deve ser previamente confirmada.',
                );
            }

            if (! $this->boolean('confirm_seal')) {
                $validator->errors()->add(
                    'confirm_seal',
                    'Confirme o selamento imutável da segunda análise.',
                );
            }
        });
    }

    /** @return array{reason:string, preview_token:string|null} */
    public function payload(): array
    {
        $validated = $this->validated();
        $token = trim((string) ($validated['preview_token'] ?? ''));

        return [
            'reason' => trim((string) $validated['reason']),
            'preview_token' => $token === '' ? null : $token,
        ];
    }
}
