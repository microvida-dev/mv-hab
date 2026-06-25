<?php

namespace Tests\Feature\Regulatory;

use App\Enums\ContestDeadlineType;
use App\Models\Contest;
use Database\Seeders\DemoAlcanenaAffordableRentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlcanenaListsHearingComplaintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_complaints_and_hearing_deadlines_exist_for_list_cycle(): void
    {
        $this->seed(DemoAlcanenaAffordableRentSeeder::class);

        $contest = Contest::query()->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)->firstOrFail();

        $complaints = $contest->deadlines()->where('type', ContestDeadlineType::Complaints->value)->firstOrFail();
        $hearing = $contest->deadlines()->where('type', ContestDeadlineType::Hearing->value)->firstOrFail();

        $this->assertSame('Reclamações à lista provisória', $complaints->label);
        $this->assertSame('Audiência de interessados', $hearing->label);
        $this->assertTrue($complaints->starts_at->lessThan($complaints->ends_at));
        $this->assertTrue($hearing->starts_at->lessThan($hearing->ends_at));
        $this->assertTrue($complaints->ends_at->lessThan($hearing->starts_at));
    }
}
