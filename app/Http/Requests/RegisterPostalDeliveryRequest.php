<?php

namespace App\Http\Requests;

use App\Models\CommunicationDelivery;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPostalDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $delivery = $this->route('communicationDelivery');

        return $delivery instanceof CommunicationDelivery
            && $this->user()?->can(
                'registerPostalBackoffice',
                $delivery,
            ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sent_at' => ['required', 'date'],
            'postal_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
