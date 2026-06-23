<?php

namespace App\Http\Requests;

use App\Models\ApplicationSimulationInconsistency;
use Illuminate\Foundation\Http\FormRequest;

class ResolveApplicationSimulationInconsistencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $inconsistency = $this->route('inconsistency');

        return $inconsistency instanceof ApplicationSimulationInconsistency
            && ($this->user()?->can('resolve', $inconsistency) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
