<?php

namespace App\Http\Requests\Concerns;

trait ValidatesAdhesionRegistration
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'wants_email_notifications' => $this->boolean('wants_email_notifications'),
            'wants_sms_notifications' => $this->boolean('wants_sms_notifications'),
            'wants_postal_notifications' => $this->boolean('wants_postal_notifications'),
            'accepts_terms' => $this->boolean('accepts_terms'),
            'accepts_data_processing' => $this->boolean('accepts_data_processing'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function draftRules(): array
    {
        return [
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile_phone' => ['nullable', 'string', 'max:50'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_valid_until' => ['nullable', 'date'],
            'nif' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'parish' => ['nullable', 'string', 'max:100'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'wants_email_notifications' => ['boolean'],
            'wants_sms_notifications' => ['boolean'],
            'wants_postal_notifications' => ['boolean'],
            'accepts_terms' => ['boolean'],
            'accepts_data_processing' => ['boolean'],
        ];
    }
}
