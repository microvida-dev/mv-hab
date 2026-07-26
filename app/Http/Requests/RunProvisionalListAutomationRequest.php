<?php

namespace App\Http\Requests;

use App\Models\Contest;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class RunProvisionalListAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contest = $this->route('contest');

        return $contest instanceof Contest
            && ($this->user()?->can('generateBackoffice', $contest) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contest_id' => ['nullable', $this->municipalContestExistsRule()],
            'confirm_snapshot_generation' => ['accepted'],
        ];
    }

    protected function municipalContestExistsRule(): Exists
    {
        $municipalityId = $this->user()->municipality_id ?? 0;

        return Rule::exists('contests', 'id')
            ->where(fn (Builder $query): Builder => $query->whereIn(
                'program_id',
                DB::table('programs')
                    ->select('id')
                    ->where('municipality_id', $municipalityId),
            ));
    }
}
