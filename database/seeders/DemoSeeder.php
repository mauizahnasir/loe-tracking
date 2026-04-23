<?php

namespace Database\Seeders;

use App\Models\ActivitySignal;
use App\Models\Assignment;
use App\Models\Integration;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkEntry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('activity_signals')->delete();
        DB::table('assignments')->delete();
        DB::table('work_entries')->delete();
        DB::table('integrations')->delete();
        DB::table('projects')->delete();
        DB::table('users')->delete();
        Schema::enableForeignKeyConstraints();

        $mauizah = User::query()->create([
            'name' => 'Mauizah Nasir',
            'email' => 'mauizah.nasir@example.com',
            'role' => 'employee',
            'title' => 'Senior Software Engineer',
            'team' => 'Engineering',
            'department' => 'Engineering',
            'join_date' => '2022-08-15',
            'password' => Hash::make('password'),
        ]);

        $projects = collect([
            ['name' => 'BEE', 'client_name' => 'Bee Platform', 'health' => 'Healthy', 'health_score' => 100, 'utilization_percent' => 0, 'confirmed_hours' => 0, 'draft_hours' => 0],
            ['name' => 'BDC', 'client_name' => 'BDC Portal', 'health' => 'Healthy', 'health_score' => 100, 'utilization_percent' => 0, 'confirmed_hours' => 0, 'draft_hours' => 0],
            ['name' => 'Ermassess', 'client_name' => 'Ermassess Suite', 'health' => 'Healthy', 'health_score' => 100, 'utilization_percent' => 0, 'confirmed_hours' => 0, 'draft_hours' => 0],
            ['name' => 'Meetings & Misc', 'client_name' => 'Google Calendar', 'health' => 'Healthy', 'health_score' => 100, 'utilization_percent' => 0, 'confirmed_hours' => 0, 'draft_hours' => 0],
            ['name' => 'Time Off', 'client_name' => 'Google Calendar', 'health' => 'Healthy', 'health_score' => 100, 'utilization_percent' => 0, 'confirmed_hours' => 0, 'draft_hours' => 0],
        ])->keyBy('name')->map(fn (array $project) => Project::query()->create($project));

        Integration::query()->insert([
            ['name' => 'Google Calendar', 'type' => 'Calendar', 'status' => 'Healthy', 'coverage_percent' => 100, 'summary' => 'Meetings and time off are auto-populated from Google Calendar events.', 'is_connected' => true, 'last_sync_at' => now()->subMinutes(5), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jira', 'type' => 'Projects', 'status' => 'Healthy', 'coverage_percent' => 100, 'summary' => 'Assigned project tickets are listed from Jira, but hours stay at 0 until the employee fills them.', 'is_connected' => true, 'last_sync_at' => now()->subMinutes(8), 'created_at' => now(), 'updated_at' => now()],
        ]);

        Assignment::query()->insert([
            ['user_id' => $mauizah->id, 'project_id' => $projects['BEE']->id, 'allocation_percent' => 40, 'source' => 'jira-assignment', 'start_date' => now()->startOfMonth()->toDateString(), 'end_date' => null, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $mauizah->id, 'project_id' => $projects['BDC']->id, 'allocation_percent' => 30, 'source' => 'jira-assignment', 'start_date' => now()->startOfMonth()->toDateString(), 'end_date' => null, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $mauizah->id, 'project_id' => $projects['Ermassess']->id, 'allocation_percent' => 30, 'source' => 'jira-assignment', 'start_date' => now()->startOfMonth()->toDateString(), 'end_date' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $today = Carbon::today();

        $this->createEntry(
            $mauizah->id,
            $projects['BEE']->id,
            $today,
            0,
            'draft',
            88,
            'Jira pulled the ticket list, but Mauizah should enter the actual hours worked.',
            'Fill your BEE hours manually.',
            [
                ['source' => 'Jira', 'label' => 'BEE-142 Fix role-permission sync issue', 'minutes' => 0],
                ['source' => 'Jira', 'label' => 'BEE-151 Refactor payroll approval endpoint', 'minutes' => 0],
            ],
        );

        $this->createEntry(
            $mauizah->id,
            $projects['BDC']->id,
            $today,
            0,
            'draft',
            88,
            'Jira pulled the ticket list, but Mauizah should enter the actual hours worked.',
            'Fill your BDC hours manually.',
            [
                ['source' => 'Jira', 'label' => 'BDC-87 Resolve export timeout on reports', 'minutes' => 0],
                ['source' => 'Jira', 'label' => 'BDC-91 Add validation for customer import flow', 'minutes' => 0],
            ],
        );

        $this->createEntry(
            $mauizah->id,
            $projects['Ermassess']->id,
            $today,
            0,
            'draft',
            88,
            'Jira pulled the ticket list, but Mauizah should enter the actual hours worked.',
            'Fill your Ermassess hours manually.',
            [
                ['source' => 'Jira', 'label' => 'ERM-203 Update attendance reconciliation service', 'minutes' => 0],
                ['source' => 'Jira', 'label' => 'ERM-219 Review API contract for mobile app sync', 'minutes' => 0],
            ],
        );

        $this->createEntry(
            $mauizah->id,
            $projects['Meetings & Misc']->id,
            $today,
            1.5,
            'confirmed',
            96,
            'Google Calendar grouped today’s meetings and misc activity automatically.',
            'Auto-filled from calendar.',
            [
                ['source' => 'Google Calendar', 'label' => 'Engineering standup', 'minutes' => 30],
                ['source' => 'Google Calendar', 'label' => 'Sprint planning', 'minutes' => 60],
            ],
        );

        $this->createEntry(
            $mauizah->id,
            $projects['Time Off']->id,
            $today,
            2.0,
            'confirmed',
            99,
            'Google Calendar found a partial time off event and used only those 2 hours.',
            'Auto-filled from calendar partial time off.',
            [
                ['source' => 'Google Calendar', 'label' => 'Event - Time off', 'minutes' => 120],
            ],
        );

        $this->createEntry(
            $mauizah->id,
            $projects['BEE']->id,
            $today->copy()->subDays(1),
            4.0,
            'confirmed',
            92,
            'Hours marked for BEE work.',
            'Worked on BEE tasks.',
            [
                ['source' => 'Jira', 'label' => 'BEE-138 Fix attendance policy mapping', 'minutes' => 0],
            ],
        );

        $this->createEntry(
            $mauizah->id,
            $projects['BDC']->id,
            $today->copy()->subDays(1),
            3.0,
            'confirmed',
            92,
            'Hours marked for BDC work.',
            'Worked on BDC issues.',
            [
                ['source' => 'Jira', 'label' => 'BDC-83 Improve report query performance', 'minutes' => 0],
            ],
        );

        $this->createEntry(
            $mauizah->id,
            $projects['Meetings & Misc']->id,
            $today->copy()->subDays(1),
            2.0,
            'confirmed',
            96,
            'Google Calendar grouped meetings automatically.',
            'Auto-filled from calendar.',
            [
                ['source' => 'Google Calendar', 'label' => 'Architecture discussion', 'minutes' => 120],
            ],
        );

        $this->createEntry(
            $mauizah->id,
            $projects['Ermassess']->id,
            $today->copy()->subDays(3),
            5.0,
            'confirmed',
            91,
            'Hours marked for Ermassess work.',
            'Worked on Ermassess tasks.',
            [
                ['source' => 'Jira', 'label' => 'ERM-198 Fix mobile sync payload issue', 'minutes' => 0],
            ],
        );

        $this->createEntry(
            $mauizah->id,
            $projects['Meetings & Misc']->id,
            $today->copy()->subDays(3),
            1.0,
            'confirmed',
            96,
            'Google Calendar grouped meetings automatically.',
            'Auto-filled from calendar.',
            [
                ['source' => 'Google Calendar', 'label' => 'Client follow-up call', 'minutes' => 60],
            ],
        );

        $this->createEntry(
            $mauizah->id,
            $projects['Time Off']->id,
            $today->copy()->subDays(3),
            3.0,
            'confirmed',
            99,
            'Google Calendar found a partial time off event and used 3 hours.',
            'Auto-filled from calendar partial time off.',
            [
                ['source' => 'Google Calendar', 'label' => 'Event - Time off', 'minutes' => 180],
            ],
        );
    }

    private function createEntry(
        int $userId,
        int $projectId,
        Carbon $date,
        float $hours,
        string $status,
        int $confidence,
        string $explanation,
        string $note,
        array $signals,
    ): void {
        $entry = WorkEntry::query()->create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'entry_date' => $date->toDateString(),
            'hours' => $hours,
            'status' => $status,
            'confidence_score' => $confidence,
            'source' => 'integration-draft',
            'note' => $note,
            'explanation' => $explanation,
            'confirmed_at' => $status === 'confirmed' ? now() : null,
        ]);

        foreach ($signals as $signal) {
            ActivitySignal::query()->create([
                'work_entry_id' => $entry->id,
                ...$signal,
            ]);
        }
    }
}
