<?php

declare(strict_types=1);

namespace App\Http\Requests\PublicPortal;

use App\Services\Visits\PublicVisitChallengeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePublicVisitBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('public_visits.enabled', true);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'visit_slot_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => [
                'nullable',
                'string',
                'max:40',
                'regex:/^[0-9+().\s-]+$/',
            ],
            'guest_count' => [
                'required',
                'integer',
                'min:1',
                'max:'.max(
                    1,
                    (int) config(
                        'public_visits.max_guests_per_booking',
                        6,
                    ),
                ),
            ],
            'privacy_accepted' => ['accepted'],
            'challenge_token' => ['nullable', 'string', 'max:2048'],
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $challenge = app(PublicVisitChallengeService::class);

                if (! $challenge->verify(
                    $this->string('challenge_token')->toString(),
                    $this->ip(),
                )) {
                    $validator->errors()->add(
                        'challenge_token',
                        'Não foi possível validar a proteção anti-robô. Atualize a página e tente novamente.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(
                trim((string) $this->input('email')),
            ),
            'phone' => $this->filled('phone')
                ? trim((string) $this->input('phone'))
                : null,
            'challenge_token' => $this->input(
                'challenge_token',
                $this->input('cf-turnstile-response'),
            ),
        ]);
    }
}
