<?php

namespace App\Http\Controllers\Generator;

use App\Http\Controllers\Controller;
use App\Models\GeneratorSession;
use App\Support\JobPosting\PostingFetcher;
use App\Support\JobPosting\PostingFetchException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * „Adresse laden": Die Seite wird geholt und auf reinen Text reduziert.
 *
 * Das Ergebnis geht direkt in die Sitzung und zurück an den Browser — der
 * Nutzer sieht, was das Modell später lesen wird, und kann es korrigieren.
 */
class JobPostingController extends Controller
{
    public function fetch(Request $request, GeneratorSession $session, PostingFetcher $fetcher): JsonResponse
    {
        $this->authorize('update', $session);

        $validated = $request->validate([
            'url' => ['required', 'string', 'url', 'max:2048'],
        ], [
            'url.required' => 'Trag zuerst die Adresse der Anzeige ein.',
            'url.url' => 'Das ist keine vollständige Web-Adresse.',
        ]);

        try {
            $posting = $fetcher->fetch($validated['url']);
        } catch (PostingFetchException $e) {
            // Eine Seite, die sich nicht laden lässt, ist keine Störung,
            // sondern der Normalfall bei Portalen mit Anmeldung.
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $session->fill(['job_url' => $validated['url'], 'job_posting' => $posting]);
        $session->startTimer();
        $session->save();

        return response()->json(['posting' => $posting]);
    }
}
