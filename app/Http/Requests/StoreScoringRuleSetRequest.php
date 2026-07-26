<?php

namespace App\Http\Requests;

use App\Models\ScoringRuleSet;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreScoringRuleSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createBackoffice', ScoringRuleSet::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()->municipality_id ?? 0;

        return [
            'program_id' => [
                'nullable',
                'required_without:contest_id',
                Rule::exists('programs', 'id')
                    ->where(fn (Builder $query): Builder => $query
                        ->where('municipality_id', $municipalityId)),
            ],
            'contest_id' => [
                'nullable',
                'required_without:program_id',
                Rule::exists('contests', 'id')
                    ->where(fn (Builder $query): Builder => $query
                        ->whereIn(
                            'program_id',
                            DB::table('programs')
                                ->select('id')
                                ->where('municipality_id', $municipalityId),
                        )),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'is_default' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
