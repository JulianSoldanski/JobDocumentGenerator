<?php

namespace App\Http\Controllers\Generator;

use App\Ai\AiTasks;
use App\Enums\AiTaskType;
use App\Http\Controllers\Controller;
use App\Models\GeneratorSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * „Felder ausfüllen": Die Anzeige geht an die KI, zurück kommen Unternehmen,
 * Position, Ansprechperson, Ort und Anschrift.
 */
class ExtractFieldsController extends Controller
{
    public function store(Request $request, GeneratorSession $session, AiTasks $tasks): JsonResponse
    {
        $this->authorize('update', $session);

        if (! $session->hasPosting()) {
            return response()->json([
                'message' => 'Füge zuerst die Stellenanzeige ein oder lade sie über die Adresse.',
            ], 422);
        }

        $task = $tasks->dispatch($request->user(), AiTaskType::ExtractFields, [], $session);

        return response()->json(['task_id' => $task->id]);
    }
}
