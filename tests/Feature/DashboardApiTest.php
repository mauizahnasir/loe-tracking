<?php

namespace Tests\Feature;

use App\Models\WorkEntry;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_endpoint_returns_seeded_data(): void
    {
        $this->seed(DemoSeeder::class);

        $response = $this->getJson('/api/dashboard');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'today',
                'people',
                'activeUser',
                'day',
                'tabs' => ['projects', 'meetings', 'timeOff'],
                'integrations',
            ]);
    }

    public function test_work_entry_can_be_confirmed(): void
    {
        $this->seed(DemoSeeder::class);
        $entry = WorkEntry::query()->where('status', 'draft')->firstOrFail();

        $response = $this->postJson("/api/entries/{$entry->id}/confirm", [
            'hours' => 4,
            'note' => 'Confirmed during test.',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('work_entries', [
            'id' => $entry->id,
            'status' => 'confirmed',
            'note' => 'Confirmed during test.',
        ]);
    }

    public function test_project_can_be_added_for_future_assignment(): void
    {
        $this->seed(DemoSeeder::class);
        $userId = $this->getJson('/api/dashboard')->json('activeUser.id');

        $response = $this->postJson('/api/assign-project', [
            'user_id' => $userId,
            'project_name' => 'New Payroll Upgrade',
            'client_name' => 'Internal Platform',
            'allocation_percent' => 20,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('projects', [
            'name' => 'New Payroll Upgrade',
        ]);

        $this->assertDatabaseHas('assignments', [
            'user_id' => $userId,
            'allocation_percent' => 20,
        ]);
    }
}
