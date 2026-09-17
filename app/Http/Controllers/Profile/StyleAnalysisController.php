<?php

namespace App\Http\Controllers\Profile;

use App\Ai\AiTasks;
use App\Enums\AiTaskType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * „Stil analysieren": Das Beispiel geht an die KI, zurück kommen Regeln, die
 * der Nutzer vor dem Übernehmen bearbeitet.
 */
class StyleAnalysisController extends Controller
{
    public function store(Request $request, AiTasks $tasks): JsonResponse
    {
        $validated = $request->validate([
            'example' => ['required', 'string', 'min:200', 'max:20000'],
        ], [
            'example.required' => 'Füge zuerst ein Beispiel-Anschreiben ein.',
            'example.min' => 'Das Beispiel ist zu kurz, um daraus einen Stil abzuleiten.',
        ]);

        $task = $tasks->dispatch(
            $request->user(),
            AiTaskType::StyleAnalysis,
            ['example' => $validated['example']],
            $request->user()->style(),
        );

        return response()->json(['task_id' => $task->id]);
    }
}
