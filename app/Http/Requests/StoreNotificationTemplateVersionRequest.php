<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationTemplateVersionRequest extends FormRequest
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
            'subject' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'html_body' => ['nullable', 'string', 'max:50000'],
            'sms_body' => ['nullable', 'string', 'max:1000'],
            'variables_schema' => ['nullable', 'array'],
            'change_summary' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
