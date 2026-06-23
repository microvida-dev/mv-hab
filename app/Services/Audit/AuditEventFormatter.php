<?php

namespace App\Services\Audit;

class AuditEventFormatter
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'remember_token',
        'secret',
        'secret_encrypted',
        'code',
        'recovery_code',
        'nif',
        'tax_number',
        'citizen_card_number',
        'social_security_number',
        'iban',
        'document_number',
        'storage_path',
        'file_path',
    ];

    /**
     * @param  array<string|int, mixed>  $values
     * @return array<string|int, mixed>
     */
    public function mask(array $values): array
    {
        return collect($values)->map(function ($value, string|int $key) {
            if (is_array($value)) {
                return $this->mask($value);
            }

            return $this->isSensitive((string) $key) ? '[masked]' : $value;
        })->all();
    }

    private function isSensitive(string $key): bool
    {
        $key = str($key)->lower()->toString();

        return collect(self::SENSITIVE_KEYS)->contains(fn (string $sensitive) => str_contains($key, $sensitive));
    }
}
