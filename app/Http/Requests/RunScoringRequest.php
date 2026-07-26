<?php

namespace App\Http\Requests;

use App\Models\ScoringRun;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RunScoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('runAnyBackoffice', ScoringRun::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()->municipality_id ?? 0;
        $programIds = DB::table('programs')
            ->select('id')
            ->where('municipality_id', $municipalityId);
        $contestIds = DB::table('contests')
            ->select('id')
            ->whereIn('program_id', clone $programIds);

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
                        ->whereIn('program_id', clone $programIds)),
            ],
            'scoring_rule_set_id' => [
                'nullable',
                Rule::exists('scoring_rule_sets', 'id')
                    ->where(fn (Builder $query): Builder => $query
                        ->where(function (Builder $scope) use ($programIds, $contestIds): void {
                            $scope
                                ->whereIn('program_id', clone $programIds)
                                ->orWhereIn('contest_id', clone $contestIds);
                        })),
            ],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
