<?php

namespace App\Http\Controllers\Api;

use App\Models\WorkEntry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ConfirmWorkEntryController extends Controller
{
    public function __invoke(Request $request, WorkEntry $workEntry)
    {
        $validated = $request->validate([
            'hours' => ['required', 'numeric', 'min:0', 'max:12'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $hours = (float) $validated['hours'];
        $isMarked = $hours > 0;

        $workEntry->update([
            'hours' => $hours,
            'note' => $validated['note'] ?? null,
            'status' => $isMarked ? 'confirmed' : 'draft',
            'confirmed_at' => $isMarked ? now() : null,
            'confidence_score' => $isMarked
                ? min(100, max(72, $workEntry->confidence_score + 10))
                : $workEntry->confidence_score,
        ]);

        return response()->json([
            'message' => $isMarked ? 'Entry saved.' : 'Entry kept as draft.',
            'entry' => $workEntry->fresh(['project', 'signals']),
        ]);
    }
}
