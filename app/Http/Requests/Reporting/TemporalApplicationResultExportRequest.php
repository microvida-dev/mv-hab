<?php

namespace App\Http\Requests\Reporting;

use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationReviewBatchCycle;
use App\Models\ReportExport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class TemporalApplicationResultExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('createTemporal', ReportExport::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'contest_id' => ['required', 'integer', 'min:1'],
            'mode' => ['required', Rule::enum(ApplicationResultExportMode::class)],
            'batch_public_id' => ['nullable', 'uuid'],
            'base_batch_public_id' => ['nullable', 'uuid'],
            'target_batch_public_id' => ['nullable', 'uuid'],
            'phase' => ['nullable', Rule::enum(ApplicationReviewBatchCycle::class)],
            'as_of' => ['nullable', 'date'],
            'since' => ['nullable', 'date'],
            'formats' => ['required', 'array', 'min:1', 'max:4'],
            'formats.*' => ['required', 'distinct', Rule::enum(ApplicationResultExportFormat::class)],
            'datasets' => ['required', 'array', 'min:1', 'max:4'],
            'datasets.*' => ['required', 'distinct', Rule::enum(ApplicationResultExportDataset::class)],
            'csv_delimiter' => ['required', Rule::in(['semicolon', 'comma', 'tab'])],
            'csv_bom' => ['nullable', 'boolean'],
            'include_sensitive' => ['nullable', 'boolean'],
            'sensitive_confirmed' => [
                Rule::excludeIf(fn (): bool => ! $this->boolean('include_sensitive')),
                'required',
                'accepted',
            ],
            'include_document_files' => ['nullable', 'boolean'],
            'document_files_confirmed' => [
                Rule::excludeIf(fn (): bool => ! $this->boolean('include_document_files')),
                'required',
                'accepted',
            ],
            'changed_documents_only' => ['nullable', 'boolean'],
            'include_unchanged' => ['nullable', 'boolean'],
            'idempotency_token' => ['nullable', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'csv_bom' => $this->boolean('csv_bom'),
            'include_sensitive' => $this->boolean('include_sensitive'),
            'sensitive_confirmed' => $this->boolean('sensitive_confirmed'),
            'include_document_files' => $this->boolean('include_document_files'),
            'document_files_confirmed' => $this->boolean('document_files_confirmed'),
            'changed_documents_only' => $this->boolean('changed_documents_only'),
            'include_unchanged' => $this->boolean('include_unchanged'),
        ]);
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $mode = ApplicationResultExportMode::tryFrom(
                $this->string('mode')->toString(),
            );
            if (! $mode instanceof ApplicationResultExportMode) {
                return;
            }

            $required = match ($mode) {
                ApplicationResultExportMode::CurrentState => [],
                ApplicationResultExportMode::SealedBatch => ['batch_public_id'],
                ApplicationResultExportMode::PhaseSnapshot => ['phase', 'as_of'],
                ApplicationResultExportMode::DeltaBetweenBatches => [
                    'base_batch_public_id',
                    'target_batch_public_id',
                ],
                ApplicationResultExportMode::DeltaSinceDatetime => ['since', 'as_of'],
                ApplicationResultExportMode::FinalResult => ['as_of'],
            };
            foreach ($required as $field) {
                if (trim($this->string($field)->toString()) === '') {
                    $validator->errors()->add(
                        $field,
                        'Este campo é obrigatório para o modo temporal selecionado.',
                    );
                }
            }

            $datasets = $this->input('datasets', []);
            $datasets = is_array($datasets) ? $datasets : [];
            if (
                in_array(ApplicationResultExportDataset::Changes->value, $datasets, true)
                && ! $mode->isDelta()
            ) {
                $validator->errors()->add(
                    'datasets',
                    'O dataset de alterações só está disponível nos modos delta.',
                );
            }

            if ($this->boolean('changed_documents_only') && ! $mode->isDelta()) {
                $validator->errors()->add(
                    'changed_documents_only',
                    'A opção exige um modo delta.',
                );
            }

            if ($this->boolean('include_document_files')) {
                if (! $this->boolean('include_sensitive')) {
                    $validator->errors()->add(
                        'include_document_files',
                        'O dossier documental exige uma exportação sensível confirmada.',
                    );
                }
                if (! in_array(ApplicationResultExportDataset::Documents->value, $datasets, true)) {
                    $validator->errors()->add(
                        'datasets',
                        'Selecione o dataset Documentos para pedir o dossier documental.',
                    );
                }
            }

            if (
                $mode === ApplicationResultExportMode::DeltaSinceDatetime
                && strtotime($this->string('since')->toString()) !== false
                && strtotime($this->string('as_of')->toString()) !== false
                && strtotime($this->string('since')->toString())
                    >= strtotime($this->string('as_of')->toString())
            ) {
                $validator->errors()->add(
                    'since',
                    'A referência inicial deve ser anterior ao instante final.',
                );
            }
        }];
    }
}
