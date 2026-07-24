<?php

namespace App\Http\Requests;

use App\Enums\LotteryDrawType;
use App\Models\LotteryDraw;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreLotteryDrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createBackoffice', LotteryDraw::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()->municipality_id ?? 0;

        return [
            'allocation_run_id' => [
                'required',
                Rule::exists('allocation_runs', 'id')
                    ->where(fn (Builder $query): Builder => $query->whereIn(
                        'contest_id',
                        DB::table('contests')
                            ->select('contests.id')
                            ->join('programs', 'programs.id', '=', 'contests.program_id')
                            ->where('programs.municipality_id', $municipalityId),
                    )),
            ],
            'draw_type' => ['nullable', Rule::in(LotteryDrawType::values())],
            'seed' => ['nullable', 'string', 'max:255'],
            'seed_source' => ['nullable', 'string', 'max:255'],
            'algorithm' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'public_notice_text' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
