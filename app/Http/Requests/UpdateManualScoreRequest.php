<?php

namespace App\Http\Requests;

use App\Models\ApplicationScore;
use App\Models\ApplicationScoreDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateManualScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manualReview', $this->route('applicationScore')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_score_detail_id' => ['required', 'exists:application_score_details,id'],
            'manual_points' => ['required', 'numeric', 'min:0'],
            'manual_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $applicationScore = $this->route('applicationScore');

            if (! $applicationScore instanceof ApplicationScore) {
                abort(404);
            }

            $detail = ApplicationScoreDetail::query()
                ->find($this->integer('application_score_detail_id'));

            if (
                ! $detail ||
                (int) $detail->application_score_id !== (int) $applicationScore->id
            ) {
                $validator->errors()->add(
                    'application_score_detail_id',
                    'O critério manual não pertence a esta pontuação.'
                );

                return;
            }

            if (! $detail->requires_manual_review) {
                $validator->errors()->add(
                    'application_score_detail_id',
                    'Este critério não está marcado para avaliação manual.'
                );
            }

            if (
                $detail->max_points !== null &&
                (float) $this->input('manual_points') > (float) $detail->max_points
            ) {
                $validator->errors()->add(
                    'manual_points',
                    'A pontuação manual não pode exceder a pontuação máxima do critério.'
                );
            }
        });
    }
}
