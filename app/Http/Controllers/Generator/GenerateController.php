<?php

namespace App\Http\Controllers\Generator;

use App\Ai\AiTasks;
use App\Documents\CvAssembler;
use App\Enums\AiTaskType;
use App\Http\Controllers\Controller;
use App\Models\GeneratorSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * „Generieren": Lebenslauf, Anschreiben und — falls noch keine da ist — die
 * Stellen-Übersicht, je nach gewähltem Umfang.
 *
 * Jedes Dokument ist eine eigene Aufgabe, damit das eine schon sichtbar ist,
 * während das andere noch entsteht.
 */
class GenerateController extends Controller
{
    public function store(Request $request, GeneratorSession $session, AiTasks $tasks): JsonResponse
    {
        $this->authorize('update', $session);

        $user = $request->user();

        if (! $session->hasPosting()) {
            return response()->json([
                'message' => 'Füge zuerst die Stellenanzeige ein oder lade sie über die Adresse.',
            ], 422);
        }

        // Geprüft, bevor ein Aufruf Geld kostet: Ein leeres Profil ergäbe einen
        // leeren Lebenslauf und ein Anschreiben voller Erfundenem.
        if (! (new CvAssembler($user, $session->language))->hasContent()) {
            return response()->json([
                'message' => 'Dein Profil ist noch leer. Der Lebenslauf wird daraus zusammengesetzt — am schnellsten füllst du es über Profil → Import aus einer bestehenden PDF.',
            ], 422);
        }

        $session->bankResearchTime();
        $session->save();

        $dispatch = fn (AiTaskType $type): string => $tasks->dispatch($user, $type, [], $session)->id;

        return response()->json([
            'tasks' => [
                'cv' => $session->scope->includesCv() ? $dispatch(AiTaskType::CvSelection) : null,
                'letter' => $session->scope->includesLetter() ? $dispatch(AiTaskType::CoverLetter) : null,
                'summary' => $session->summary === null ? $dispatch(AiTaskType::JobSummary) : null,
            ],
        ]);
    }
}
