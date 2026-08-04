<?php

namespace App\Services\Municipalities;

use App\Data\Municipalities\MunicipalityOnboardingData;
use DomainException;

final class MunicipalityIdentityNormalizer
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function onboardingData(array $input): MunicipalityOnboardingData
    {
        $actorId = filter_var($input['actor_id'] ?? null, FILTER_VALIDATE_INT);

        if (! is_int($actorId) || $actorId <= 0) {
            throw new DomainException('O ID do ator é obrigatório e deve ser válido.');
        }

        $name = $this->name((string) ($input['name'] ?? ''), 'nome do Município');
        $code = $this->code((string) ($input['code'] ?? ''));
        $taxNumber = $this->taxNumber((string) ($input['tax_number'] ?? ''));
        $contactEmail = $this->email((string) ($input['contact_email'] ?? ''), 'email institucional');
        $adminName = $this->name((string) ($input['admin_name'] ?? ''), 'nome do administrador');
        $adminEmail = $this->email((string) ($input['admin_email'] ?? ''), 'email do administrador');
        $justification = $this->justification((string) ($input['justification'] ?? ''));

        return new MunicipalityOnboardingData(
            actorId: $actorId,
            name: $name,
            code: $code,
            taxNumber: $taxNumber,
            contactEmail: $contactEmail,
            adminName: $adminName,
            adminEmail: $adminEmail,
            justification: $justification,
        );
    }

    public function code(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/\s+/', '-', $value) ?? $value;

        if ($value === '' || mb_strlen($value) > 80 || preg_match('/^[A-Z0-9][A-Z0-9_-]*$/', $value) !== 1) {
            throw new DomainException(
                'O código municipal deve conter apenas letras, números, hífen ou underscore.',
            );
        }

        return $value;
    }

    public function taxNumber(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($digits) !== 9 || ! $this->validPortugueseTaxNumber($digits)) {
            throw new DomainException('O NIF/NIPC municipal indicado não é válido.');
        }

        return $digits;
    }

    public function email(string $value, string $label = 'email'): string
    {
        $value = mb_strtolower(trim($value));

        if ($value === '' || mb_strlen($value) > 255 || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException("O {$label} indicado não é válido.");
        }

        return $value;
    }

    public function name(string $value, string $label): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        if ($value === '' || mb_strlen($value) > 255) {
            throw new DomainException("O {$label} é obrigatório e não pode exceder 255 caracteres.");
        }

        if ($value !== strip_tags($value)) {
            throw new DomainException("O {$label} não pode conter HTML.");
        }

        return $value;
    }

    public function justification(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        if (mb_strlen($value) < 10 || mb_strlen($value) > 1000) {
            throw new DomainException('A justificação deve ter entre 10 e 1000 caracteres.');
        }

        if ($value !== strip_tags($value)) {
            throw new DomainException('A justificação não pode conter HTML.');
        }

        return $value;
    }

    private function validPortugueseTaxNumber(string $digits): bool
    {
        $sum = 0;

        for ($index = 0, $weight = 9; $index < 8; $index++, $weight--) {
            $sum += ((int) $digits[$index]) * $weight;
        }

        $remainder = $sum % 11;
        $checkDigit = $remainder < 2 ? 0 : 11 - $remainder;

        return $checkDigit === (int) $digits[8];
    }
}
