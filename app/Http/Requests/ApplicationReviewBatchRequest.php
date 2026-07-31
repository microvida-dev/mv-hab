<?php

namespace App\Http\Requests;

use App\Enums\ApplicationReviewBatchCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApplicationReviewBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'process_ids' => $this->normalizeIds(
                $this->input('process_ids', []),
            ),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cycle' => [
                'required',
                Rule::enum(ApplicationReviewBatchCycle::class),
            ],
            'process_ids' => ['required', 'array', 'min:1', 'max:500'],
            'process_ids.*' => ['required', 'integer', 'distinct'],
            'reason' => ['required', 'string', 'max:2000'],
            'preview_token' => [
                'nullable',
                'string',
                'size:64',
                'regex:/\A[a-f0-9]{64}\z/',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->routeIs(
                'backoffice.application-review-batches.seal',
            ) && trim((string) $this->input('preview_token')) === '') {
                $validator->errors()->add(
                    'preview_token',
                    'O lote deve ser previamente confirmado.',
                );
            }
        });
    }

    /**
     * @return array{
     *     cycle: ApplicationReviewBatchCycle,
     *     process_ids: list<int>,
     *     reason: string,
     *     preview_token: string|null
     * }
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $processIds = array_values(array_map(
            'intval',
            $validated['process_ids'],
        ));
        sort($processIds, SORT_NUMERIC);
        $previewToken = trim((string) ($validated['preview_token'] ?? ''));

        return [
            'cycle' => ApplicationReviewBatchCycle::from(
                (string) $validated['cycle'],
            ),
            'process_ids' => $processIds,
            'reason' => trim((string) $validated['reason']),
            'preview_token' => $previewToken === '' ? null : $previewToken,
        ];
    }

    /** @return list<mixed> */
    private function normalizeIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            static function (mixed $item): mixed {
                if (is_string($item)
                    && preg_match('/\A[0-9]+\z/', $item) === 1) {
                    return (int) $item;
                }

                return $item;
            },
            $value,
        ));
    }
}
