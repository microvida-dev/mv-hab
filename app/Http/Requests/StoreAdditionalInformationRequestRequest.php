<?php

namespace App\Http\Requests;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdditionalInformationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $complaint = $this->route('complaint');

        return $complaint instanceof Complaint
            && ($this->user()?->can('requestInformationBackoffice', $complaint) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'deadline_at' => ['required', 'date', 'after:now'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
