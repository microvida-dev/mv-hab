<?php

namespace App\Http\Requests;

use App\Models\EligibilityRuleSet;
use App\Models\User;
use App\Services\Platform\PlatformOperatorScopeService;
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
        $user = $this->user();
        $municipalityId = $user instanceof User ? $user->municipality_id : null;
        $hasGlobalScope = $user instanceof User
            && app(PlatformOperatorScopeService::class)->hasGlobalScope($user);

        $programExists = Rule::exists('programs', 'id');
        $contestExists = Rule::exists('contests', 'id');

        if (! $hasGlobalScope) {
            $programExists->where(
                fn (Builder $query): Builder => $municipalityId === null
                    ? $query->whereRaw('1 = 0')
                    : $query->where('municipality_id', $municipalityId),
            );
            $contestExists->where(
                fn (Builder $query): Builder => $municipalityId === null
                    ? $query->whereRaw('1 = 0')
                    : $query->whereIn(
                        'program_id',
                        DB::table('programs')
                            ->select('id')
                            ->where('municipality_id', $municipalityId),
                    ),
            );
        }

        return [
            'program_id' => [
                'nullable',
                'integer',
                $programExists,
                'required_without:contest_id',
            ],
            'contest_id' => [
                'nullable',
                'integer',
                $contestExists,
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
