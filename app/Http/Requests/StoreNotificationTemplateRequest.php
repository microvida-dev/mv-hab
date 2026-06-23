<?php

namespace App\Http\Requests;

use App\Enums\CommunicationChannel;
use App\Enums\TemplateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationTemplateRequest extends FormRequest
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
        $template = $this->route('notificationTemplate');
        $codeRule = Rule::unique('notification_templates', 'code')
            ->where(fn ($query) => $query
                ->where('channel', $this->input('channel'))
                ->where('program_id', $this->input('program_id'))
                ->where('contest_id', $this->input('contest_id'))
                ->whereNull('deleted_at'))
            ->ignore($template);

        return [
            'code' => ['required', 'string', 'max:150', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'template_type' => ['required', Rule::in(TemplateType::values())],
            'channel' => ['required', Rule::in(CommunicationChannel::values())],
            'language' => ['required', 'string', 'max:10'],
            'subject' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'html_body' => ['nullable', 'string', 'max:50000'],
            'sms_body' => ['nullable', 'string', 'max:1000'],
            'requires_acknowledgement' => ['sometimes', 'boolean'],
            'is_official' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'contest_id' => ['nullable', 'exists:contests,id'],
        ];
    }
}
