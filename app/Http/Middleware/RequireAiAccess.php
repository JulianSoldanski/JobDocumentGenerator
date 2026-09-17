<?php

namespace App\Http\Middleware;

use App\Ai\AiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ohne hinterlegten Schlüssel gibt es keinen KI-Aufruf.
 *
 * Die Prüfung steht bewusst vor dem Anlegen der Aufgabe: Ein Nutzer soll nicht
 * auf einen Hintergrund-Job warten, der ohnehin nur "kein Schlüssel" melden
 * kann.
 */
class RequireAiAccess
{
    public function __construct(private readonly AiClient $ai) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->ai->using($user)->hasCredentials()) {
            return response()->json([
                'message' => 'Für KI-Funktionen brauchst du einen eigenen API-Schlüssel. Trag ihn unter Profil → KI-Zugang ein.',
            ], 422);
        }

        return $next($request);
    }
}
