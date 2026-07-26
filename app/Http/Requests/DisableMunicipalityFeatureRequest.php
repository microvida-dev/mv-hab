<?php

namespace App\Http\Requests;

use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DisableMunicipalityFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $municipality = $this->route('municipality');

        return $municipality instanceof Municipality
            && Gate::allows('update', [MunicipalityFeatureEntitlement::class, $municipality]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'justification' => ['required', 'string', 'min:10', 'max:1000', 'not_regex:/<[^>]*>/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'justification' => trim((string) $this->input('justification')),
        ]);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['justification' => 'justificação'];
    }
}
