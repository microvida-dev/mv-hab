<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof Document
            && $this->user()?->can('updateBackoffice', $document) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'citizen_id' => [
                'nullable',
                Rule::exists('citizens', 'id')
                    ->where('municipality_id', $this->user()->municipality_id ?? -1),
            ],
            'housing_application_id' => [
                'nullable',
                Rule::exists('housing_applications', 'id')
                    ->where('municipality_id', $this->user()->municipality_id ?? -1),
            ],
            'contract_id' => ['nullable', $this->municipalContractRule()],
            'name' => ['required', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }

    private function municipalContractRule(): Exists
    {
        $municipalityId = $this->user()->municipality_id ?? -1;

        return Rule::exists('contracts', 'id')->where(
            fn ($query) => $query
                ->whereIn(
                    'program_id',
                    Program::query()
                        ->select('id')
                        ->where('municipality_id', $municipalityId),
                )
                ->orWhereIn(
                    'user_id',
                    User::query()
                        ->select('id')
                        ->where('municipality_id', $municipalityId),
                ),
        );
    }
}
