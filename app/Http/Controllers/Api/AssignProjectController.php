<?php

namespace App\Http\Controllers\Api;

use App\Models\Assignment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AssignProjectController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'project_name' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'allocation_percent' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        $project = Project::query()->firstOrCreate(
            ['name' => $validated['project_name']],
            [
                'client_name' => $validated['client_name'] ?: 'New Client',
                'health' => 'Healthy',
                'health_score' => 100,
                'utilization_percent' => 0,
                'confirmed_hours' => 0,
                'draft_hours' => 0,
            ],
        );

        Assignment::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'project_id' => $project->id,
            ],
            [
                'allocation_percent' => $validated['allocation_percent'],
                'source' => 'manual-add',
                'start_date' => now()->toDateString(),
                'end_date' => null,
            ],
        );

        return response()->json([
            'message' => 'Project added for the employee.',
            'project' => $project,
        ]);
    }
}
