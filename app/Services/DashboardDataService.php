<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Integration;
use App\Models\User;
use App\Models\WorkEntry;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;

class DashboardDataService
{
    public function build(?int $selectedUserId = null): array
    {
        $people = User::query()
            ->orderBy('name')
            ->get();

        $activeUser = $people->firstWhere('id', $selectedUserId) ?? $people->first();
        $today = Carbon::today();

        $todayEntries = WorkEntry::query()
            ->with(['project', 'signals'])
            ->where('user_id', $activeUser->id)
            ->whereDate('entry_date', $today)
            ->orderBy('project_id')
            ->orderBy('id')
            ->get();

        $monthEntries = WorkEntry::query()
            ->with('project')
            ->where('user_id', $activeUser->id)
            ->whereBetween('entry_date', [
                $today->copy()->startOfMonth()->toDateString(),
                $today->copy()->endOfMonth()->toDateString(),
            ])
            ->orderByDesc('entry_date')
            ->orderBy('project_id')
            ->orderBy('id')
            ->get();

        $assignments = Assignment::query()
            ->with('project')
            ->where('user_id', $activeUser->id)
            ->whereDate('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->orderByDesc('allocation_percent')
            ->get();

        $projectAssignments = $assignments
            ->filter(fn (Assignment $assignment) => ! in_array($assignment->project->name, ['Meetings & Misc', 'Time Off'], true))
            ->values();

        $monthColumns = collect($projectAssignments->map(fn (Assignment $assignment) => $assignment->project->name)->all())
            ->merge(['Meetings & Misc', 'Time Off'])
            ->unique()
            ->values();

        $workingDates = collect(iterator_to_array(
            CarbonPeriod::create($today->copy()->startOfMonth(), $today->copy()->endOfMonth())
                ->filter(fn (Carbon $date) => ! $date->isWeekend())
                ->map(fn (Carbon $date) => $date->format('Y-m-d'))
        ))->values();

        $meetingEntries = $todayEntries
            ->filter(fn (WorkEntry $entry) => $entry->project->name === 'Meetings & Misc')
            ->values();

        $timeOffEntries = $todayEntries
            ->filter(fn (WorkEntry $entry) => $entry->project->name === 'Time Off')
            ->values();

        $loggedHours = (float) $todayEntries->sum('hours');
        $meetingHours = (float) $meetingEntries->sum('hours');
        $timeOffHours = (float) $timeOffEntries->sum('hours');

        return [
            'today' => $today->toDateString(),
            'people' => $people->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'title' => $user->title,
                'team' => $user->team,
            ])->values(),
            'activeUser' => [
                'id' => $activeUser->id,
                'name' => $activeUser->name,
                'title' => $activeUser->title,
                'team' => $activeUser->team,
                'department' => $activeUser->department,
                'joinDate' => optional($activeUser->join_date)->format('Y-m-d'),
            ],
            'day' => [
                'targetHours' => 9,
                'loggedHours' => $loggedHours,
                'remainingHours' => max(0, 9 - $loggedHours),
                'projectHours' => max(0, $loggedHours - $meetingHours - $timeOffHours),
                'meetingHours' => $meetingHours,
                'timeOffHours' => $timeOffHours,
                'allMarked' => $loggedHours >= 9,
            ],
            'tabs' => [
                'projects' => $projectAssignments->map(function (Assignment $assignment) use ($todayEntries) {
                    $entries = $todayEntries->where('project_id', $assignment->project_id)->values();

                    return [
                        'projectId' => $assignment->project_id,
                        'project' => $assignment->project->name,
                        'client' => $assignment->project->client_name,
                        'assignedPercent' => $assignment->allocation_percent,
                        'tickets' => $entries->map(function (WorkEntry $entry) {
                            $jiraSignal = $entry->signals->firstWhere('source', 'Jira');

                            return [
                                'id' => $entry->id,
                                'title' => $jiraSignal?->label ?? 'Jira ticket',
                                'hours' => (float) $entry->hours,
                                'status' => $entry->status,
                                'note' => $entry->note,
                                'details' => $entry->explanation,
                                'source' => 'jira',
                            ];
                        })->values(),
                        'totalHours' => (float) $entries->sum('hours'),
                    ];
                })->values(),
                'meetings' => [
                    'title' => 'Meetings & Misc',
                    'totalHours' => $meetingHours,
                    'entries' => $meetingEntries->map(fn (WorkEntry $entry) => [
                        'id' => $entry->id,
                        'title' => $entry->signals->first()?->label ?? 'Calendar event',
                        'hours' => (float) $entry->hours,
                        'status' => $entry->status,
                        'note' => $entry->note,
                        'details' => $entry->explanation,
                        'source' => 'calendar',
                    ])->values(),
                ],
                'timeOff' => [
                    'title' => 'Time Off',
                    'rule' => 'Whole-day time off events count as 9 hours. Partial time off uses only the event hours.',
                    'totalHours' => $timeOffHours,
                    'entries' => $timeOffEntries->map(fn (WorkEntry $entry) => [
                        'id' => $entry->id,
                        'title' => $entry->signals->first()?->label ?? 'Time off event',
                        'hours' => (float) $entry->hours,
                        'status' => $entry->status,
                        'note' => $entry->note,
                        'details' => $entry->explanation,
                        'source' => 'calendar',
                        'isFullDay' => Str::contains(strtolower($entry->explanation ?? ''), 'whole-day'),
                    ])->values(),
                ],
            ],
            'projectOptions' => [
                'suggested' => ['BEE', 'BDC', 'Ermassess', 'New Project'],
                'defaultAllocationPercent' => 25,
            ],
            'month' => [
                'label' => $today->format('F Y'),
                'numberOfDays' => $today->daysInMonth,
                'weekendDays' => $today->daysInMonth - $workingDates->count(),
                'workingDays' => $workingDates->count(),
                'totalHours' => (float) $monthEntries->sum('hours'),
                'loePercent' => $workingDates->count() > 0
                    ? round((((float) $monthEntries->sum('hours') / 8) / $workingDates->count()) * 100, 1)
                    : 0,
                'columns' => $monthColumns,
                'rows' => $workingDates
                    ->map(function (string $date) use ($monthEntries, $monthColumns) {
                        $entries = $monthEntries->filter(
                            fn (WorkEntry $entry) => $entry->entry_date->format('Y-m-d') === $date
                        );

                        return [
                            'date' => $date,
                            'projects' => $monthColumns->mapWithKeys(fn (string $project) => [
                                $project => (float) $entries->where('project.name', $project)->sum('hours'),
                            ]),
                            'totalHours' => (float) $entries->sum('hours'),
                        ];
                    })
                    ->values(),
                'columnTotals' => $monthColumns->mapWithKeys(fn (string $project) => [
                    $project => (float) $monthEntries->where('project.name', $project)->sum('hours'),
                ]),
                'columnDays' => $monthColumns->mapWithKeys(fn (string $project) => [
                    $project => round(((float) $monthEntries->where('project.name', $project)->sum('hours')) / 8, 1),
                ]),
                'columnPercents' => $monthColumns->mapWithKeys(fn (string $project) => [
                    $project => $workingDates->count() > 0
                        ? round(((((float) $monthEntries->where('project.name', $project)->sum('hours')) / 8) / $workingDates->count()) * 100)
                        : 0,
                ]),
                'totalDays' => round(((float) $monthEntries->sum('hours')) / 8, 1),
            ],
            'integrations' => Integration::query()
                ->whereIn('name', ['Google Calendar', 'Jira'])
                ->orderBy('name')
                ->get()
                ->map(fn (Integration $integration) => [
                    'id' => $integration->id,
                    'name' => $integration->name,
                    'status' => $integration->status,
                    'summary' => $integration->summary,
                    'isConnected' => (bool) $integration->is_connected,
                    'lastSyncAt' => optional($integration->last_sync_at)->format('M d, g:i A'),
                ])->values(),
        ];
    }
}
