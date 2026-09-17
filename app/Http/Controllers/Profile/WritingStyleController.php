<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Support\Translations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Prinzip 3: Nicht das Beispiel steuert die Stimme der KI, sondern die daraus
 * destillierten Regeln — und die gehören dem Nutzer.
 */
class WritingStyleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'example' => ['nullable', 'string', 'max:20000'],
            'rules' => ['array'],
            // Leere Zeilen sind beim Bearbeiten normal — sie fallen beim
            // Speichern weg, statt eine Fehlermeldung auszulösen.
            'rules.*' => ['nullable', 'string', 'max:500'],
        ]);

        $request->user()->style()->fill([
            'example' => $validated['example'] ?? '',
            'rules' => Translations::lines($validated['rules'] ?? []),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Schreibstil gespeichert.']);

        return back();
    }
}
