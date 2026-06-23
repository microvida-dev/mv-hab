<?php

namespace App\Http\Requests;

use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Program::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'municipality_id' => ['required', 'exists:municipalities,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('programs', 'slug')],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'legal_basis' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'rules' => ['nullable', 'array', 'max:20'],
            'rules.*.title' => ['required_with:rules', 'string', 'max:255'],
            'rules.*.description' => ['required_with:rules', 'string'],
            'rules.*.effective_from' => ['nullable', 'date'],
            'rules.*.effective_until' => ['nullable', 'date', 'after_or_equal:rules.*.effective_from'],
        ];
    }
}
