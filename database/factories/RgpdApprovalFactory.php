<?php

namespace Database\Factories;

use App\Models\RgpdApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RgpdApproval>
 */
class RgpdApprovalFactory extends Factory
{
    protected $model = RgpdApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_number' => 'DPO-'.$this->faker->unique()->numerify('########'),
            'flow_type' => 'rgpd_anonymization',
            'status' => RgpdApproval::STATUS_PENDING_DPO_APPROVAL,
            'requested_by' => User::factory(),
            'justification' => 'Pedido DPO criado para teste.',
            'metadata' => [],
            'requested_at' => now(),
        ];
    }
}
