<?php

namespace App\Http\Controllers\Profile;

use App\Ai\AiTasks;
use App\Enums\AiTaskType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Entwurf der ausführlichen Projektbeschreibung aus der Kurzfassung.
 */
class ProjectDraftController extends Controller
{
    public function store(Request $request, AiTasks $tasks): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:2000'],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:50'],
            'grade' => ['nullable', 'string', 'max:50'],
        ], [
            'title.required' => 'Für einen Entwurf braucht es Titel und Kurzbeschreibung.',
            'summary.required' => 'Für einen Entwurf braucht es Titel und Kurzbeschreibung.',
        ]);

        // Nur unter den eigenen Projekten gesucht: eine fremde Kennung führt
        // hier nicht zu fremden Daten im Prompt.
        $project = null;
        if (! empty($validated['project_id'])) {
            $project = $request->user()->projects()
                ->whereKey($validated['project_id'])
                ->firstOrFail();
        }

        $task = $tasks->dispatch(
            $request->user(),
            AiTaskType::ProjectDraft,
            [
                'title' => $validated['title'],
                'summary' => $validated['summary'],
                'tags' => $validated['tags'] ?? [],
                'grade' => $validated['grade'] ?? '',
            ],
            $project,
        );

        return response()->json(['task_id' => $task->id]);
    }
}
