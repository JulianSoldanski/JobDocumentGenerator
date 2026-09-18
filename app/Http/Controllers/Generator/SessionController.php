<?php

namespace App\Http\Controllers\Generator;

use App\Enums\DocumentLayout;
use App\Enums\DocumentType;
use App\Enums\GenerationScope;
use App\Enums\Language;
use App\Http\Controllers\Controller;
use App\Models\GeneratorSession;
use App\Support\JobPosting\PostingText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Speichert, was links eingetragen wird: die Anzeige, die Felder, die
 * Einstellungen.
 */
class SessionController extends Controller
{
    /** Spalten ohne NULL: Ihr Leerwert ist der leere String. */
    private const REQUIRED_TEXT = ['company', 'position', 'contact_person', 'city'];

    public function update(Request $request, GeneratorSession $session): RedirectResponse
    {
        $this->authorize('update', $session);

        $validated = $request->validate([
            'job_url' => ['nullable', 'string', 'url', 'max:2048'],
            'job_posting' => ['nullable', 'string', 'max:100000'],
            'company' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'language' => ['required', Rule::enum(Language::class)],
            'layout' => ['required', Rule::enum(DocumentLayout::class)],
            'scope' => ['required', Rule::enum(GenerationScope::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'job_url.url' => 'Das ist keine vollständige Web-Adresse.',
        ]);

        // Ein geleertes Eingabefeld kommt als null an (ConvertEmptyStringsToNull).
        // Diese vier Spalten sind aber nicht nullable — ihr Leerwert ist der
        // leere String.
        foreach (self::REQUIRED_TEXT as $field) {
            if (array_key_exists($field, $validated)) {
                $validated[$field] = (string) $validated[$field];
            }
        }

        // Eingefügter Text bekommt dieselbe Bereinigung wie geladener, damit
        // das Modell in beiden Fällen dieselbe Grundlage sieht.
        if (array_key_exists('job_posting', $validated)) {
            $validated['job_posting'] = PostingText::normalize((string) $validated['job_posting']);
        }

        $session->fill($validated);
        $session->startTimer();
        $session->save();

        // Das Layout ist reine Darstellung: Ein Wechsel braucht kein neues
        // Generieren, der vorhandene Lebenslauf bekommt es direkt.
        if ($session->wasChanged('layout')) {
            $cv = $session->documents()->where('type', DocumentType::Cv->value)->first();

            if ($cv !== null) {
                $cv->layout = $session->layout;
                $cv->invalidateRendering();
                $cv->save();
            }
        }

        return back();
    }
}
