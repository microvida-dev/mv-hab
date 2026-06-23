<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provisional_list_id' => ['required', 'exists:provisional_lists,id'],
            'provisional_list_entry_id' => ['nullable', 'exists:provisional_list_entries,id'],
            'application_id' => ['required', 'exists:applications,id'],
            'subject' => ['required', 'string', 'max:255'],
            'grounds' => ['required', 'string', 'min:10', 'max:10000'],
            'requested_outcome' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.document_submission_id' => ['nullable', 'exists:document_submissions,id'],
            'attachments.*.description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
