<?php

namespace Database\Factories;

use App\Enums\PlatformOperatorGrantSource;
use App\Enums\PlatformOperatorStatus;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlatformOperatorAssignment> */
class PlatformOperatorAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'municipality_id' => null,
                'status' => 'active',
            ]),
            'status' => PlatformOperatorStatus::Active,
            'grant_source' => PlatformOperatorGrantSource::Bootstrap,
            'granted_by' => null,
            'granted_at' => now(),
            'grant_justification' => 'Atribuição explícita para testes automatizados.',
            'approval_reference_primary' => 'SEC-TEST-001',
            'approval_reference_secondary' => 'MANAGEMENT-TEST-001',
            'revoked_by' => null,
            'revoked_at' => null,
            'revoke_justification' => null,
        ];
    }

    public function revoked(?User $actor = null): static
    {
        return $this->state(fn (): array => [
            'status' => PlatformOperatorStatus::Revoked,
            'revoked_by' => $actor,
            'revoked_at' => now(),
            'revoke_justification' => 'Revogação explícita para testes automatizados.',
        ]);
    }
}
