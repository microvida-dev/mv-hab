<?php

namespace App\Http\Requests;

use App\Models\EligibilityRuleSet;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreEligibilityRuleSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createBackoffice', EligibilityRuleSet::class) ?? false;
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
                'integer',
                Rule::exists('programs', 'id')
                    ->where(fn (Builder $query): Builder => $query
                        ->where('municipality_id', $municipalityId)),
                'required_without:contest_id',
            ],
            'contest_id' => [
                'nullable',
                'integer',
                Rule::exists('contests', 'id')
                    ->where(fn (Builder $query): Builder => $query
                        ->whereIn(
                            'program_id',
                            DB::table('programs')
                                ->select('id')
                                ->where('municipality_id', $municipalityId),
                        )),
                'required_without:program_id',
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_default' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
