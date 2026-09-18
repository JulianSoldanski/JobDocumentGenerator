<?php

namespace App\Http\Controllers\Generator;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneratorSessionResource;
use App\Models\GeneratorSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Der Arbeitsplatz: links die Stelle, rechts das Dokument.
 *
 * Die Sitzung liegt auf dem Server, nicht im Browser — ein Neuladen bleibt
 * damit dort, wo der Nutzer war, und die Uhr für die Recherchezeit läuft
 * unabhängig vom Tab weiter.
 */
class GeneratorController extends Controller
{
    /**
     * Ohne Angabe die zuletzt bearbeitete Sitzung. Gibt es keine, entsteht
     * eine leere — der Generator ist der Einstieg, kein Formular, das man
     * erst anlegen muss.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $session = $user->generatorSessions()->latest('updated_at')->first()
            ?? $user->generatorSessions()->create([]);

        return $this->render($session);
    }

    public function show(Request $request, GeneratorSession $session): Response
    {
        $this->authorize('view', $session);

        return $this->render($session);
    }

    private function render(GeneratorSession $session): Response
    {
        return Inertia::render('generator/index', [
            'session' => GeneratorSessionResource::make($session)->resolve(),
        ]);
    }
}
