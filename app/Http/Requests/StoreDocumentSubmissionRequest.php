<?php

namespace App\Http\Requests;

use App\Models\DocumentSubmission;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DocumentSubmission::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'required_document_id' => ['required', 'integer', 'exists:required_documents,id'],
            'household_member_id' => ['nullable', 'integer', 'exists:household_members,id'],
            'income_record_id' => ['nullable', 'integer', 'exists:income_records,id'],
            'current_housing_situation_id' => ['nullable', 'integer', 'exists:current_housing_situations,id'],
            'application_public_id' => ['nullable', 'uuid', 'exists:applications,public_id'],
            'title' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'file' => ['required', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/heic,image/heif', 'mimes:pdf,jpg,jpeg,png,webp,heic,heif'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
