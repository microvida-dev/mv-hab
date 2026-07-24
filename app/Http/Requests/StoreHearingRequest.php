<?php

namespace App\Http\Requests;

use App\Enums\HearingType;
use App\Models\Hearing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHearingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createBackoffice', Hearing::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()?->municipality_id;
        $contestIds = fn ($query) => $query
            ->select('contests.id')
            ->from('contests')
            ->join('programs', 'programs.id', '=', 'contests.program_id')
            ->where('programs.municipality_id', $municipalityId);

        return [
            'application_id' => [
                'required',
                Rule::exists('applications', 'id')->where(
                    fn ($query) => $query->whereIn(
                        'program_id',
                        fn ($programs) => $programs
                            ->select('id')
                            ->from('programs')
                            ->where('municipality_id', $municipalityId),
                    ),
                ),
            ],
            'provisional_list_id' => [
                'nullable',
                Rule::exists('provisional_lists', 'id')->where(
                    fn ($query) => $query->whereIn('contest_id', $contestIds),
                ),
            ],
            'definitive_list_id' => [
                'nullable',
                Rule::exists('definitive_lists', 'id')->where(
                    fn ($query) => $query->whereIn('contest_id', $contestIds),
                ),
            ],
            'hearing_type' => ['required', 'string', Rule::in(HearingType::values())],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'grounds' => ['required', 'string', 'max:10000'],
            'deadline_at' => ['required', 'date', 'after:now'],
            'candidate_visible' => ['boolean'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
