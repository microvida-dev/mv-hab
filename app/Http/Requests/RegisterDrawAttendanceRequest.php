<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use App\Models\LotteryDraw;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class RegisterDrawAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $draw = $this->route('lotteryDraw');

        return $draw instanceof LotteryDraw
            && ($this->user()?->can('registerAttendanceBackoffice', $draw) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['required', $this->participantApplicationExistsRule()],
            'user_id' => ['required', $this->participantUserExistsRule()],
            'lottery_participant_id' => ['nullable', $this->participantExistsRule()],
            'draw_convocation_id' => ['nullable', $this->convocationExistsRule()],
            'status' => ['required', Rule::in(AttendanceStatus::values())],
            'justification' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    protected function participantApplicationExistsRule(): Exists
    {
        return Rule::exists('applications', 'id')
            ->where(fn (Builder $query): Builder => $query->whereIn(
                'id',
                DB::table('lottery_participants')
                    ->select('application_id')
                    ->where('lottery_run_id', $this->lotteryDrawId()),
            ));
    }

    protected function participantUserExistsRule(): Exists
    {
        return Rule::exists('users', 'id')
            ->where(fn (Builder $query): Builder => $query->whereIn(
                'id',
                DB::table('lottery_participants')
                    ->select('user_id')
                    ->where('lottery_run_id', $this->lotteryDrawId()),
            ));
    }

    protected function participantExistsRule(): Exists
    {
        return Rule::exists('lottery_participants', 'id')
            ->where('lottery_run_id', $this->lotteryDrawId());
    }

    protected function convocationExistsRule(): Exists
    {
        return Rule::exists('draw_convocations', 'id')
            ->where('lottery_run_id', $this->lotteryDrawId());
    }

    protected function lotteryDrawId(): int
    {
        $draw = $this->route('lotteryDraw');

        return $draw instanceof LotteryDraw ? (int) $draw->getKey() : 0;
    }
}
