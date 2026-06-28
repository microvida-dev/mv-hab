<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ! $user->hasRole('candidate')
            && $user->hasPermission('reports.view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'contest_id' => ['nullable', 'integer', 'exists:contests,id'],
            'status' => ['nullable', 'string', 'max:80'],
            'priority' => ['nullable', 'string', 'max:40'],
            'sla' => ['nullable', 'string', 'max:40'],
            'municipal_team_id' => ['nullable', 'integer', 'exists:municipal_teams,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'typology' => ['nullable', 'string', 'max:20'],
            'parish' => ['nullable', 'string', 'max:120'],
        ];
    }
}
