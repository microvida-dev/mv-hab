<?php

namespace App\Http\Requests;

use App\Enums\BulkApplicationReviewAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkApplicationReviewRequest extends FormRequest
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
            'document_ids' => $this->normalizeIds(
                $this->input('document_ids', []),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => [
                'required',
                Rule::enum(BulkApplicationReviewAction::class),
            ],
            'process_ids' => ['required', 'array', 'min:1', 'max:200'],
            'process_ids.*' => [
                'required',
                'integer',
                'distinct',
            ],
            'document_ids' => ['nullable', 'array', 'max:500'],
            'document_ids.*' => [
                'integer',
                'distinct',
            ],
            'assigned_to' => ['nullable', 'integer'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
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
            $action = BulkApplicationReviewAction::tryFrom(
                (string) $this->input('action'),
            );

            if (! $action instanceof BulkApplicationReviewAction) {
                return;
            }

            if ($action->requiresAssignee()
                && $this->input('assigned_to') === null) {
                $validator->errors()->add(
                    'assigned_to',
                    'Selecione o analista a atribuir.',
                );
            }

            if ($action->requiresDocuments()
                && $this->input('document_ids', []) === []) {
                $validator->errors()->add(
                    'document_ids',
                    'Selecione pelo menos um documento.',
                );
            }

            if ($action->requiresReason()
                && trim((string) $this->input('reason')) === '') {
                $validator->errors()->add(
                    'reason',
                    'Indique o fundamento da operação.',
                );
            }

            if ($this->routeIs(
                'backoffice.application-review-workspace.apply',
            ) && trim((string) $this->input('preview_token')) === '') {
                $validator->errors()->add(
                    'preview_token',
                    'A operação deve ser previamente confirmada.',
                );
            }
        });
    }

    /**
     * @return array{
     *     action: BulkApplicationReviewAction,
     *     process_ids: list<int>,
     *     document_ids: list<int>,
     *     assigned_to: int|null,
     *     reason: string|null,
     *     internal_notes: string|null,
     *     preview_token: string|null
     * }
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $action = BulkApplicationReviewAction::from(
            (string) $validated['action'],
        );

        return [
            'action' => $action,
            'process_ids' => array_values(array_map(
                'intval',
                $validated['process_ids'],
            )),
            'document_ids' => array_values(array_map(
                'intval',
                $validated['document_ids'] ?? [],
            )),
            'assigned_to' => isset($validated['assigned_to'])
                ? (int) $validated['assigned_to']
                : null,
            'reason' => $this->nullableString(
                $validated['reason'] ?? null,
            ),
            'internal_notes' => $this->nullableString(
                $validated['internal_notes'] ?? null,
            ),
            'preview_token' => $this->nullableString(
                $validated['preview_token'] ?? null,
            ),
        ];
    }

    /**
     * Preserve invalid values so the validator rejects tampered requests
     * instead of silently discarding them.
     *
     * @return list<mixed>
     */
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

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
