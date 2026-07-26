<?php

namespace App\Http\Requests;

use App\Enums\ListPublicationChannel;
use App\Models\ProvisionalList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublishProvisionalListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $list = $this->route('provisionalList');

        return $list instanceof ProvisionalList
            && ($this->user()?->can('publishBackoffice', $list) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => ['nullable', 'string', Rule::in(ListPublicationChannel::values())],
            'title' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'visibility_starts_at' => ['nullable', 'date'],
            'visibility_ends_at' => ['nullable', 'date', 'after_or_equal:visibility_starts_at'],
        ];
    }
}
