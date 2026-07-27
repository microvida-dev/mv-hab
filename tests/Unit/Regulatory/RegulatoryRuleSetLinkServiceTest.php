<?php

namespace Tests\Unit\Regulatory;

use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\Program;
use App\Models\User;
use App\Services\Regulatory\RegulatoryRuleSetLinkService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegulatoryRuleSetLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_set_inherits_profile_from_contest_without_browser_input(): void
    {
        $municipality = Municipality::factory()->create();
        $profile = AffordableRentRegulatoryProfile::factory()->create();
        $program = Program::factory()->create([
            'municipality_id' => $municipality->id,
            'regulatory_profile_id' => $profile->id,
        ]);
        $contest = Contest::factory()->for($program)->create([
            'regulatory_profile_id' => $profile->id,
        ]);
        $actor = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);

        $linked = app(RegulatoryRuleSetLinkService::class)->link([
            'contest_id' => $contest->id,
        ], $actor);

        $this->assertSame($program->id, $linked['program_id']);
        $this->assertSame($contest->id, $linked['contest_id']);
        $this->assertSame($profile->id, $linked['regulatory_profile_id']);
    }

    public function test_mismatched_program_and_contest_are_rejected(): void
    {
        $municipality = Municipality::factory()->create();
        $program = Program::factory()->create(['municipality_id' => $municipality->id]);
        $otherProgram = Program::factory()->create(['municipality_id' => $municipality->id]);
        $contest = Contest::factory()->for($program)->create();
        $actor = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        app(RegulatoryRuleSetLinkService::class)->link([
            'program_id' => $otherProgram->id,
            'contest_id' => $contest->id,
        ], $actor);
    }

    public function test_municipal_actor_cannot_link_rules_to_another_municipality(): void
    {
        $actor = User::factory()->create([
            'municipality_id' => Municipality::factory()->create()->id,
            'status' => 'active',
        ]);
        $foreignProgram = Program::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(RegulatoryRuleSetLinkService::class)->link([
            'program_id' => $foreignProgram->id,
        ], $actor);
    }

    public function test_explicit_global_operator_can_link_rules_to_any_municipality(): void
    {
        $actor = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
        ]);
        PlatformOperatorAssignment::factory()->for($actor)->create();
        $program = Program::factory()->create();

        $linked = app(RegulatoryRuleSetLinkService::class)->link([
            'program_id' => $program->id,
        ], $actor);

        $this->assertSame($program->id, $linked['program_id']);
    }

    public function test_user_without_municipality_or_global_assignment_cannot_link_rules(): void
    {
        $actor = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
        ]);
        $program = Program::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(RegulatoryRuleSetLinkService::class)->link([
            'program_id' => $program->id,
        ], $actor);
    }
}
