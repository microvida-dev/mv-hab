<?php

namespace App\Http\Requests;

use App\Enums\AnonymizationMode;
use App\Models\DefinitiveList;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GenerateDefinitiveListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('generateBackoffice', DefinitiveList::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()->municipality_id ?? 0;

        return [
            'provisional_list_id' => [
                'required',
                Rule::exists('provisional_lists', 'id')
                    ->where(fn (Builder $query): Builder => $query->whereIn(
                        'contest_id',
                        DB::table('contests')
                            ->select('contests.id')
                            ->join('programs', 'programs.id', '=', 'contests.program_id')
                            ->where('programs.municipality_id', $municipalityId),
                    )),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'publication_starts_at' => ['nullable', 'date'],
            'publication_ends_at' => ['nullable', 'date', 'after_or_equal:publication_starts_at'],
            'anonymization_mode' => ['nullable', 'string', Rule::in(AnonymizationMode::values())],
            'public_visibility' => ['required', 'boolean'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
