<?php

namespace Database\Factories;

use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Municipality>
 */
class MunicipalityFactory extends Factory
{
    public function definition(): array
    {
        $identifier = str_replace(
            '-',
            '',
            (string) Str::uuid(),
        );

        $shortIdentifier = strtoupper(
            substr($identifier, 0, 12),
        );

        return [
            'name' => 'Município de Teste '.$shortIdentifier,
            'code' => 'MUN-'.$shortIdentifier,
            'tax_number' => null,
            'contact_email' => 'municipio.'
                .$identifier
                .'@example.test',
            'settings' => [],
            'active' => true,
        ];
    }
}
