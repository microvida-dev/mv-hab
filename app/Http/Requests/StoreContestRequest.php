<?php

namespace App\Http\Requests;

use App\Enums\ContestDeadlineType;
use App\Http\Requests\Concerns\ValidatesContestApplicationTimeline;
use App\Models\Contest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreContestRequest extends FormRequest
{
    use ValidatesContestApplicationTimeline;

    public function authorize(): bool
    {
        return Gate::allows('createBackoffice', Contest::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['required', 'exists:programs,id'],
            'code' => ['required', 'string', 'max:100', Rule::unique('contests', 'code')],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('contests', 'slug')],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'application_instructions' => ['nullable', 'string'],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date', 'after:opens_at'],
            'deadlines' => ['nullable', 'array', 'max:20'],
            'deadlines.*.type' => ['nullable', 'distinct', Rule::enum(ContestDeadlineType::class)],
            'deadlines.*.label' => ['nullable', 'string', 'max:255'],
            'deadlines.*.starts_at' => ['nullable', 'date'],
            'deadlines.*.ends_at' => ['nullable', 'date'],
            'deadlines.*.description' => ['nullable', 'string'],
            'jury_members' => ['nullable', 'array', 'max:20'],
            'jury_members.*.user_id' => ['required_with:jury_members', 'distinct', 'exists:users,id'],
            'jury_members.*.role_in_jury' => ['required_with:jury_members', 'string', 'max:100'],
        ];
    }
}
